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

namespace pocketmine\entity\behavior;

use pocketmine\block\Block;
use pocketmine\block\Grass;
use pocketmine\block\TallGrass;
use pocketmine\entity\Animal;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use function max;

class EatBlockBehavior extends Behavior{

	/** @var int */
	protected $duration;
	protected $mutexBits = 7;

	public function canStart() : bool{
		if($this->random->nextBoundedInt(1000) != 0) return false;

		$direction = $this->mob->getDirectionVector()->normalize();
		$coordinates = $this->mob->add($direction->x, 0, $direction->z);

		return $this->mob->level->getBlock($coordinates->down()) instanceof Grass or $this->mob->level->getBlock($coordinates) instanceof TallGrass;
	}

	public function onStart() : void{
		$this->mob->broadcastEntityEvent(ActorEventPacket::EAT_GRASS_ANIMATION);
		$this->duration = 40;
		$this->mob->getNavigator()->clearPath();
	}

	public function canContinue() : bool{
		return $this->duration > 0;
	}

	public function onTick() : void{
		$this->duration = max(0, $this->duration - 1);

		if($this->duration === 4){
			$pos = $this->mob->down();

			if($this->mob->level->getBlock($pos) instanceof Grass){
				$this->mob->level->addParticle(new DestroyBlockParticle($this->mob->floor(), Block::get(Block::GRASS)));
				$this->mob->level->setBlock($pos, Block::get(Block::DIRT));

				if($this->mob instanceof Animal){
					$this->mob->eatGrassBonus($pos);
				}
			}
		}
	}

	public function onEnd() : void{
		$this->duration = 0;
	}
}