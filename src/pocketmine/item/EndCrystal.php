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

namespace pocketmine\item;

use pocketmine\block\Air;
use pocketmine\block\Bedrock;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\object\EnderCrystal;
use pocketmine\level\Location;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;
use function count;

class EndCrystal extends Item{
	public function __construct(int $meta = 0){
		parent::__construct(self::END_CRYSTAL, $meta, "End Crystal");
	}

	public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector) : bool{
		if($blockClicked->getId() === self::OBSIDIAN || $blockClicked instanceof Bedrock){
			$level = $blockClicked->getLevelNonNull();
			$entities = $level->getNearbyEntities(new AxisAlignedBB($blockClicked->getX(), $blockClicked->getY(), $blockClicked->getZ(), $blockClicked->getX() + 1, $blockClicked->getY() + 2, $blockClicked->getZ() + 1));
			if(count($entities) === 0 && $level->getBlock($blockClicked->up()) instanceof Air && $level->getBlock($blockClicked->up(2)) instanceof Air){
			    $nbt = Entity::createBaseNBT(Location::fromObject($blockClicked->add(0.5, 1.5, 0.5)));

				$crystal = new EnderCrystal($level, $nbt);
				$crystal->spawnToAll();

				$this->pop();

				return true;
			}
		}

		return false;
	}
}
