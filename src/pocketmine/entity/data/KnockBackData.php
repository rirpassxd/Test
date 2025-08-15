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

namespace pocketmine\entity\data;

class KnockBackData{
    /** @var bool */
	public bool $activeVerticalLimit = false;

	public function __construct(
        private readonly bool $vanilla = true,
		private readonly float $x = 0.4,
		private readonly float $y = 0.4,
		private readonly int $delay = 10,
		private readonly bool $verticalLimitEnabled = true,
		private readonly float $verticalLimit = 2.5,
		private readonly float $vericalLimitMultiplier = 0.85
	){}

    public function isVanilla() : bool{
        return $this->vanilla;
    }

	public function getX() : float{
		return $this->x;
	}

	public function getY() : float{
		return $this->y;
	}

	public function getDelay() : int{
		return $this->delay;
	}

	public function isVerticalLimitEnabled() : bool{
		return $this->verticalLimitEnabled;
	}

	public function getVerticalLimit() : float{
		return $this->verticalLimit;
	}

	public function getVericalLimitMultiplier() : float{
		return $this->vericalLimitMultiplier;
	}

	public function isActiveVerticalLimit() : bool{
		return $this->activeVerticalLimit;
	}

    public function setActiveVerticalLimit(bool $active) : void{
        $this->activeVerticalLimit = $active;
    }
}
