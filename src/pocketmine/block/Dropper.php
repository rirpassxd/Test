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
use pocketmine\tile\Dropper as TileDropper;
use pocketmine\tile\Tile;
use function abs;

class Dropper extends Solid implements ElectricalAppliance{
	protected $id = self::DROPPER;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getHardness() : float{
		return 3.5;
	}

	public function getName() : string{
		return "Dropper";
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_PICKAXE;;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		if($player instanceof Player){
			$pitch = $player->getPitch();
			if(abs($pitch) >= 45){
				if($pitch < 0){
					$f = 4;
				}else{
					$f = 5;
				}
			}else{
				$f = $player->getDirection();
			}
		}else{
			$f = 0;
		}

		$faces = [
			3 => 3,
			0 => 4,
			2 => 5,
			1 => 2,
			4 => 0,
			5 => 1
		];
		$this->meta = $faces[$f];

		$this->getLevelNonNull()->setBlock($blockReplace, $this, true, true);
		Tile::createTile(Tile::DROPPER, $this->getLevelNonNull(), TileDropper::createNBT($this, $face, $item, $player));

		return true;
	}

	public function activate(array $ignore = []){
		$tile = $this->getLevelNonNull()->getTile($this);
		if($tile instanceof TileDropper){
			$tile->activate();
		}
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		if($player instanceof Player){

			$t = $this->getLevelNonNull()->getTile($this);
			$dropper = null;
			if($t instanceof TileDropper){
				$dropper = $t;
			}else{
				$dropper = Tile::createTile(Tile::DROPPER, $this->getLevelNonNull(), TileDropper::createNBT($this, null));
				if(!($dropper instanceof TileDropper)){
					return true;
				}
			}

			$player->addWindow($dropper->getInventory());
		}

		return true;
	}
}
