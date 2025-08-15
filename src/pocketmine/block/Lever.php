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

class Lever extends RedstoneSource{

	protected $id = self::LEVER;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Lever";
	}

	public function getHardness() : float{
		return 0.5;
	}

	public function getVariantBitmask() : int{
		return 0;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		if(!$blockClicked->isSolid()){
			return false;
		}

		if($face === Vector3::SIDE_DOWN){
			$this->meta = 0;
		}else{
			$this->meta = 6 - $face;
		}

		if($player !== null){
			if(($player->getDirection() & 0x01) === 0){
				if($face === Vector3::SIDE_UP){
					$this->meta = 6;
				}
			}else{
				if($face === Vector3::SIDE_DOWN){
					$this->meta = 7;
				}
			}
		}

		$this->level->setBlock($blockReplace, $this, true, true);

		return true;
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		$this->meta ^= 0x08;
		$this->getLevelNonNull()->setBlock($this, $this, true, false);
		$this->getLevelNonNull()->addSound(new ButtonClickSound($this));
		if($this->isActivated()){
			$this->activate();
		}else{
			$this->deactivate();
		}

		return true;
	}

	public function onNearbyBlockChange() : void{
		$faces = [
			0 => Vector3::SIDE_UP,
			1 => Vector3::SIDE_WEST,
			2 => Vector3::SIDE_EAST,
			3 => Vector3::SIDE_NORTH,
			4 => Vector3::SIDE_SOUTH,
			5 => Vector3::SIDE_DOWN,
			6 => Vector3::SIDE_DOWN,
			7 => Vector3::SIDE_UP
		];

		$side = $this->meta;
		if($this->isActivated()){
			$side ^= 0x08;
		}

		if(!$this->getSide($faces[$side])->isSolid()){
			$this->level->useBreakOn($this);
		}
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

	public function activate(array $ignore = []){
		parent::activate($ignore);

		$side = $this->meta;
		if($this->isActivated()){
			$side ^= 0x08;
		}

		$faces = [
			0 => Vector3::SIDE_UP,
			1 => Vector3::SIDE_WEST,
			2 => Vector3::SIDE_EAST,
			3 => Vector3::SIDE_NORTH,
			4 => Vector3::SIDE_SOUTH,
			5 => Vector3::SIDE_DOWN,
			6 => Vector3::SIDE_DOWN,
			7 => Vector3::SIDE_UP
		];

		$block = $this->getSide($faces[$side])->getSide(Vector3::SIDE_UP);
		if(!$this->equals($block)){
			$this->activateBlock($block);
		}

		$this->checkTorchOn($this->getSide($faces[$side]), [static::getOppositeSide($faces[$side])]);
	}

	public function deactivate(array $ignore = []){
		parent::deactivate($ignore);

		$side = $this->meta;
		if($this->isActivated()){
			$side ^= 0x08;
		}

		$faces = [
			0 => Vector3::SIDE_UP,
			1 => Vector3::SIDE_WEST,
			2 => Vector3::SIDE_EAST,
			3 => Vector3::SIDE_NORTH,
			4 => Vector3::SIDE_SOUTH,
			5 => Vector3::SIDE_DOWN,
			6 => Vector3::SIDE_DOWN,
			7 => Vector3::SIDE_UP
		];

		$block = $this->getSide($faces[$side])->getSide(Vector3::SIDE_UP);
		if(!$this->equals($block)){
			$this->deactivateBlock($block);
		}

		$this->checkTorchOff($this->getSide($faces[$side]), [static::getOppositeSide($faces[$side])]);
	}

	public function isActivated(Block $from = null) : bool{
		return (($this->meta & 0x08) === 0x08);
	}
}
