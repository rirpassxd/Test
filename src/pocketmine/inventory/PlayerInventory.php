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

namespace pocketmine\inventory;

use InvalidArgumentException;
use LogicException;
use pocketmine\entity\Human;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\network\mcpe\cache\CreativePacketCache;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\ContainerSetContentPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\CreativeGroup;
use pocketmine\Player;
use RuntimeException;
use SplFixedArray;
use function array_search;
use function in_array;
use function is_array;
use function range;

class PlayerInventory extends BaseInventory{

	/** @var Human */
	protected $holder;
	/** @var int */
	protected $itemInHandIndex = 0;
	/** @var SplFixedArray<int> */
	protected $hotbar;

	/**
	 * @param Human $player
	 */
	public function __construct(Human $player){
		$this->holder = $player;
		$this->resetHotbar();
		parent::__construct();
	}

	public function getName() : string{
		return "Player";
	}

	public function getDefaultSize() : int{
		return 36;
	}

	/**
	 * Returns the index of the inventory slot mapped to the specified hotbar slot, or -1 if the hotbar slot does not exist.
	 *
	 * @param int $index
	 *
	 * @return int
	 */
	public function getHotbarSlotIndex(int $index) : int{
		return $this->hotbar[$index] ?? -1;
	}

	/**
	 * Links a hotbar slot to the specified slot in the main inventory. -1 links to no slot and will clear the hotbar slot.
	 * This method is intended for use in network interaction with clients only.
	 *
	 * NOTE: Do not change hotbar slot mapping with plugins, this will cause myriad client-sided bugs, especially with desktop GUI clients.
	 *
	 * @param int $hotbarSlot
	 * @param int $inventorySlot
	 *
	 * @throws RuntimeException if the hotbar slot is out of range
	 * @throws InvalidArgumentException if the inventory slot is out of range
	 */
	public function setHotbarSlotIndex(int $hotbarSlot, int $inventorySlot){
		if($inventorySlot < -1 or $inventorySlot >= $this->getSize()){
			throw new InvalidArgumentException("Inventory slot index \"$inventorySlot\" is out of range");
		}

		if($inventorySlot !== -1 and ($alreadyEquippedIndex = array_search($inventorySlot, $this->getHotbar(), true)) !== false){
			/* Swap the slots
			 * This assumes that the equipped slot can only be equipped in one other slot
			 * it will not account for ancient bugs where the same slot ended up linked to several hotbar slots.
			 * Such bugs will require a hotbar reset to default.
			 */
			$this->hotbar[$alreadyEquippedIndex] = $this->hotbar[$hotbarSlot];
		}

		$this->hotbar[$hotbarSlot] = $inventorySlot;
	}

	/**
	 * Returns the item in the slot linked to the specified hotbar slot, or Air if the slot is not linked to any hotbar slot.
	 * @param int $hotbarSlotIndex
	 *
	 * @return Item
	 */
	public function getHotbarSlotItem(int $hotbarSlotIndex) : Item{
		$inventorySlot = $this->getHotbarSlotIndex($hotbarSlotIndex);
		if($inventorySlot !== -1){
			return $this->getItem($inventorySlot);
		}else{
			return ItemFactory::get(Item::AIR, 0, 0);
		}
	}

	public function getHotbar() : array{
		return $this->hotbar->toArray();
	}

	/**
	 * Resets hotbar links to their original defaults.
	 */
	public function resetHotbar() : void{
		$this->hotbar = SplFixedArray::fromArray(range(0, $this->getHotbarSize() - 1, 1));
	}

    /**
     * @param int $hotbarSlot
     * 
     * @return bool
     */
    public function isHotbarSlot(int $hotbarSlot) : bool{
        return $hotbarSlot >= 0 && $hotbarSlot <= $this->getHotbarSize();
    }

	/**
	 * @param int $slot
	 *
	 * @throws InvalidArgumentException
	 */
	public function throwIfNotHotbarSlot(int $slot){
		if(!$this->isHotbarSlot($slot)){
			throw new InvalidArgumentException("$slot is not a valid hotbar slot index (expected 0 - " . ($this->getHotbarSize() - 1) . ")");
		}
	}

	/**
	 * Returns the hotbar slot number the holder is currently holding.
	 * @return int
	 */
	public function getHeldItemIndex() : int{
		return $this->itemInHandIndex;
	}

	/**
	 * Called when a client equips a hotbar slot. This method should not be used by plugins.
	 * This method will call PlayerItemHeldEvent.
	 *
	 * @param int $hotbarSlot Number of the hotbar slot to equip.
	 * @param ?int $inventorySlot
	 *
	 * @return bool if the equipment change was successful, false if not.
	 */
	public function equipItem(int $hotbarSlot, ?int $inventorySlot = null) : bool{
		$holder = $this->getHolder();
		if(!$this->isHotbarSlot($hotbarSlot)){
			if($holder instanceof Player){
				$this->sendContents($holder);
			}
			return false;
		}

		if($holder instanceof Player){
			$ev = new PlayerItemHeldEvent($holder, $inventorySlot === null ? $this->getItem($hotbarSlot) : $this->getItem($inventorySlot), $hotbarSlot);
			$ev->call();

			if($ev->isCancelled()){
				$this->sendHeldItem($holder);
				return false;
			}
		}
		$this->setHeldItemIndex($hotbarSlot, false, $inventorySlot);

		return true;
	}

