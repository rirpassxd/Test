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

namespace pocketmine\level\format\io;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\math\Vector3;
use pocketmine\thread\NonThreadSafeValue;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\block\BlockFactory;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\network\mcpe\multiversion\block\BlockPalette;
use pocketmine\network\mcpe\multiversion\block\palettes\Palette;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\tile\Spawnable;
use function assert;
use function strlen;

class ChunkRequestTask extends AsyncTask{
    /** @var int */
	protected $levelId;
	/** @var string */
	protected $chunk;
	/** @var int */
	protected $chunkX;
	/** @var int */
	protected $chunkZ;
	/** @var int */
	protected $dimensionId;
	/** @var string */
    protected $tiles;
	/** @var NonThreadSafeValue */
	protected $palette;
    /** @var int */
	protected $compressionLevel;
	/** @var int */
	protected $protocol;
	/** @var int */
	protected $levelChunkPacketId;
	protected $obfuscate;

    const REPLACE_WITH = [
        BlockIds::GOLD_ORE, BlockIds::GOLD_BLOCK,
        BlockIds::IRON_ORE, BlockIds::IRON_BLOCK,
        BlockIds::LAPIS_ORE, BlockIds::LAPIS_BLOCK,
        BlockIds::DIAMOND_ORE, BlockIds::DIAMOND_BLOCK,
        BlockIds::REDSTONE_ORE, BlockIds::REDSTONE_BLOCK,
        BlockIds::EMERALD_ORE, BlockIds::EMERALD_BLOCK,
    ];

	public function __construct(Level $level, int $chunkX, int $chunkZ, int $dimensionId, Chunk $chunk, int $protocol, $obfuscate){
		$this->levelId = $level->getId();
		$this->compressionLevel = $level->getServer()->networkCompressionLevel;

        $this->obfuscate = $obfuscate;
		$this->chunkX = $chunkX;
		$this->chunkZ = $chunkZ;
		$this->dimensionId = $dimensionId;
		$this->chunk = $chunk->fastSerialize();

        $tiles = "";
		foreach($chunk->getTiles() as $tile){
			if($tile instanceof Spawnable){
				$tiles .= $tile->getProtocolSerializedSpawnCompound($protocol);
			}
		}
        $this->tiles = $tiles;

		$this->palette = new NonThreadSafeValue(BlockPalette::getPalette($protocol));

		$this->protocol = $protocol;

		$this->levelChunkPacketId = PacketPool::getPacketIdByMagic(LevelChunkPacket::NETWORK_ID, $protocol);
	}

	public function onRun() : void{
		BlockFactory::init();

	    $chunk = Chunk::fastDeserialize($this->chunk);
		$dimensionId = $this->dimensionId;

        $protocol = $this->protocol;

        if($this->obfuscate){
            self::obfuscateChunk($chunk);
        }

		if(($palette = $this->palette->deserialize()) !== null){
			BlockPalette::addPalette($palette, $protocol);
		    $legacyToRuntime = function(int $blockId, int $meta) use ($palette) : int{
			    return $palette::toStaticRuntimeId($blockId, $meta);
		    };
		}

	    $pk = LevelChunkPacket::withoutCache(
	        new ChunkPosition($this->chunkX, $this->chunkZ),
	        LevelChunkPacket::ORDER_COLUMNS,
	        $dimensionId,
	        $chunk->getSubChunkSendCount($dimensionId, $protocol),
	        $chunk->networkSerialize($protocol, $dimensionId, $legacyToRuntime ?? null) . $this->tiles
	    );
		$pk->setPacketIdToSend($this->levelChunkPacketId);
	    $pk->setProtocol($protocol);

	    $batch = new BatchPacket();
	    $batch->setProtocol($protocol);
	    $batch->addPacket($pk);
	    $batch->setCompressionLevel($this->compressionLevel);
	    $batch->encode();

        $this->setResult($batch->buffer);
	}

	public function onCompletion(Server $server){
		$level = $server->getLevel($this->levelId);
		if($level instanceof Level){
			if($this->hasResult()){
			    $buffer = $this->getResult();
			    $protocol = $this->protocol;

			    $batch = new BatchPacket($buffer);
			   	assert(strlen($batch->buffer) > 0);
			    $batch->setProtocol($protocol);
			    $batch->isEncoded = true;

				$level->chunkRequestCallback($this->chunkX, $this->chunkZ, $protocol, $batch);
			}else{
				$server->getLogger()->error("Chunk request (protocol: {$this->protocol}) for world #" . $this->levelId . ", x=" . $this->chunkX . ", z=" . $this->chunkZ . " doesn't have any result data");
			}
		}else{
			$server->getLogger()->debug("Dropped chunk task due to world not loaded");
		}
	}

    public static function obfuscateChunk(Chunk $chunk): void{
        for($yy = 0; $yy <= 8; $yy++){
            $subchunk = $chunk->getSubChunk($yy);
            for($x = 1; $x < 15; $x++){
                for($z = 1; $z < 15; $z++){
                    for($y = 1; $y < 15; $y++){
                        if($subchunk->getBlockId($x, $y, $z) !== 1){
                            continue;
                        }
                        $vector = new Vector3($chunk->getX() * 16 + $x, $yy * 16 + $y, $chunk->getZ() * 16 + $z);
                        foreach([Vector3::SIDE_DOWN, Vector3::SIDE_UP, Vector3::SIDE_NORTH, Vector3::SIDE_SOUTH, Vector3::SIDE_WEST, Vector3::SIDE_EAST] as $side){
                            $side = $vector->getSide($side);
                            $blockId = $chunk->getBlockId($side->x & 0x0f, $side->y, $side->z & 0x0f);
                            if($blockId !== Block::STONE){
                                continue 2;
                            }
                        }
                        $subchunk->setBlockId($x, $y, $z, self::REPLACE_WITH[array_rand(self::REPLACE_WITH)]);
                    }
                }
            }
        }
    }
}
