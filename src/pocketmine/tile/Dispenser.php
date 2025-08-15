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

namespace pocketmine\tile;

use pocketmine\entity\Entity;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Egg;
use pocketmine\entity\projectile\ExperienceBottle;
use pocketmine\entity\projectile\Snowball;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\inventory\DispenserInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\particle\SmokeParticle;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use function count;
use function is_array;
use function lcg_value;
use function mt_rand;

class Dispenser extends Spawnable implements InventoryHolder, Container, Nameable{
	use NameableTrait {
		addAdditionalSpawnData as addNameSpawnData;
	}
	use ContainerTrait;

	/** @var DispenserInventory */
	protected $inventory;

	protected function readSaveData(CompoundTag $nbt) : void{
		$this->loadName($nbt);

		$this->inventory = new DispenserInventory($this);
		$this->loadItems($nbt);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$this->saveName($nbt);
		$this->saveItems($nbt);
	}

	public function getDefaultName() : string{
		return "Dispenser";
	}

	public function close() : void{
		if(!$this->closed){
			$this->inventory->removeAllViewers(true);
			$this->inventory = null;

			parent::close();
		}
	}

	public function getInventory(){
		return $this->inventory;
	}

	public function getRealInventory(){
		return $this->getInventory();
	}

	public function getMotion() : array{
		$meta = $this->getBlock()->getDamage();
		switch($meta){
			case Vector3::SIDE_DOWN:
				return [0, -1, 0];
			case Vector3::SIDE_UP:
				return [0, 1, 0];
			case Vector3::SIDE_NORTH:
				return [0, 0, -1];
			case Vector3::SIDE_SOUTH:
				return [0, 0, 1];
			case Vector3::SIDE_WEST:
				return [-1, 0, 0];
			case Vector3::SIDE_EAST:
				return [1, 0, 0];
			default:
				return [0, 0, 0];
		}
	}

	public function activate() : void{
		$itemIndex = [];
		for($i = 0; $i < $this->getInventory()->getSize(); $i++){
			$item = $this->getInventory()->getItem($i);
			if($item->getId() !== Item::AIR){
				$itemIndex[] = [$i, $item];
			}
		}

		$max = count($itemIndex) - 1;
		if($max < 0){
			$itemArr = null;
		}elseif($max === 0){
			$itemArr = $itemIndex[0];
		}else{
			$itemArr = $itemIndex[mt_rand(0, $max)];
		}

		if(is_array($itemArr)){
			/** @var Item $item */
			$item = $itemArr[1];
			$item->pop();

			$this->getInventory()->setItem($itemArr[0], $item->getCount() > 0 ? $item : Item::get(ItemIds::AIR));

			$motion = $this->getMotion();
			$needItem = clone $item;
			$needItem->setCount(1);

			$f = 1.5;
			$nbt = Entity::createBaseNBT(
				new Vector3(
					$this->x + $motion[0] * 2 + 0.5,
					$this->y + ($motion[1] > 0 ? $motion[1] : 0.5),
					$this->z + $motion[2] * 2 + 0.5
				),
				new Vector3(
					$motion[0],
					$motion[1],
					$motion[2],
				),
				lcg_value() * 360
			);

			switch($needItem->getId()){
				case ItemIds::ARROW:
					$entity = new Arrow($this->getLevelNonNull(), $nbt);
					break;
				case ItemIds::SNOWBALL:
					$entity = new Snowball($this->getLevelNonNull(), $nbt);
					break;
				case ItemIds::EGG:
					$entity = new Egg($this->getLevelNonNull(), $nbt);
					break;
				case ItemIds::SPLASH_POTION:
					$nbt->setShort("PotionId", $item->getDamage());

					$entity = new SplashPotion($this->getLevelNonNull(), $nbt);
					break;
				case ItemIds::EXPERIENCE_BOTTLE:
					$entity = new ExperienceBottle($this->getLevelNonNull(), $nbt);
					break;
				default:
				    $f = 0.3;

					$nbt->setShort("Health", 5);
					$nbt->setTag($needItem->nbtSerialize(-1, "Item"));
					$nbt->setShort("PickupDelay", 10);

					$entity = new ItemEntity($this->getLevelNonNull(), $nbt);
					break;
			}

			$entity->setMotion($entity->getMotion()->multiply($f));
			$entity->spawnToAll();

			for($i = 1; $i < 10; $i++){
				$this->getLevelNonNull()->addParticle(new SmokeParticle($this->add($motion[0] * $i * 0.3 + 0.5, $motion[1] === 0 ? 0.5 : $motion[1] * $i * 0.3, $motion[2] * $i * 0.3 + 0.5)));
			}
		}
	}
}