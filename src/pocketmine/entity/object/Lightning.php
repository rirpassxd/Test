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

declare(strict_types = 1);

namespace pocketmine\entity\object;

use pocketmine\block\Liquid;
use pocketmine\entity\{Entity, Living};
use pocketmine\entity\hostile\Creeper;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;

class Lightning extends Entity{
	public const NETWORK_ID = self::LIGHTNING_BOLT;

	/** @var float */
	public $width = 0.3;
	/** @var float */
	public $height = 1.8;

	/** @var int */
	protected $age = 0;

	/** @var bool */
	public $doneDamage = false;

	public function onUpdate(int $currentTick): bool{
		if(!$this->doneDamage){
			$this->doneDamage = true;
			if($this->getLevelNonNull()->getServer()->lightningFire){
				$fire = Item::get(Item::FIRE)->getBlock();
				$oldBlock = $this->getLevelNonNull()->getBlock($this);
				if($oldBlock instanceof Liquid){

				}elseif($oldBlock->isSolid()){
					$v3 = new Vector3($this->x, $this->y + 1, $this->z);
				}else{
					$v3 = new Vector3($this->x, $this->y, $this->z);
				}

				$fire->setDamage(11); // Only one random tick away till a chance of despawn ;)

				if(isset($v3)) $this->getLevelNonNull()->setBlock($v3, $fire);

				foreach($this->level->getNearbyEntities($this->growAxis($this->boundingBox, 6, 6, 6), $this) as $entity){
					if($entity instanceof Living){
						$distance = $this->distance($entity);

						$distance = ($distance > 0 ? $distance : 1);

						$k = 5;
						$damage = $k / $distance;

						$ev = new EntityDamageByEntityEvent($this, $entity, 16, $damage); // LIGHTNING
						$entity->attack($ev);
						$entity->setOnFire(mt_rand(3, 8));
					}

					if($entity instanceof Creeper){
						$entity->setPowered(true);
					}
				}
			}
			$spk = new PlaySoundPacket();
			$spk->soundName = "ambient.weather.lightning.impact";
			$spk->x = $this->getX();
			$spk->y = $this->getY();
			$spk->z = $this->getZ();
			$spk->volume = 500;
			$spk->pitch = 1;

			foreach($this->level->getPlayers() as $p){
				$p->sendDataPacket(clone $spk);
			}
		}
		if($this->age > 6 * 20){
			$this->flagForDespawn();
		}
		$this->age++;

		return parent::onUpdate($currentTick);
	}

	private function growAxis(AxisAlignedBB $axis, $x, $y, $z){
		return new AxisAlignedBB($axis->minX - $x, $axis->minY - $y, $axis->minZ - $z, $axis->maxX + $x, $axis->maxY + $y, $axis->maxZ + $z);
	}
}
