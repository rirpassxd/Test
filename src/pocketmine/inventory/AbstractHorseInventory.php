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

use pocketmine\entity\passive\AbstractHorse;
use pocketmine\item\Item;
use pocketmine\item\Saddle;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

abstract class AbstractHorseInventory extends BaseInventory{
	/** @var AbstractHorse */
	protected $holder;

	public function __construct(AbstractHorse $holder, array $items = [], int $size = null, string $title = null){
		$this->holder = $holder;
		parent::__construct($items, $size, $title);
	}

	/**
	 * @param Item $saddle
	 */
	public function setSaddle(Item $saddle) : void{
		$this->setItem(0, $saddle);

		$this->holder->setSaddled($saddle instanceof Saddle);
	}

	public function onSlotChange(int $index, Item $before, bool $send) : void{
		parent::onSlotChange($index, $before, $send);

		if($index === 0){
			$this->holder->setSaddled($this->getSaddle() instanceof Saddle);

			$this->holder->level->broadcastLevelSoundEvent($this->holder, LevelSoundEventPacket::SOUND_SADDLE);
		}
	}

	/**
	 * @return Item
	 */
	public function getSaddle() : Item{
		return $this->getItem(0);
	}

	/**
	 * @return AbstractHorse
	 */
	public function getHolder(){
		return $this->holder;
	}
}