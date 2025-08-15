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

namespace pocketmine\inventory\PETransaction;

use pocketmine\inventory\AnvilInventory;
use pocketmine\inventory\EnchantInventory;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;
//use pocketmine\item\Arrow;
//use pocketmine\item\Durable;
use function abs;

class Transaction{
    /** @var ?Inventory */
    protected $inventory;
    /** @var int */
    protected $slot;
    /** @var Item */
    protected $targetItem;
    /** @var int */
    protected $failures = 0;
    /** @var string[] */
    protected $errorLog = [];

	/**
	 * @param Inventory $inventory
	 * @param int       $slot
	 * @param Item      $targetItem
	 */
    public function __construct(Inventory $inventory, int $slot, Item $targetItem){
        $this->inventory = $inventory;
        $this->slot = $slot;
        $this->targetItem = $targetItem;
    }

    /**
     * @return ?Inventory
     */
    public function getInventory() : ?Inventory{
        return $this->inventory;
    }

    /**
     * @return int
     */
    public function getSlot() : int{
        return $this->slot;
    }

    /**
     * @return Item
     */
    public function getTargetItem() : Item{
        return clone $this->targetItem;
    }

    /**
     * @return Item
     */
    public function getSourceItem() : Item{
        return $this->inventory->getItem($this->slot);
    }

    /**
     * @param Item $item
     */
    public function setTargetItem(Item $item) : void{
        $this->targetItem = $item;
    }

    /**
     * @return int
     */
    public function addFailure() : int{
        return ++$this->failures;
    }

	/**
	 * @param Player $source
	 */
	public function revert(Player $source) : void{
		if($this->getInventory() instanceof AnvilInventory || $this->getInventory() instanceof EnchantInventory){
			return;
		}

		$this->inventory->sendSlot($this->slot, $source);
	}

	/**
	 * Returns the change in inventory resulting from this transaction
	 *
	 * @return array ("in" => items added to the inventory, "out" => items removed from the inventory)
	 * ]
	 */
    public function getChange() : ?array{
        $sourceItem = $this->getInventory()->getItem($this->slot);

        if($sourceItem->equalsExact($this->targetItem)){
			//This should never happen, somehow a change happened where nothing changed
            return null;

        }elseif($sourceItem->equals($this->targetItem, true, true)){
            $item = clone $sourceItem;
            $countDiff = $this->targetItem->getCount() - $sourceItem->getCount();
            $item->setCount(abs($countDiff));

            if($countDiff < 0){     //Count decreased
                return ["in" => null,
                    "out" => $item];
            }elseif($countDiff > 0){ //Count increased
                return ["in" => $item,
                    "out" => null];
            }else{
				//Should be impossible (identical items and no count change)
				//This should be caught by the first condition even if it was possible
                return null;
            }
        }elseif($sourceItem->getId() !== ItemIds::AIR && $this->targetItem->getId() === ItemIds::AIR){
			//Slot emptied (item removed)
            return ["in" => null,
                "out" => clone $sourceItem];

        }elseif($sourceItem->getId() === ItemIds::AIR && $this->targetItem->getId() !== ItemIds::AIR){
			//Slot filled (item added)
            return ["in" => $this->getTargetItem(),
                "out" => null];

        }else{
			//Some other slot change - an item swap (tool damage changes will be ignored as they are processed server-side before any change is sent by the client
            return ["in" => $this->getTargetItem(),
                "out" => clone $sourceItem];
        }
    }

	/**
	 * @param TransactionQueue $transactionQueue
	 *
	 * @return bool
	 *
	 * Handles transaction execution. Returns whether transaction was successful or not.
	 */
    public function execute(TransactionQueue $transactionQueue) : bool{
        $change = $this->getChange();
        $player = $transactionQueue->getPlayer();

        if($change === null){
            $this->getInventory()->setItem($this->getSlot(), $this->getTargetItem(), false);
            return true;
        }

        if($change["out"] instanceof Item){
            if(!$this->getInventory()->getItem($this->getSlot())->equals($change["out"], $change["out"]->hasAnyDamageValue(), !$change["out"]->hasNamedTag())){
                return $this->error("Player inventory not contains " . $change["out"] . " in slot " . $this->getSlot() . ". Have " . $this->getInventory()->getItem($this->getSlot()));
            }
        }

        if($change["in"] instanceof Item){
            if($transactionQueue->getInventory()->contains($change["in"])){
                //условие построено странно но мне нравится такой стиль
            }elseif($player->isCreative(true) and Item::getCreativeItemIndex($change["in"], $player->getProtocol()) !== -1){
                $transactionQueue->getInventory()->addItem($change["in"]);
            //}elseif($this->targetItem instanceof Durable or $this->targetItem instanceof Arrow){
                //превентивный удар по легаси, возможно с избытком
                //return false;
            }else{
                return $this->error("Transaction inventory not contains " . $change["in"] . ". Transaction inventory contents: " . implode("; ", $transactionQueue->getInventory()->getContents()));
            }
        }

        if($change["out"] instanceof Item){
            $transactionQueue->getInventory()->addItem($change["out"]);
        }
        if($change["in"] instanceof Item){
            $transactionQueue->getInventory()->removeItem($change["in"]);
        }

        $this->getInventory()->setItem($this->getSlot(), $this->getTargetItem(), false);

        return true;
    }

    /**
     * @param string $error
     * 
     * @return bool
     */
    public function error(string $error) : bool{
        $this->errorLog[] = $error;

        return false;
    }

    /**
     * @return ?string
     */
    public function getLastError() : ?string{
        return array_shift($this->errorLog);
    }
}
