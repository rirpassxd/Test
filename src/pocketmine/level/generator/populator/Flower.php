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
use pocketmine\block\Flower as FlowerBlock;
use pocketmine\level\ChunkManager;
use pocketmine\utils\Random;
use function count;
use function mt_rand;

class Flower extends Populator{
	/** @var ChunkManager */
	private $level;
	private $randomAmount;
	private $baseAmount = 8;

	private $flowerTypes = [];

	public function setRandomAmount($amount){
		$this->randomAmount = $amount;
	}

	public function setBaseAmount($amount){
		$this->baseAmount = $amount;
	}

	public function addType($type){
		$this->flowerTypes[] = $type;
	}

	public function getTypes(){
		return $this->flowerTypes;
	}

	public function populate(ChunkManager $level, int $chunkX, int $chunkZ, Random $random){
		$this->level = $level;
		$amount = $random->nextRange(0, $this->randomAmount + 1) + $this->baseAmount;

		if(count($this->flowerTypes) === 0){
			$this->addType([BlockIds::DANDELION, 0]);
			$this->addType([BlockIds::RED_FLOWER, FlowerBlock::TYPE_POPPY]);
		}

		$endNum = count($this->flowerTypes) - 1;

		for($i = 0; $i < $amount; ++$i){
			$x = $random->nextRange($chunkX * 16, $chunkX * 16 + 15);
			$z = $random->nextRange($chunkZ * 16, $chunkZ * 16 + 15);
			$y = $this->getHighestWorkableBlock($x, $z);
			if($y !== -1 and $this->canFlowerStay($x, $y, $z)){
				$type = mt_rand(0, $endNum);
				$this->level->setBlockIdAt($x, $y, $z, $this->flowerTypes[$type][0]);
				$this->level->setBlockDataAt($x, $y, $z, $this->flowerTypes[$type][1]);
			}
		}
	}

	private function canFlowerStay(int $x, int $y, int $z) : bool{
		$b = $this->level->getBlockIdAt($x, $y, $z);
		return ($b === BlockIds::AIR or $b === BlockIds::SNOW_LAYER) and $this->level->getBlockIdAt($x, $y - 1, $z) === BlockIds::GRASS;
	}

	private function getHighestWorkableBlock(int $x, int $z) : int{
		for($y = 127; $y >= 0; --$y){
			$b = $this->level->getBlockIdAt($x, $y, $z);
			if($b !== BlockIds::AIR and $b !== BlockIds::LEAVES and $b !== BlockIds::LEAVES2 and $b !== BlockIds::SNOW_LAYER){
				break;
			}
		}
		return $y === 0 ? -1 : ++$y;
	}
}