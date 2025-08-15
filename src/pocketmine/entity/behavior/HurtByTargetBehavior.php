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

use pocketmine\entity\Living;
use pocketmine\entity\Mob;

class HurtByTargetBehavior extends TargetBehavior{

	protected $alertSameType;
	protected $hurtOwner;
	protected $revengeTimerOld = 0;

	public function __construct(Mob $mob, bool $alertSameType = false, bool $hurtOwner = false){
		parent::__construct($mob, false);

		$this->alertSameType = $alertSameType;
		$this->hurtOwner = $hurtOwner;
	}

	public function canStart() : bool{
		$attacker = $this->mob->getRevengeTarget();
		$i = $this->mob->getRevengeTimer();

		return $i !== $this->revengeTimerOld and $attacker instanceof Living and !($this->hurtOwner and $this->mob->getOwningEntity() === $attacker) and $this->isSuitableTargetLocal($attacker, false);
	}

	public function onStart() : void{
		$this->mob->setTargetEntity($this->mob->getRevengeTarget());
		$this->revengeTimerOld = $this->mob->getRevengeTimer();

		if($this->alertSameType){
			$d = $this->getTargetDistance();

			foreach($this->mob->level->getNearbyEntities($this->mob->getBoundingBox()->expandedCopy($d, 10, $d), $this->mob) as $entity){
				if($entity->getTargetEntity() === null){
					$entity->setTargetEntity($this->mob->getRevengeTarget());
				}
			}
		}

		parent::onStart();
	}
}