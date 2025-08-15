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

namespace pocketmine\level\generator;

use pocketmine\block\Block;
use pocketmine\level\format\Chunk;
use pocketmine\math\Vector3;

class VoidGenerator extends Generator{
	/** @var Chunk */
	private $chunk;
	/** @var ?Chunk */
	private $emptyChunk = null;

	public function getSettings() : array{
		return [];
	}

	public function getName() : string{
		return "void";
	}

	/**
	 * @param array $options
	 *
	 * @throws InvalidGeneratorOptionsException
	 */
	public function __construct(array $settings = []){

	}

	public function generateChunk(int $chunkX, int $chunkZ) : void{
		if($this->emptyChunk === null){
			$this->chunk = clone $this->level->getChunk($chunkX, $chunkZ);
			$this->chunk->setGenerated();

			for($Z = 0; $Z < 16; ++$Z){
				for($X = 0; $X < 16; ++$X){
					$this->chunk->setBiomeId($X, $Z, 1);
					for($y = 0; $y < 128; ++$y){
						$this->chunk->setBlockId($X, $y, $Z, Block::AIR);
					}
				}
			}

			$spawn = $this->getSpawn();
			if($spawn->getX() >> 4 === $chunkX and $spawn->getZ() >> 4 === $chunkZ){
				$this->chunk->setBlockId(0, 64, 0, Block::GRASS);
			}else{
				$this->emptyChunk = clone $this->chunk;
			}
		}else{
			$this->chunk = clone $this->emptyChunk;
		}

		$chunk = clone $this->chunk;
		$chunk->setX($chunkX);
		$chunk->setZ($chunkZ);
		$this->level->setChunk($chunkX, $chunkZ, $chunk);
	}

	public function populateChunk(int $chunkX, int $chunkZ) : void{

	}

	public function getSpawn() : Vector3{
		return new Vector3(128, 72, 128);
	}
}