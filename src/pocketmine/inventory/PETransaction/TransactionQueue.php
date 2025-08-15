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

use pocketmine\event\inventory\InventoryClickEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\CraftingGrid;
use pocketmine\inventory\PlayerInventory;
use pocketmine\Player;
use SplQueue;
use function get_class;

class TransactionQueue{
    public const DEFAULT_ALLOWED_RETRIES = 5;

    /** @var Player */
    protected $player;
    /** @var SplQueue */
    protected $transactionQueue;
    /** @var CraftingGrid */
    protected $transactionInventory;

    /**
     * @param Player $player
     */
    public function __construct(Player $player){
        $this->player = $player;
        $this->transactionInventory = new class($player, 6) extends CraftingGrid{};
        $this->transactionQueue = new SplQueue();
    }

    /**
     * @return Player
     */
    public function getPlayer() : Player{
        return $this->player;
    }

    /**
     * @return Player
     */
    public function getSource() : Player{
        return $this->player;
    }

    /**
     * @return ?SplQueue
     */ 
    public function getTransactions() : ?SplQueue{
    	return $this->transactionQueue;
    }

    /**
     * @return CraftingGrid
     */
    public function getInventory() : CraftingGrid{
        return $this->transactionInventory;
    }

    /**
     * @param Transaction $transaction
     */
    public function addTransaction(Transaction $transaction) : void{
        $this->transactionQueue->enqueue($transaction);
    }

    public function onCloseWindow() : void{
        while(!$this->transactionQueue->isEmpty()){
            $this->transactionQueue->dequeue()->execute($this);
        }

        foreach($this->transactionInventory->getContents() as $item){
            $this->transactionInventory->removeItem($item);
            $this->player->getInventory()->addItem($item);
        }
    }

  public function execute() : void{
    /** @var Transaction[] */
    $failed = [];

    if($this->transactionQueue->isEmpty()){
      return;
    }

    ($ev = new InventoryTransactionEvent($this))->call();

    while(!$this->transactionQueue->isEmpty()){
      $transaction = $this->transactionQueue->dequeue();

      if(!$transaction instanceof DropItemTransaction){
        if($transaction->getInventory() instanceof ContainerInventory || $transaction->getInventory() instanceof PlayerInventory){
          ($event = new InventoryClickEvent($transaction->getInventory(), $this->player, $transaction->getSlot(), $transaction->getInventory()->getItem($transaction->getSlot())))->call();
          if($event->isCancelled()){
            $ev->setCancelled();
          }
        }
      }

      if($ev->isCancelled()){
        $transaction->revert($this->player); //Send update back to client for cancelled transaction
        continue;
      }elseif(!$transaction->execute($this)){
        $this->player->getServer()->getLogger()->debug("Can't execute " . get_class($transaction) . ": " . $transaction->getLastError());
        if($transaction->addFailure() >= self::DEFAULT_ALLOWED_RETRIES){
          /* Transaction failed completely after several retries, hold onto it to send a slot update */
          $failed[] = $transaction;
        }else{
          /* Add the transaction to the back of the queue to be retried on the next tick */
          $this->addTransaction($transaction);
        }
        continue;
      }
    }

    foreach($failed as $f){
      $f->revert($this->player);
    }
  }
}