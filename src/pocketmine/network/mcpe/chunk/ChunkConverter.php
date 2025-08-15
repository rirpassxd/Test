<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\network\mcpe\chunk;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\world\format\PalettedBlockArray;
use function str_repeat;
use function ord;
use function chr;

final class ChunkConverter{

    /**
     * @param PalettedBlockArray $palettedBlockArray
     * @param int $protocol
     * 
     * @return string[]
     */
    public static function convertSubChunkFromPaletteXZY(PalettedBlockArray $palettedBlockArray, int $protocol) : array{
		// thx Submarine :)
		$blockIdArray = "";
		$blockDataArray = "";
		for($x = 0; $x < 16; ++$x){
			for($z = 0; $z < 16; ++$z){
			    for($y = 0; $y < 16; ++$y){
				    $fullBlock = $palettedBlockArray->get($x, $y, $z);
					$block = BlockFactory::get($fullBlock >> Block::INTERNAL_METADATA_BITS, $fullBlock & Block::INTERNAL_METADATA_MASK);
					$block = $block->getBlockProtocol($protocol) ?? $block;
					[$legacyId, $legacyMeta] = [$block->getId(), $block->getDamage()];

					if($legacyId > 255){
						$legacyId = 248; //minecraft:info_update
						$legacyMeta = 0;
					}

					$blockIdArray[($x << 8) | ($z << 4) | $y] = chr($legacyId);
					$indexData = ($x << 7) | ($z << 3) | ($y >> 1);
					if(($y & 1) === 0){
							  $blockDataArray[$indexData] = chr((ord($blockDataArray[$indexData] ?? chr(0)) & 0xf0) | ($legacyMeta & 0x0f));
					}else{
	    	            $blockDataArray[$indexData] = chr((($legacyMeta & 0x0f) << 4) | (ord($blockDataArray[$indexData] ?? chr(0)) & 0x0f));
					}
				}
			}
		}

		return [$blockIdArray, $blockDataArray];
    }

    /**
     * @param PalettedBlockArray[] $palettedBlocks
     *
     * @return string[]
     */
    public static function convertSubChunkFromPaletteColumn(array $palettedBlocks, int $protocol) : array{
        $ids = str_repeat("\x00", 32768);
        $data = str_repeat("\x00", 16384);

        $yOffset = 0;
        foreach($palettedBlocks as $palettedBlockArray){
            for($x = 0; $x < 16; ++$x){
                for($z = 0; $z < 16; ++$z){
                    for($y = 0; $y < 16; ++$y){
                        $yy = ($yOffset << 4) | $y;
                        $idx = ($x << 11) | ($z << 7) | $yy;
                        $dataIdx = $idx >> 1;
                
				        $fullBlock = $palettedBlockArray->get($x, $y, $z);
				    	$block = BlockFactory::get($fullBlock >> Block::INTERNAL_METADATA_BITS, $fullBlock & Block::INTERNAL_METADATA_MASK);
				    	$block = $block->getBlockProtocol($protocol) ?? $block;
				    	[$legacyId, $legacyMeta] = [$block->getId(), $block->getDamage()];

				    	if($legacyId > 255){
					    	$legacyId = 248; //minecraft:info_update
					    	$legacyMeta = 0;
				    	}

                        $ids[$idx] = chr($legacyId);

                        $current = ord($data[$dataIdx] ?? "\x00");
                        if(($yy & 1) === 0){
                            $current = ($current & 0xf0) | $legacyMeta;
                        }else{
                            $current = ($current & 0x0f) | ($legacyMeta << 4);
                        }
                        $data[$dataIdx] = chr($current);
                    }
                }
            }

            $yOffset++;
        }

        return [$ids, $data];
    }

    public static function unreorderNibbleArray(string $reordered, string $commonValue = "\x00") : string {
        $result = str_repeat($commonValue, 2048);

        if($reordered !== $result){
            $i = 0;
            for($x = 0; $x < 8; ++$x){
                for($z = 0; $z < 16; ++$z){
                    $zx = (($z << 3) | $x);
                    for($y = 0; $y < 8; ++$y){
                        $j = (($y << 8) | $zx);
                        $j80 = ($j | 0x80);
                    
                        if($reordered[$i] === $commonValue && $reordered[$i | 0x80] === $commonValue){
                            //values are already filled with commonValue
                        }else{
                            $byte1 = ord($reordered[$i]);
                            $byte2 = ord($reordered[$i | 0x80]);

                            $result[$j]   = chr($byte1 & 0x0f | (($byte2 & 0x0f) << 4));
                            $result[$j80] = chr(($byte1 >> 4) | ($byte2 & 0xf0));
                        }
                        $i++;
                    }
                }
                $i += 128;
            }
        }

        return $result;
    }
}
