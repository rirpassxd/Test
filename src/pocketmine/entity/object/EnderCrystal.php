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

namespace pocketmine\entity\object;

use pocketmine\entity\Entity;
use pocketmine\entity\Explosive;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\level\Explosion;
use pocketmine\level\Position;

class EnderCrystal extends Entity implements Explosive{
    public const NETWORK_ID = self::ENDER_CRYSTAL;

	public const TAG_SHOWBASE = "ShowBottom"; //TAG_Byte

	public $gravity = 0.0;
	public $drag = 1.0;

	public $height = 2.0;
	public $width = 2.0;

	public function showBase() : bool{
		return $this->getGenericFlag(self::DATA_FLAG_SHOWBASE);
	}

	public function setShowBase(bool $showBase) : void{
		$this->setGenericFlag(self::DATA_FLAG_SHOWBASE, $showBase);
	}

	public function attack(EntityDamageEvent $source) : void{
		if(
			$source->getCause() !== EntityDamageEvent::CAUSE_FIRE &&
			$source->getCause() !== EntityDamageEvent::CAUSE_FIRE_TICK &&
		    $source->getCause() !== EntityDamageEvent::CAUSE_LAVA
		){
			parent::attack($source);
			if(!$this->isFlaggedForDespawn() && !$source->isCancelled()){
				$this->flagForDespawn();
				$this->explode();
			}
		}
	}

	public function isFireProof() : bool{
		return true;
	}

	public function hasMovementUpdate() : bool{
		return false;
	}

	protected function updateMovement(bool $teleport = false) : void{

	}

	public function canBeMovedByCurrents() : bool{
		return false;
	}

	public function canBeCollidedWith() : bool{
		return true;
	}

	/**
	 * @param int  $propertyId
	 * @param int  $flagId
	 * @param bool $value
	 * @param int  $propertyType
	 */
	public function setDataFlag(int $propertyId, int $flagId, bool $value = true, int $propertyType = self::DATA_TYPE_LONG) : void{
	    if($flagId === self::DATA_FLAG_ONFIRE){
	        return; //TODO: Hack to prevent fire animation
	    }
	    parent::setDataFlag($propertyId, $flagId, $value, $propertyType);
	}

	/**
	 * Wrapper around {@link Entity#setDataFlag} for generic data flag setting.
	 *
	 * @param int  $flagId
	 * @param bool $value
	 */
	public function setGenericFlag(int $flagId, bool $value = true) : void{
	    if($flagId === self::DATA_FLAG_ONFIRE){
	        return; //TODO: Hack to prevent fire animation
	    }
	    parent::setGenericFlag($flagId, $value);
	}

	protected function initEntity() : void{
		parent::initEntity();

		$this->setMaxHealth(1);
		$this->setHealth(1);

		$this->setShowBase($this->namedtag->getByte(self::TAG_SHOWBASE, 0) === 1);
	}

	public function saveNBT() : void{
		parent::saveNBT();

		$this->namedtag->setByte(self::TAG_SHOWBASE, $this->showBase() ? 1 : 0);
	}

	public function explode() : void{
		$ev = new ExplosionPrimeEvent($this, 6);
		$ev->call();
		if(!$ev->isCancelled()){
			$explosion = new Explosion(Position::fromObject($this->add(0, 0.5, 0), $this->getLevelNonNull()), $ev->getForce(), $this);
			if($ev->isBlockBreaking()){
				$explosion->explodeA();
			}
			$explosion->explodeB();
		}
	}
}