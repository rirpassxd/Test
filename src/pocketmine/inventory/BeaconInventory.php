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

namespace pocketmine\inventory;

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;
use pocketmine\tile\Beacon;

class BeaconInventory extends ContainerInventory{

	public function __construct(Beacon $tile){
		parent::__construct($tile);
	}

	public function getName() : string{
		return "Beacon";
	}

	public function getDefaultSize() : int{
		return 1;
	}

	public function getResultSlot() : int{
		return 0;
	}

	public function getNetworkType() : int{
		return WindowTypes::BEACON;
	}

	public function onClose(Player $who) : void{
		parent::onClose($who);

		$this->getHolder()->getLevelNonNull()->dropItem($this->getHolder()->add(0.5, 0.5, 0.5), $this->getItem($this->getResultSlot()));
		$this->clear($this->getResultSlot());
	}

	/**
	 * @param Player|Player[] $target
	 */
	public function sendContents($target) : void{
		if($target instanceof Player){
			$target = [$target];
		}

		foreach($target as $player){
			if(($id = $player->getWindowId($this)) === ContainerIds::NONE){
				$this->close($player);
				continue;
			}
	    	if($player->getProtocol() < ProtocolInfo::PROTOCOL_130){
		    	continue;
			}
			parent::sendContents($player);
		}
	}

	/**
	 * @param int             $index
	 * @param Player|Player[] $target
	 */
	public function sendSlot(int $index, $target) : void{
		if($target instanceof Player){
			$target = [$target];
		}

		foreach($target as $player){
			if(($id = $player->getWindowId($this)) === ContainerIds::NONE){
				$this->close($player);
				continue;
			}
	    	if($player->getProtocol() < ProtocolInfo::PROTOCOL_130){
		    	continue;
			}
			parent::sendSlot($index, $player);
		}
	}
}