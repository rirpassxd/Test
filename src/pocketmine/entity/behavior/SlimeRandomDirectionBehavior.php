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

class SlimeRandomDirectionBehavior extends Behavior{
	/** @var Slime */
	protected $mob;
	protected $randomYaw = 0;
	protected $directionTimer = 0;

	public function __construct(Slime $slime){
		parent::__construct($slime);

		$this->setMutexBits(2);
	}

	public function canStart() : bool{
		return $this->mob->getTargetEntity() === null and ($this->mob->onGround or $this->mob->isInsideOfWater() or $this->mob->isInsideOfLava());
	}

	public function onTick() : void{
		if(--$this->directionTimer <= 0){
			$this->directionTimer = 40 + $this->random->nextBoundedInt(60);
			$this->randomYaw = $this->random->nextBoundedInt(360);
		}

		$this->mob->getMoveHelper()->jumpWithYaw($this->randomYaw, false);
	}
}