	/**
	 * Sets which hotbar slot the player is currently loading.
	 *
	 * @param int  $hotbarSlot 0-8 index of the hotbar slot to hold
	 * @param bool $send Whether to send updates back to the inventory holder. This should usually be true for plugin calls.
	 *                    It should only be false to prevent feedback loops of equipment packets between client and server.
	 * @param ?int $inventorySlot
	 *
	 * @throws InvalidArgumentException if the hotbar slot is out of range
	 */
	public function setHeldItemIndex(int $hotbarSlot, bool $send = true, ?int $inventorySlot = null){
	    $this->throwIfNotHotbarSlot($hotbarSlot);
		$this->itemInHandIndex = $hotbarSlot;

		if($inventorySlot !== null){
			/* Handle a hotbar slot mapping change. This allows PE to select different inventory slots.
			 * This is the only time slot mapping should ever be changed. */
            $this->setHotbarSlotIndex($hotbarSlot, $inventorySlot);
		}

		if($this->getHolder() instanceof Player and $send){
			$this->sendHeldItem($this->getHolder());
		}

		$this->sendHeldItem($this->getHolder()->getViewers());
	}

	/**
	 * Returns the currently-held item.
	 *
	 * @return Item
	 */
	public function getItemInHand() : Item{
		return $this->getHotbarSlotItem($this->itemInHandIndex);
	}

	/**
	 * Sets the item in the currently-held slot to the specified item.
	 *
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function setItemInHand(Item $item) : bool{
		return $this->setItem($this->getHeldItemSlot(), $item);
	}

	/**
	 * Returns the hotbar slot number currently held.
	 * @return int
	 */
	public function getHeldItemSlot() : int{
		return $this->getHotbarSlotIndex($this->itemInHandIndex);
	}

	/**
	 * Sets the hotbar slot link of the currently-held hotbar slot.
	 * @deprecated Do not change hotbar slot mapping with plugins, this will cause myriad client-sided bugs, especially with desktop GUI clients.
	 *
	 * @param int $slot
	 */
	public function setHeldItemSlot(int $slot){
		if($slot >= -1 and $slot < $this->getSize()){
			$this->setHotbarSlotIndex($this->getHeldItemIndex(), $slot);
		}
	}

	/**
	 * Sends the currently-held item to specified targets.
	 * @param Player|Player[] $target
	 */
	public function sendHeldItem($target){
		$item = $this->getItemInHand();

		$pk = new MobEquipmentPacket();
		$pk->entityRuntimeId = $this->getHolder()->getId();
		$pk->item = $item;
		$pk->inventorySlot = $this->getHeldItemSlot();
		$pk->hotbarSlot = $this->getHeldItemIndex();
		$pk->windowId = ContainerIds::INVENTORY;

		if(!is_array($target)){
		    if($target->getProtocol() >= ProtocolInfo::PROTOCOL_130){
		        $pk->inventorySlot = $pk->hotbarSlot;
		    }
			$target->dataPacket($pk);
			if($this->getHeldItemSlot() !== -1 and $target === $this->getHolder()){
				$this->sendSlot($this->getHeldItemSlot(), $target);
			}
		}else{
		    foreach($target as $player){
		        $packet = clone $pk;
		        if($player->getProtocol() >= ProtocolInfo::PROTOCOL_130){
		            $packet->inventorySlot = $pk->hotbarSlot;
		        }
		        $player->dataPacket($packet);
		    }
			if($this->getHeldItemSlot() !== -1 and in_array($this->getHolder(), $target, true)){
				$this->sendSlot($this->getHeldItemSlot(), $this->getHolder());
			}
		}
	}

	/**
	 * Returns the number of slots in the hotbar.
	 * @return int
	 */
	public function getHotbarSize() : int{
		return 9;
	}

	public function sendCreativeContents(){
		//TODO: this mess shouldn't be in here
		$holder = $this->getHolder();
		if(!($holder instanceof Player)){
			throw new LogicException("Cannot send creative inventory contents to non-player inventory holder");
		}

		if($holder->getProtocol() >= ProtocolInfo::PROTOCOL_392){
		    $pk = new CreativeContentPacket();
			$pk->items = !$holder->isSpectator() ? CreativePacketCache::getInstance()->getItems($holder->getProtocol()) : [];
		    $pk->groups = CreativePacketCache::getInstance()->getGroups($holder->getProtocol());
        }elseif($holder->getProtocol() >= ProtocolInfo::PROTOCOL_130){
            $creativeItemEntries = !$holder->isSpectator() ? CreativePacketCache::getInstance()->getItems($holder->getProtocol()) : [];

            $items = [];
            foreach($creativeItemEntries as $creativeItemEntry){
                $items[] = clone $creativeItemEntry->getItem();
            }

	    	$pk = new InventoryContentPacket();
	    	$pk->windowId = ContainerIds::CREATIVE;
			$pk->items = $items;
        }else{
	   	    $pk = new ContainerSetContentPacket();
	    	$pk->windowid = ContainerIds::CREATIVE;
		    if($holder->getGamemode() === Player::CREATIVE){
                $creativeItemEntries = CreativePacketCache::getInstance()->getItems($holder->getProtocol());
                foreach($creativeItemEntries as $i => $creativeItemEntry){
                    $pk->slots[$i] = clone $creativeItemEntry->getItem();
                }
		    }
		    $pk->targetEid = $holder->getId();
        }

        $holder->dataPacket($pk);
    }

    public function clearAll(bool $send = true) : void{
        $this->resetHotbar();
        parent::clearAll($send);
    }

	public function onSlotChange(int $index, Item $before, bool $send) : void{
	    parent::onSlotChange($index, $before, $send);
		if($index === $this->itemInHandIndex){
			$this->sendHeldItem($this->holder->getViewers());
			if($send && $this->holder instanceof Player){
				$this->sendHeldItem($this->holder);
			}
		}
	}

	/**
	 * This override is here for documentation and code completion purposes only.
	 * @return Human|Player
	 */
	public function getHolder(){
		return $this->holder;
	}
}
