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

use pocketmine\entity\Mob;

class FloatBehavior extends Behavior{

  private int $tick = 0;

	public function __construct(Mob $mob){
		parent::__construct($mob);
		$this->mutexBits = 4;
	}

	public function canStart() : bool{
		return $this->mob->isInsideOfWater();
	}

	public function onStart() : void{
		$this->mob->setSwimmer(true);
	}

	public function onEnd() : void{
		$this->mob->setSwimmer(false);
	}

	public function onTick() : void{
		if($this->tick++ % 10 === 0){
			$this->mob->getJumpHelper()->setJumping(true);
		}
	}
}