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

use pocketmine\nbt\tag\CompoundTag;

class PistonHead extends Spawnable{
	public const TAG_PROGRESS = "Progress";
	public const TAG_STATE = "State";
	public const TAG_STICKY = "Sticky";

	/** @var float */
	protected $progress;
	/** @var int */
	protected $state;
	/** @var int */
	protected $sticky;

	/**
	 * @return float
	 */
	public function getProgress() : float{
		return $this->progress;
	}

	/**
	 * @param float $progress
	 */
	public function setProgress(float $progress) : void{
		$this->progress = $progress;
	}

	/**
	 * @return int
	 */
	public function getState() : int{
		return $this->state;
	}

	/**
	 * @param int $state
	 */
	public function setState(int $state) : void{
		$this->state = $state;
	}

	/**
	 * @return int
	 */
	public function getSticky() : int{
		return $this->sticky;
	}

	/**
	 * @param int $sticky
	 */
	public function setSticky(int $sticky) : void{
		$this->sticky = $sticky;
	}

	protected function readSaveData(CompoundTag $nbt) : void{
		$this->progress = $nbt->getFloat(self::TAG_PROGRESS, 0.0, true);
		$this->state = $nbt->getByte(self::TAG_STATE, 0, true);
		$this->sticky = $nbt->getByte(self::TAG_STICKY, 0, true);
	}

	public function getDefaultName() : string{
		return "PistonHead";
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$nbt->setFloat(self::TAG_PROGRESS, $this->progress, true);
		$nbt->setByte(self::TAG_STATE, $this->state, true);
		$nbt->setByte(self::TAG_STICKY, $this->sticky, true);
	}

	public function addAdditionalSpawnData(CompoundTag $nbt) : void{
		$nbt->setFloat(self::TAG_PROGRESS, $this->progress);
		$nbt->setByte(self::TAG_STATE, $this->state);
		$nbt->setByte(self::TAG_STICKY, $this->sticky);
	}
}
