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
use pocketmine\item\TieredTool;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Obsidian extends Solid{

	protected $id = self::OBSIDIAN;

	/** @var Vector3 */
	private $temporalVector = null;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
		if($this->temporalVector === null){
			$this->temporalVector = new Vector3(0, 0, 0);
		}
	}

	public function getName() : string{
		return "Obsidian";
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_PICKAXE;
	}

	public function getToolHarvestLevel() : int{
		return TieredTool::TIER_DIAMOND;
	}

	public function getHardness() : float{
		return 35; //50 in PC
	}

	public function getBlastResistance() : float{
		return 6000;
	}

	/**
	 * @param Item        $item
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function onBreak(Item $item, Player $player = null) : bool{
		parent::onBreak($item, $player);

		if($this->getLevelNonNull()->getServer()->netherEnabled){
			for($i = 0; $i <= 6; $i++){
				if($this->getSide($i)->getId() === BlockIds::PORTAL){
					break;
				}
				if($i === 6){
					return true;
				}
			}
			$block = $this->getSide($i);
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
					for($y = $block->y; $this->getLevelNonNull()->getBlock($this->temporalVector->setComponents($block->x, $y, $z))->getId() === BlockIds::PORTAL; $y++){
						$this->getLevelNonNull()->setBlock($this->temporalVector->setComponents($block->x, $y, $z), new Air());
					}
					for($y = $block->y - 1; $this->getLevelNonNull()->getBlock($this->temporalVector->setComponents($block->x, $y, $z))->getId() === BlockIds::PORTAL; $y--){
						$this->getLevelNonNull()->setBlock($this->temporalVector->setComponents($block->x, $y, $z), new Air());
					}
				}
			}
		}
		return true;
	}
}
