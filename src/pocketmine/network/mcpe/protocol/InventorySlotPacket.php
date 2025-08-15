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

namespace pocketmine\network\mcpe\protocol;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;
use pocketmine\network\mcpe\protocol\types\ContainerIds;

class InventorySlotPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::INVENTORY_SLOT_PACKET;

	/** @var int */
	public $windowId;
	/** @var int */
	public $inventorySlot;
	/** @var int */
	public $isNullItem;
	/** @var FullContainerName */
	public $containerName;
	/** @var int */
	public $dynamicContainerSize = 0;
	/** @var Item */
	public $storage;
    /** @var int */
    public $dynamicContainerId = 0;
	/** @var Item */
	public $item;

	protected function decodePayload(){
		$this->windowId = $this->getUnsignedVarInt();
		$this->inventorySlot = $this->getUnsignedVarInt();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392 && $this->getProtocol() <= ProtocolInfo::PROTOCOL_428){
		    $this->isNullItem = $this->getVarInt();
		}
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_712){
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_729){
				$this->containerName = FullContainerName::read($this);
				if($this->getProtocol() >= ProtocolInfo::PROTOCOL_748){
					$this->storage = $this->getSlot();
				}else{
				    $this->dynamicContainerSize = $this->getUnsignedVarInt();
				}
			}else{
                $this->dynamicContainerId = $this->getUnsignedVarInt();
			}
        }
		$this->item = $this->getSlot();
	}

	protected function encodePayload(){
		$this->putUnsignedVarInt($this->windowId);
		$this->putUnsignedVarInt($this->inventorySlot);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392 && $this->getProtocol() <= ProtocolInfo::PROTOCOL_428){
	    	$this->putVarInt($this->item->isNull() ? 0 : 1);
		}
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_712){
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_729){
				($this->containerName ?? new FullContainerName(ContainerIds::FIRST))->write($this);
				if($this->getProtocol() >= ProtocolInfo::PROTOCOL_748){
					$this->putSlot($this->storage ?? ItemFactory::get(Item::AIR));
				}else{
				    $this->putUnsignedVarInt($this->dynamicContainerSize);
				}
			}else{
                $this->putUnsignedVarInt($this->dynamicContainerId);
			}
        }
		$this->putSlot($this->item);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleInventorySlot($this);
	}
}
