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

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\LittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

class ItemFrame extends Spawnable{
	public const TAG_ITEM_ROTATION = "ItemRotation";
	public const TAG_ITEM_DROP_CHANCE = "ItemDropChance";
	public const TAG_ITEM = "Item";

	/** @var Item */
	private $item;
	/** @var int */
	private $itemRotation;
	/** @var float */
	private $itemDropChance;

	/** @var NetworkLittleEndianNBTStream|null */
	private static $nbtWriter = null;

	/** @var LittleEndianNBTStream|null */
	private static $oldNbtWriter = null;

	/** @var int[string|null] */
	private $protocolSpawnCompoundCache = [];

	protected function readSaveData(CompoundTag $nbt) : void{
		if(($itemTag = $nbt->getCompoundTag(self::TAG_ITEM)) !== null){
			$this->item = Item::nbtDeserialize($itemTag);
		}else{
			$this->item = ItemFactory::get(Item::AIR, 0, 0);
		}
		$this->item->setOnItemFrame(true);

		$this->itemRotation = $nbt->getByte(self::TAG_ITEM_ROTATION, 0, true);
		$this->itemDropChance = $nbt->getFloat(self::TAG_ITEM_DROP_CHANCE, 1.0, true);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$nbt->setFloat(self::TAG_ITEM_DROP_CHANCE, $this->itemDropChance);
		$nbt->setByte(self::TAG_ITEM_ROTATION, $this->itemRotation);
		$nbt->setTag($this->item->nbtSerialize(-1, self::TAG_ITEM));
	}

	public function hasItem() : bool{
		return !$this->item->isNull();
	}

	public function getItem() : Item{
		return clone $this->item;
	}

	public function setItem(Item $item = null){
		if($item !== null and !$item->isNull()){
			$this->item = clone $item;
		}else{
			$this->item = ItemFactory::get(Item::AIR, 0, 0);
		}
		$this->item->setOnItemFrame(true);

		$this->onChanged();
	}

	public function getItemRotation() : int{
		return $this->itemRotation;
	}

	public function setItemRotation(int $rotation){
		$this->itemRotation = $rotation;
		$this->onChanged();
	}

	public function getItemDropChance() : float{
		return $this->itemDropChance;
	}

	public function setItemDropChance(float $chance){
		$this->itemDropChance = $chance;
		$this->onChanged();
	}

	protected function addAdditionalSpawnData(CompoundTag $nbt) : void{
		$nbt->setFloat(self::TAG_ITEM_DROP_CHANCE, $this->itemDropChance);
		$nbt->setByte(self::TAG_ITEM_ROTATION, $this->itemRotation);
	}

	/**
	 * Performs actions needed when the tile is modified, such as clearing caches and respawning the tile to players.
	 * WARNING: This MUST be called to clear spawn-compound and chunk caches when the tile's spawn compound has changed!
	 */
	protected function onChanged() : void{
		$this->protocolSpawnCompoundCache = [];
		$this->spawnToAll();

		$this->level->clearChunkCache($this->getFloorX() >> 4, $this->getFloorZ() >> 4);
	}

	public function getProtocolSerializedSpawnCompound(int $playerProtocol) : string{
		$compound = $this->getSpawnCompound();
		if(!($this->item->getNamedTagEntry("map_uuid") instanceof LongTag) || $playerProtocol >= ProtocolInfo::PROTOCOL_130){
			$compound->setTag($this->item->nbtSerialize(-1, self::TAG_ITEM, $playerProtocol));
		}else{
		    $item = clone $this->item;
			$mapId = $item->getNamedTagEntry("map_uuid")->getValue();
	    	$item->removeNamedTagEntry("map_uuid");
	    	$item->setNamedTagEntry(new StringTag("map_uuid", (string) $mapId));

			$compound->setTag($item->nbtSerialize(-1, self::TAG_ITEM));
		}

		if(!isset($this->protocolSpawnCompoundCache[$playerProtocol])){
		    if($playerProtocol >= ProtocolInfo::PROTOCOL_90){
		    	if(self::$nbtWriter === null){
			    	self::$nbtWriter = new NetworkLittleEndianNBTStream();
		    	}
		    	$nbtWriter = self::$nbtWriter;
		    }else{
		    	if(self::$oldNbtWriter === null){
			    	self::$oldNbtWriter = new LittleEndianNBTStream();
		    	}
		    	$nbtWriter = self::$oldNbtWriter;
		    }

			$this->protocolSpawnCompoundCache[$playerProtocol] = $nbtWriter->write($compound);
		}

		return $this->protocolSpawnCompoundCache[$playerProtocol];
	}
}
