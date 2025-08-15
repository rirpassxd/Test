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

use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\item\Item;
use pocketmine\level\sound\GenericSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;
use function array_filter;
use function count;

class PressurePlate extends RedstoneSource{
	/** @var int */
	protected $activateTime = 0;
	/** @var bool */
	protected $canActivate = true;

	public function hasEntityCollision() : bool{
		return true;
	}

	public function onEntityCollide(Entity $entity) : void{
		if($this->getLevelNonNull()->getServer()->redstoneEnabled and $this->canActivate){
			$entities = $this->getLevelNonNull()->getNearbyEntities($this->getHitCollision());
			$entities = array_filter($entities, fn($entity) => $entity instanceof Living && !($entity instanceof Player && $entity->isSpectator()));
			if(count($entities) === 0){
				$this->getLevelNonNull()->scheduleDelayedBlockUpdate($this, 20);
				return;
			}

			if(!$this->isActivated()){
				$this->meta = 1;
				$this->getLevelNonNull()->setBlock($this, $this, true, false);
				$this->getLevelNonNull()->addSound(new GenericSound($this, 1000));
				$this->activate();
			}

			$this->getLevelNonNull()->scheduleDelayedBlockUpdate($this, 20);
		}
	}

	public function isActivated(Block $from = null) : bool{
		return ($this->meta === 0) ? false : true;
	}

	public function onNearbyBlockChange() : void{
		$below = $this->getSide(Vector3::SIDE_DOWN);
		if($below instanceof Transparent){
			$this->getLevelNonNull()->useBreakOn($this);
		}
	}

	public function onScheduledUpdate() : void{
		$this->checkActivation();
	}

	public function checkActivation() : void{
		if(!$this->isActivated()){
			return;
		}

        $entities = $this->getLevelNonNull()->getNearbyEntities($this->getHitCollision());
        $entities = array_filter($entities, fn($entity) => $entity instanceof Living && !($entity instanceof Player && $entity->isSpectator()));
        if(count($entities) !== 0){
			$this->getLevelNonNull()->scheduleDelayedBlockUpdate($this, 20);
			return;
		}

		if((($this->getLevelNonNull()->getServer()->getTick() - $this->activateTime)) >= 3){
			$this->meta = 0;
			$this->getLevelNonNull()->setBlock($this, $this, true, false);
			$this->getLevelNonNull()->addSound(new GenericSound($this, 1000));
			$this->deactivate();
		}
	}


    protected function getHitCollision() : AxisAlignedBB{
        return new AxisAlignedBB(
            $this->getX() + 0.0625,
            $this->getY(),
            $this->getZ() + 0.0625,
            $this->getX() + 0.9375,
            $this->getY() + 0.0625,
            $this->getZ() + 0.9375
        );
    }

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		$below = $this->getSide(Vector3::SIDE_DOWN);
		if($below instanceof Transparent){
			return false;
		}else{
		    $this->getLevelNonNull()->setBlock($this, $this, true, true);
			return true;
		}
	}

	public function onBreak(Item $item, Player $player = null) : bool{
		if($this->isActivated()){
			$this->meta = 0;
			$this->deactivate();
		}
		$this->canActivate = false;

		$this->getLevelNonNull()->setBlock($this, new Air(), true, true);

		return true;
	}
}
