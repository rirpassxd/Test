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

use pocketmine\entity\Animal;
use pocketmine\entity\Entity;
use pocketmine\entity\Mob;

class MateBehavior extends Behavior{
	/** @var float */
	protected $speedMultiplier;
	/** @var int */
	protected $spawnBabyDelay = 0;
	/** @var Animal */
	protected $targetMate;

	public function __construct(Animal $mob, float $speedMultiplier){
		parent::__construct($mob);

		$this->speedMultiplier = $speedMultiplier;
		$this->mutexBits = 3;
	}

	public function canStart() : bool{
        if($this->mob->hasReproduceCooldown()){
            return false;
        }
		if($this->mob->isInLove()){
			$mate = $this->getNearbyMate();
			if($mate !== null){
				$this->targetMate = $mate;
				return true;
			}
		}

		return false;
	}

	public function canContinue() : bool{
		return $this->targetMate->isAlive() and $this->targetMate->isInLove() and $this->spawnBabyDelay < 60;
	}

	public function onTick() : void{
		$this->mob->getLookHelper()->setLookPositionWithEntity($this->targetMate, 10, $this->mob->getVerticalFaceSpeed());
		$this->mob->getNavigator()->tryMoveTo($this->targetMate, $this->speedMultiplier);

		$this->spawnBabyDelay++;

		if($this->spawnBabyDelay === 60 and $this->mob->distance($this->targetMate) < 9){
			$this->spawnBaby();
		}
	}

	public function onEnd() : void{
		$this->targetMate = null;
		$this->spawnBabyDelay = 0;
	}

	public function getNearbyMate() : ?Animal{
		$list = $this->mob->level->getEntities();
		$dist = 8;
		$animal = null;

		foreach($list as $entity){
			if($entity !== $this->mob and $entity instanceof Animal and $entity->isInLove() and !$entity->isBaby() and $entity->distance($this->mob) < $dist and $entity::NETWORK_ID === $this->mob::NETWORK_ID and !$entity->hasReproduceCooldown()){
				$dist = $entity->distance($this->mob);
				$animal = $entity;
			}
		}

		return $animal;
	}

	private function spawnBaby() : void{
		if($this->mob->isInLove()){
			/** @var Mob $baby */
			$baby = Entity::createEntity($this->mob::NETWORK_ID, $this->mob->level, Entity::createBaseNBT($this->mob));
			$baby->setBaby(true);
			$baby->setImmobile(false);
			$baby->spawnToAll();

			$this->targetMate->setInLove(false);
            $this->targetMate->addReproduceCooldown();
			$this->mob->setInLove(false);
            $this->mob->addReproduceCooldown();
		}
	}
}