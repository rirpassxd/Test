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

use pocketmine\entity\hostile\Slime;
use pocketmine\Player;

class SlimeAttackBehavior extends Behavior{
	/** @var Slime */
	protected $mob;

	private $attackTime;

	public function __construct(Slime $slime){
		parent::__construct($slime);

		$this->setMutexBits(2);
	}

	public function canStart() : bool{
		$target = $this->mob->getTargetEntity();

		return $target === null ? false : (!$target->isAlive() ? false : !($target instanceof Player) or ($target instanceof Player && !$target->isCreative()));
	}

	public function onStart() : void{
		$this->attackTime = 300;
	}

	public function canContinue() : bool{
		return $this->canStart() and --$this->attackTime > 0;
	}

	public function onTick() : void{
		$this->mob->faceEntity($this->mob->getTargetEntity(), 10, 10);
		$this->mob->getMoveHelper()->jumpWithYaw($this->mob->yaw, $this->mob->canDamagePlayer());
	}
}