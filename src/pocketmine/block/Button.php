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

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\level\sound\ButtonClickSound;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class Button extends RedstoneSource{

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getVariantBitmask() : int{
		return 0;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		if($blockClicked->isTransparent() === false){
			$this->meta = $face;
			$this->getLevelNonNull()->setBlock($this, $this, true, true);
			return true;
		}

		return false;
	}

	public function onBreak(Item $item, Player $player = null) : bool{
		if($this->isActivated()){
			$this->meta ^= 0x08;
			$this->getLevelNonNull()->setBlock($this, $this, true, false);
			$this->deactivate();
		}
		$this->getLevelNonNull()->setBlock($this, new Air(), true, false);

		return true;
	}

	public function isActivated(Block $from = null) : bool{
		return (($this->meta & 0x08) === 0x08);
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		if(!$this->isActivated()){
			$this->meta ^= 0x08;
			$this->getLevelNonNull()->setBlock($this, $this, true, false);
			$this->getLevelNonNull()->addSound(new ButtonClickSound($this));
			$this->activate();
			$this->getLevelNonNull()->scheduleDelayedBlockUpdate($this, 30);
		}
		return true;
	}

	public function onScheduledUpdate() : void{
		if($this->isActivated()){
			$this->meta ^= 0x08;
			$this->getLevelNonNull()->setBlock($this, $this, true, true);
			$this->getLevelNonNull()->addSound(new ButtonClickSound($this));
			$this->deactivate();
		}
	}

	public function onNearbyBlockChange() : void{
		$side = $this->getDamage();
		if($this->isActivated()){
			$side ^= 0x08;
		}

		$faces = [
			0 => 1,
			1 => 0,
			2 => 3,
			3 => 2,
			4 => 5,
			5 => 4,
		];

		if($this->getSide($faces[$side]) instanceof Transparent){
			$this->getLevelNonNull()->useBreakOn($this);
		}
	}
}
