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
use pocketmine\math\Vector3;
use pocketmine\Player;

class PoweredRepeater extends RedstoneSource{
	protected $id = self::POWERED_REPEATER;

	public const ACTION_ACTIVATE = "Repeater Activate";
	public const ACTION_DEACTIVATE = "Repeater Deactivate";

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Powered Repeater";
	}

	public function getStrength() : int{
		return 15;
	}

	public function getDirection() : int{
		$direction = 0;
		switch($this->meta % 4){
			case 0:
				$direction = 3;
				break;
			case 1:
				$direction = 4;
				break;
			case 2:
				$direction = 2;
				break;
			case 3:
				$direction = 5;
				break;
		}
		return $direction;
	}

	public function getOppositeDirection() : int{
		return static::getOppositeSide($this->getDirection());
	}

	public function getDelayLevel() : float{
		return round(($this->meta - ($this->meta % 4)) / 4) + 1;
	}

	public function isActivated(Block $from = null) : bool{
		if(!$from instanceof Block){
			return false;
		}else{
			if($this->y !== $from->y){
				return false;
			}
			if($from->equals($this->getSide($this->getOppositeDirection()))){
				return true;
			}
			return false;
		}
	}

	public function activate(array $ignore = []){
		if($this->canCalc()){
			if($this->id !== self::POWERED_REPEATER){
				$this->id = self::POWERED_REPEATER;
				$this->getLevelNonNull()->setBlock($this, $this, true, true);
			}
			$this->getLevelNonNull()->setBlockTempData($this, self::ACTION_ACTIVATE);
			$this->getLevelNonNull()->scheduleDelayedBlockUpdate($this, (int) $this->getDelayLevel() * 2);
		}
	}

	public function deactivate(array $ignore = []){
		if($this->canCalc()){
			if($this->id !== self::UNPOWERED_REPEATER){
				$this->id = self::UNPOWERED_REPEATER;
				$this->getLevelNonNull()->setBlock($this, $this, true, true);
			}
			$this->getLevelNonNull()->setBlockTempData($this, self::ACTION_DEACTIVATE);
			$this->getLevelNonNull()->scheduleDelayedBlockUpdate($this, (int) $this->getDelayLevel() * 2);
		}
	}

	public function deactivateImmediately() : void{
		$this->deactivateBlock($this->getSide($this->getOppositeDirection()));
		$this->deactivateBlock($this->getSide(Vector3::SIDE_DOWN, 2));//TODO: improve
	}

	public function onScheduledUpdate() : void{
		if($this->getLevelNonNull()->getBlockTempData($this) === self::ACTION_ACTIVATE){
			$this->activateBlock($this->getSide($this->getOppositeDirection()));
			$this->activateBlock($this->getSide(Vector3::SIDE_DOWN, 2));
		}elseif($this->getLevelNonNull()->getBlockTempData($this) === self::ACTION_DEACTIVATE){
			$this->deactivateImmediately();
		}
		$this->getLevelNonNull()->setBlockTempData($this);
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		$meta = $this->meta + 4;
		if($meta > 15){
			$this->meta = $this->meta % 4;
		}else{
		    $this->meta = $meta;
		}
		$this->getLevelNonNull()->setBlock($this, $this, true, true);

		return true;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		if($player instanceof Player){
			$this->meta = ((int) $player->getDirection() + 5) % 4;
		}

		$this->getLevelNonNull()->setBlock($this, $this, true, true);
		if($this->checkPower($this)){
			$this->activate();
		}

		return true;
	}

	public function onBreak(Item $item, Player $player = null) : bool{
		$this->deactivateImmediately();
		$this->getLevelNonNull()->setBlock($this, new Air(), true, true);
		$this->getLevelNonNull()->setBlockTempData($this);

		return true;
	}

	public function getDrops(Item $item) : array{
		return [
			Item::get(Item::REPEATER, 0, 1)
		];
	}
}
