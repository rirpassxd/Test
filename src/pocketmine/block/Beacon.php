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
use pocketmine\tile\Beacon as TileBeacon;
use pocketmine\tile\Tile;

class Beacon extends Transparent{

	protected $id = self::BEACON;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Beacon";
	}

	public function getLightLevel() : int{
		return 15;
	}

	public function getHardness() : float{
		return 3;
	}

	public function getBreakTime(Item $item) : float{
		return 4.5;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		$this->getLevelNonNull()->setBlock($blockReplace, $this, true, true);

		Tile::createTile(Tile::BEACON, $this->getLevelNonNull(), TileBeacon::createNBT($this, $face, $item, $player));

		return true;
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		if($player instanceof Player){
			$tile = $this->level->getTile($this);
			if($tile instanceof TileBeacon){
				$top = $this->getSide(Vector3::SIDE_UP);
				if($top->isTransparent() !== true){
					return true;
				}

				$player->addWindow($tile->getInventory(), Player::BEACON_WINDOW_ID);
			}
		}

		return true;
	}
}