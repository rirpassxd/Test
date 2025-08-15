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
use function count;

class InventoryContentPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::INVENTORY_CONTENT_PACKET;

	/** @var int */
	public $windowId;
	/** @var Item[] */
	public $items = [];
    /** @var int[] */
    public $index = [];
	/** @var FullContainerName */
	public $containerName;
	/** @var int */
	public $dynamicContainerSize = 0;
	/** @var Item */
	public $storage = null;
    /** @var int */
    public $dynamicContainerId = 0;

	protected function decodePayload(){
		$this->windowId = $this->getUnsignedVarInt();
		$count = $this->getUnsignedVarInt();
		for($i = 0; $i < $count; ++$i){
		    $this->index[] = $this->getVarInt();
			$this->items[] = $this->getSlot();
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
	}

	protected function encodePayload(){
		$this->putUnsignedVarInt($this->windowId);
		$this->putUnsignedVarInt(count($this->items));
		$index = 1;
		foreach($this->items as $item){
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392 && $this->getProtocol() <= ProtocolInfo::PROTOCOL_428){
				if($item->getId() === 0){
					$this->putVarInt(0);
				}else{
			     	$this->putVarInt($index++);
				}
			}
			$this->putSlot($item);
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
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleInventoryContent($this);
	}
}
