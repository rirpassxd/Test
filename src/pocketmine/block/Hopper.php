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
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\Hopper as TileHopper;
use pocketmine\tile\Tile;

class Hopper extends Transparent{
	protected $id = self::HOPPER_BLOCK;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getHardness() : float{
		return 3;
	}

	public function getName() : string{
		return "Hopper";
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_PICKAXE;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [
			ItemFactory::get(Item::HOPPER, 0, 1)
		];
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		$faces = [
			0 => 0,
			1 => 0,
			2 => 3,
			3 => 2,
			4 => 5,
			5 => 4
		];
		$this->meta = $faces[$face];
		$this->getLevelNonNull()->setBlock($blockReplace, $this, true, true);

		Tile::createTile(Tile::HOPPER, $this->getLevelNonNull(), TileHopper::createNBT($this, $face, $item, $player));

		return true;
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		if($player instanceof Player){
			$hopper = $this->getLevelNonNull()->getTile($this);
			if(!($hopper instanceof TileHopper)){
				$hopper = Tile::createTile(Tile::HOPPER, $this->getLevelNonNull(), TileHopper::createNBT($this));
				if(!($hopper instanceof TileHopper)){
					return true;
				}
			}

			if(!$hopper->canOpenWith($item->getCustomName())){
				return true;
			}

			$player->addWindow($hopper->getInventory());
		}

		return true;
	}

	public function getVariantBitmask() : int{
		return 0;
	}
}
