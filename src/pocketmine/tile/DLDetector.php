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

namespace pocketmine\tile;

use pocketmine\block\BlockIds;
use pocketmine\block\DaylightSensor;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;

class DLDetector extends Spawnable{
	/** @var CompoundTag */
	private $nbt;

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->scheduleUpdate();
	}

	/**
	 * @return int
	 */
	public function getLightByTime() : int{
		$time = $this->getLevelNonNull()->getTime();
		if(($time >= Level::TIME_DAY and $time <= Level::TIME_SUNSET) or
			($time >= Level::TIME_SUNRISE and $time <= Level::TIME_FULL)
		){
			return 15;
		}

		return 0;
	}

	/**
	 * @return bool
	 */
	public function isActivated() : bool{
		if($this->getType() === BlockIds::DAYLIGHT_SENSOR){
			if($this->getLightByTime() === 15){
				return true;
			}

			return false;
		}else{
			if($this->getLightByTime() === 0){
				return true;
			}

			return false;
		}
	}

	/**
	 * @return int
	 */
	private function getType() : int{
		return $this->getBlock()->getId();
	}

	/**
	 * @return bool
	 */
	public function onUpdate() : bool{
		if($this->closed){
			return false;
		}

		$this->timings->startTiming();

		if(($this->getLevelNonNull()->getServer()->getTick() % 3) === 0){ //Update per 3 ticks
			/** @var DaylightSensor $block */
			$block = $this->getBlock();
			if(!$this->isActivated()){
				$block->deactivate();
			}else{
				$block->activate();
			}
		}

		$this->timings->stopTiming();

		return true;
	}

	protected function readSaveData(CompoundTag $nbt) : void{
		$this->nbt = $nbt;
	}

	protected function writeSaveData(CompoundTag $nbt) : void{

	}

	public function addAdditionalSpawnData(CompoundTag $nbt) : void{

	}
}