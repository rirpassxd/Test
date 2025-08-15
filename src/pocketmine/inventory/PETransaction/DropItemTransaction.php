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

use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\item\Item;
use pocketmine\Player;

class DropItemTransaction extends Transaction{

    /**
     * @param Item $targetItem
     */
    public function __construct(Item $targetItem){
        $this->targetItem = $targetItem;
    }

	/**
	 * @param Player $source
	 */
	public function revert(Player $source) : void{

	}

    /**
     * @param TransactionQueue $transactionQueue
     * 
     * @return bool
     */
    public function execute(TransactionQueue $transactionQueue) : bool{
        $player = $transactionQueue->getPlayer();
        $item = $this->getTargetItem();

        if($transactionQueue->getInventory()->contains($item)){
            $transactionQueue->getInventory()->removeItem($item);
        }elseif($player->isCreative(true)){
            if(Item::getCreativeItemIndex($item, $player->getProtocol()) === -1){
                return $this->error("Player transaction inventory not contains $item");
            }
        }else{
            return $this->error("Player transaction inventory not contains $item");
        }

        $ev = new PlayerDropItemEvent($player, $item);
        $ev->call();
        if($ev->isCancelled()){
            $player->getInventory()->addItem($item);
            return true;
        }

        $player->dropItem($item);
        $player->getInventory()->sendHeldItem($player);
        $player->getInventory()->sendHeldItem($player->getViewers());

        return true;
    }
}