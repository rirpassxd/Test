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

namespace pocketmine\level\generator\populator;

use pocketmine\block\BlockIds;
use pocketmine\level\ChunkManager;
use pocketmine\utils\Random;
use function cos;
use function deg2rad;
use function mt_rand;
use function pi;
use function sin;

class EnderPilar extends Populator{
    /** @var ChunkManager */
    private $level;
    private $randomAmount;
    private $baseAmount;

    public function setRandomAmount($amount){
        $this->randomAmount = $amount;
    }

    public function setBaseAmount($amount){
        $this->baseAmount = $amount;
    }

    public function populate(ChunkManager $level, int $chunkX, int $chunkZ, Random $random){
        if (mt_rand(0, 100) < 10) {
            $this->level = $level;
            $amount = $random->nextRange(0, $this->randomAmount + 1) + $this->baseAmount;
            for ($i = 0; $i < $amount; ++$i) {
                $x = $random->nextRange($chunkX * 16, $chunkX * 16 + 15);
                $z = $random->nextRange($chunkZ * 16, $chunkZ * 16 + 15);
                $y = $this->getHighestWorkableBlock($x, $z);
                if ($this->level->getBlockIdAt($x, $y, $z) === BlockIds::END_STONE) {
                    $height = mt_rand(28, 50);
                    for ($ny = $y; $ny < $y + $height; $ny++) {
                        for ($r = 0.5; $r < 5; $r += 0.5) {
                            $nd = 360 / (2 * pi() * $r);
                            for ($d = 0; $d < 360; $d += $nd) {
                                $level->setBlockIdAt((int) ($x + (cos(deg2rad($d)) * $r)), (int) $ny, (int) ($z + (sin(deg2rad($d)) * $r)), BlockIds::OBSIDIAN);
                            }
                        }
                    }
                }
            }
        }
    }

    private function getHighestWorkableBlock(int $x, int $z) : int{
        for ($y = 127; $y >= 0; --$y) {
            $b = $this->level->getBlockIdAt($x, $y, $z);
            if ($b === BlockIds::END_STONE) {
                break;
            }
        }
        return $y === 0 ? -1 : $y;
    }
}