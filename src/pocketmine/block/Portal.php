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

class Portal extends Transparent{

	protected $id = self::PORTAL;

	/** @var Vector3 */
	private $temporalVector = null;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
		if($this->temporalVector === null){
			$this->temporalVector = new Vector3(0, 0, 0);
		}
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Portal";
	}

	/**
	 * @return float
	 */
	public function getHardness() : float{
		return -1;
	}

	/**
	 * @return float
	 */
	public function getResistance() : float{
		return 0;
	}

	/**
	 * @return int
	 */
	public function getToolType() : int{
		return BlockToolType::TYPE_PICKAXE;
	}

	/**
	 * @return bool
	 */
	public function canPassThrough() : bool{
		return true;
	}

	/**
	 * @return bool
	 */
	public function hasEntityCollision() : bool{
		return true;
	}

	/**
	 * @param Item $item
	 * @param Player|null $player
	 * @return bool
	 */
	public function onBreak(Item $item, Player $player = null) : bool{
		$block = $this;
		if($this->getLevelNonNull()->getBlock($this->temporalVector->setComponents($block->x - 1, $block->y, $block->z))->getId() === BlockIds::PORTAL or
			$this->getLevelNonNull()->getBlock($this->temporalVector->setComponents($block->x + 1, $block->y, $block->z))->getId() === BlockIds::PORTAL
		){//x方向
			for($x = $block->x; $this->getLevelNonNull()->getBlock($this->temporalVector->setComponents($x, $block->y, $block->z))->getId() === BlockIds::PORTAL; $x++){
				for($y = $block->y; $this->getLevelNonNull()->getBlock($this->temporalVector->setComponents($x, $y, $block->z))->getId() === BlockIds::PORTAL; $y++){
					$this->getLevelNonNull()->setBlock($this->temporalVector->setComponents($x, $y, $block->z), new Air());
				}
				for($y = $block->y - 1; $this->getLevelNonNull()->getBlock($this->temporalVector->setComponents($x, $y, $block->z))->getId() === BlockIds::PORTAL; $y--){
					$this->getLevelNonNull()->setBlock($this->temporalVector->setComponents($x, $y, $block->z), new Air());
				}
			}
			for($x = $block->x - 1; $this->getLevelNonNull()->getBlock($this->temporalVector->setComponents($x, $block->y, $block->z))->getId() === BlockIds::PORTAL; $x--){
				for($y = $block->y; $this->getLevelNonNull()->getBlock($this->temporalVector->setComponents($x, $y, $block->z))->getId() === BlockIds::PORTAL; $y++){
					$this->getLevelNonNull()->setBlock($this->temporalVector->setComponents($x, $y, $block->z), new Air());
				}
				for($y = $block->y - 1; $this->getLevelNonNull()->getBlock($this->temporalVector->setComponents($x, $y, $block->z))->getId() === BlockIds::PORTAL; $y--){
					$this->getLevelNonNull()->setBlock($this->temporalVector->setComponents($x, $y, $block->z), new Air());
				}
			}
		}else{//z方向
			for($z = $block->z; $this->getLevelNonNull()->getBlock($this->temporalVector->setComponents($block->x, $block->y, $z))->getId() === BlockIds::PORTAL; $z++){
				for($y = $block->y; $this->getLevelNonNull()->getBlock($this->temporalVector->setComponents($block->x, $y, $z))->getId() === BlockIds::PORTAL; $y++){
					$this->getLevelNonNull()->setBlock($this->temporalVector->setComponents($block->x, $y, $z), new Air());
				}
				for($y = $block->y - 1; $this->getLevelNonNull()->getBlock($this->temporalVector->setComponents($block->x, $y, $z))->getId() === BlockIds::PORTAL; $y--){
					$this->getLevelNonNull()->setBlock($this->temporalVector->setComponents($block->x, $y, $z), new Air());
				}
			}
			for($z = $block->z - 1; $this->getLevelNonNull()->getBlock($this->temporalVector->setComponents($block->x, $block->y, $z))->getId() === BlockIds::PORTAL; $z--){
				for($y = $block->y; $this->getLeveNonNulll()->getBlock($this->temporalVector->setComponents($block->x, $y, $z))->getId() == BlockIds::PORTAL; $y++){
					$this->getLevelNonNull()->setBlock($this->temporalVector->setComponents($block->x, $y, $z), new Air());
				}
				for($y = $block->y - 1; $this->getLevelNonNull()->getBlock($this->temporalVector->setComponents($block->x, $y, $z))->getId() === BlockIds::PORTAL; $y--){
					$this->getLevelNonNull()->setBlock($this->temporalVector->setComponents($block->x, $y, $z), new Air());
				}
			}
		}
		return parent::onBreak($item, $player);
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		if($player instanceof Player){
			$this->meta = $player->getDirection() & 0x01;
		}
		$this->getLevelNonNull()->setBlock($blockReplace, $this, true, true);

		return true;
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		return [];
	}
}