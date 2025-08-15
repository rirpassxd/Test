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

use pocketmine\inventory\BrewingInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryEventProcessor;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\mcpe\protocol\ContainerSetDataPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Server;
use function in_array;
use function max;

class BrewingStand extends Spawnable implements InventoryHolder, Container, Nameable{
	use NameableTrait {
		addAdditionalSpawnData as addNameSpawnData;
	}
	use ContainerTrait;

	public const TAG_BREW_TIME = "BrewTime";
	public const TAG_BURN_TIME = "BurnTime";

	public const MAX_BREW_TIME = 400;
	public const MAX_BURN_TIME = 20;

	public const INGREDIENTS = [
		Item::NETHER_WART,
		Item::GLOWSTONE_DUST,
		Item::REDSTONE,
		Item::FERMENTED_SPIDER_EYE,
		Item::MAGMA_CREAM,
		Item::SUGAR,
		Item::GLISTERING_MELON,
		Item::SPIDER_EYE,
		Item::GHAST_TEAR,
		Item::BLAZE_POWDER,
		Item::GOLDEN_CARROT,
		Item::PUFFERFISH,
		Item::RABBIT_FOOT,
		Item::GUNPOWDER,
		Item::DRAGON_BREATH,
	];

	/** @var int */
	private $burnTime;
	/** @var int */
	private $brewTime;
	/** @var BrewingInventory */
	protected $inventory = null;

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		if($this->burnTime > 0){
		    $this->scheduleUpdate();
		}
	}

	protected function readSaveData(CompoundTag $nbt) : void{
		$this->burnTime = max(0, $nbt->getShort(self::TAG_BURN_TIME, 0, true));

		$this->brewTime = $nbt->getShort(self::TAG_BREW_TIME, 0, true);
		if($this->burnTime === 0){
			$this->brewTime = self::MAX_BREW_TIME;
		}

		$this->loadName($nbt);

		$this->inventory = new BrewingInventory($this);
		$this->loadItems($nbt);

		$this->inventory->setEventProcessor(new class($this) implements InventoryEventProcessor{
			/** @var BrewingStand */
			private $brewingStand;

			public function __construct(BrewingStand $brewingStand){
				$this->brewingStand = $brewingStand;
			}

			public function onSlotChange(Inventory $inventory, int $slot, Item $oldItem, Item $newItem) : ?Item{
				$this->brewingStand->scheduleUpdate();
				return $newItem;
			}
		});
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
        $nbt->setShort(self::TAG_BREW_TIME, self::MAX_BREW_TIME);
		$this->saveName($nbt);
		$this->saveItems($nbt);
	}

	/**
	 * @return string
	 */
	public function getDefaultName() : string{
		return "Brewing Stand";
	}

	public function close() : void{
		if(!$this->closed){
			$this->inventory->removeAllViewers(true);
			$this->inventory = null;

			parent::close();
		}
	}

    /**
     * @return BrewingInventory
     */
	public function getInventory(){
		return $this->inventory;
	}

    /**
     * @return BrewingInventory
     */
	public function getRealInventory(){
		return $this->inventory;
	}

	public function onUpdate() : bool{
		if($this->closed){
			return false;
		}

		$this->timings->startTiming();

		$return = $consumeFuel = $canBrew = false;

		$fuel = $this->getInventory()->getFuel();
		$ingredient = $this->getInventory()->getIngredient();

		for($i = 1; $i <= 3; $i++){
			$currItem = $this->inventory->getItem($i);
			if($this->isValidPotion($currItem)){
				$canBrew = true;
			}
		}

		if($this->burnTime > 0){
			$canBrew = true;
			$this->broadcastFuelAmount($this->burnTime);
			$this->broadcastFuelTotal(self::MAX_BURN_TIME);
		}else{
			if(!$fuel->isNull()){
				if($fuel->equals(Item::get(Item::BLAZE_POWDER, 0), true, false)){
					$consumeFuel = true;
					$canBrew = true;
				}
			}else{
				$canBrew = false;
			}
		}

		if(!$ingredient->isNull() && $canBrew){
			if($canBrew && $this->isValidIngredient($ingredient)){
				foreach($this->inventory->getPotions() as $potion){
					$recipe = Server::getInstance()->getCraftingManager()->matchBrewingRecipe($ingredient, $potion);
					if($recipe !== null){
						$canBrew = true;
						break;
					}
					$canBrew = false;
				}
			}
		}else{
			$canBrew = false;
		}

		if($canBrew){
			if($consumeFuel){
				$fuel->count--;
				if($fuel->getCount() <= 0){
					$fuel = Item::get(Item::AIR);
				}
				$this->inventory->setFuel($fuel);
				$this->burnTime = self::MAX_BURN_TIME;
				$this->broadcastFuelAmount(self::MAX_BURN_TIME);
			}
			$return = true;
			$this->brewTime--;

			$this->broadcastBrewTime($this->brewTime);
			$this->broadcastFuelTotal(self::MAX_BURN_TIME);

			if($this->brewTime <= 0){
				for($i = 1; $i <= 3; $i++){
					$potion = $this->inventory->getItem($i);
					$recipe = Server::getInstance()->getCraftingManager()->matchBrewingRecipe($ingredient, $potion);
					if($recipe != null and !$potion->isNull()){
						$this->inventory->setItem($i, $recipe->getResult());
					}
				}
				$this->getLevelNonNull()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_POTION_BREWED);
				$ingredient->count--;
				if($ingredient->getCount() <= 0){
					$ingredient = Item::get(Item::AIR);
				}
				$this->inventory->setIngredient($ingredient);

				$fuelAmount = max($this->burnTime - 1, 0);
				$this->burnTime = $fuelAmount;
				$this->broadcastFuelAmount($fuelAmount);
			}
		}else{
			$this->brewTime = self::MAX_BREW_TIME;
			$this->broadcastBrewTime(0);
		}

		if($return){
			$this->inventory->sendContents($this->inventory->getViewers());
			$this->onChanged();
		}

		$this->timings->stopTiming();

		return $return;
	}

	public function isValidPotion(Item $item) : bool{
		return (in_array($item->getId(), [
		    Item::POTION,
		    Item::SPLASH_POTION
		]));
	}

	public function isValidIngredient(Item $item) : bool{
		return (
		    in_array($item->getId(), self::INGREDIENTS)
		    && $item->getDamage() === 0
		);
	}

	public function broadcastFuelAmount(int $value) : void{
		$pk = new ContainerSetDataPacket();
		$pk->property = ContainerSetDataPacket::PROPERTY_BREWING_STAND_FUEL_AMOUNT;
		$pk->value = $value;

		foreach($this->inventory->getViewers() as $viewer){
			$pk->windowId = $viewer->getWindowId($this->getInventory());
			if($pk->windowId > 0){
				$viewer->dataPacket($pk);
			}
		}
	}

	public function broadcastFuelTotal(int $value) : void{
		$pk = new ContainerSetDataPacket();
		$pk->property = ContainerSetDataPacket::PROPERTY_BREWING_STAND_FUEL_TOTAL;
		$pk->value = $value;

		foreach($this->inventory->getViewers() as $viewer){
			$pk->windowId = $viewer->getWindowId($this->getInventory());
			if($pk->windowId > 0){
				$viewer->dataPacket($pk);
			}
		}
	}

	public function broadcastBrewTime(int $time) : void{
		$pk = new ContainerSetDataPacket();
		$pk->property = ContainerSetDataPacket::PROPERTY_BREWING_STAND_BREW_TIME;
		$pk->value = $time;

		foreach($this->inventory->getViewers() as $viewer){
			$pk->windowId = $viewer->getWindowId($this->getInventory());
			if($pk->windowId > 0){
				$viewer->dataPacket($pk);
			}
		}
	}

	public function addAdditionalSpawnData(CompoundTag $nbt) : void{
		$nbt->setShort(self::TAG_BREW_TIME, $this->brewTime);

		$this->addNameSpawnData($nbt);
	}
}