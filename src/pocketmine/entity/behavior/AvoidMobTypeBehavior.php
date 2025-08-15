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

namespace pocketmine\entity\behavior;

use Closure;
use pocketmine\entity\Entity;
use pocketmine\entity\Mob;
use pocketmine\entity\utils\RandomPositionGenerator;
use pocketmine\Player;

class AvoidMobTypeBehavior extends Behavior{

	protected $targetEntityClass;
	protected $avoidDistance;
	protected $farSpeed;
	protected $nearSpeed;

	protected $nearestEntity;

	protected $path;

	public function __construct(Mob $mob, string $targetEntityClass, float $avoidDistance, float $farSpeed, float $nearSpeed){
		parent::__construct($mob);

		$this->targetEntityClass = $targetEntityClass;
		$this->avoidDistance = $avoidDistance;
		$this->farSpeed = $farSpeed;
		$this->nearSpeed = $nearSpeed;

		$this->setMutexBits(1);
	}

	public function canStart() : bool{
		$nearest = $this->mob->level->getNearestEntity($this->mob, $this->avoidDistance, $this->targetEntityClass);

		if($nearest !== null){
			$this->nearestEntity = $nearest;

			$vec = RandomPositionGenerator::findRandomTargetBlockAwayFrom($this->mob, 16, 7, $nearest);

			if($vec !== null and $nearest->distanceSquared($vec) >= $nearest->distanceSquared($this->mob)){
				$this->path = $this->mob->getNavigator()->findPath($vec);
				return true;
			}
		}

		return false;
	}

	public function canContinue() : bool{
		return $this->mob->getNavigator()->isBusy();
	}

	public function onStart() : void{
		$this->mob->getNavigator()->setPath($this->path);
		$this->mob->getNavigator()->setSpeedMultiplier($this->farSpeed);
	}

	public function onTick() : void{
		if($this->mob->distanceSquared($this->nearestEntity) < 49){
			$this->mob->getNavigator()->setSpeedMultiplier($this->nearSpeed);
		}else{
			$this->mob->getNavigator()->setSpeedMultiplier($this->farSpeed);
		}
	}

	public function onEnd() : void{
		$this->nearestEntity = null;
	}
}