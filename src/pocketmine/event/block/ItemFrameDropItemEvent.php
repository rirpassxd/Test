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

namespace pocketmine\event\block;

use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\tile\ItemFrame;

class ItemFrameDropItemEvent extends BlockEvent implements Cancellable{

	/** @var  Player */
	private $player;
	/** @var  Item */
	private $item;
	/** @var  ItemFrame */
	private $itemFrame;

	/**
	 * ItemFrameDropItemEvent constructor.
	 *
	 * @param Player $player
	 * @param Block $block
	 * @param ItemFrame $itemFrame
	 * @param Item $item
	 */
	public function __construct(Player $player, Block $block, ItemFrame $itemFrame, Item $item){
		$this->player = $player;
		$this->block = $block;
		$this->itemFrame = $itemFrame;
		$this->item = $item;
	}

	/**
	 * @return Player
	 */
	public function getPlayer(){
		return $this->player;
	}

	/**
	 * @return ItemFrame
	 */
	public function getItemFrame(){
		return $this->itemFrame;
	}

	/**
	 * @return Item
	 */
	public function getItem(){
		return $this->item;
	}

    public function setItem(Item $item) :void{
        $this->item = $item;
    }
}
