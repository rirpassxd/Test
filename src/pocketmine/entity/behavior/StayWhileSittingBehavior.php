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

use pocketmine\entity\Tamable;

class StayWhileSittingBehavior extends Behavior{

	/** @var Tamable */
	protected $mob;
	protected $isSitting = false;

	public function __construct(Tamable $mob){
		parent::__construct($mob);
		$this->mutexBits = 1;
	}

	public function canStart() : bool{
		if($this->mob->isTamed() and !$this->mob->isInsideOfWater() and !($this->mob->fallDistance > 0)){
			$owner = $this->mob->getOwningEntity();

			return $owner === null ? true : ((($this->mob->distanceSquared($owner) < 144 and ($this->mob->getTargetEntity() !== null and $this->mob->getTargetEntity()->isAlive())) ? false : $this->isSitting));
		}

		return false;
	}

	public function onStart() : void{
		$this->mob->getNavigator()->clearPath(true);
	    $this->mob->setSitting(true);
	}

	public function onEnd() : void{
	    $this->mob->setSitting(false);
	}

	public function setSitting(bool $value) : void{
		$this->isSitting = $value;
	}
}