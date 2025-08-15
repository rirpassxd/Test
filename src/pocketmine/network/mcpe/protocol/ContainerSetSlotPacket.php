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
use pocketmine\network\mcpe\NetworkSession;
use function chr;
use function ord;

class ContainerSetSlotPacket extends DataPacket{
	const NETWORK_ID = ProtocolInfo::CONTAINER_SET_SLOT_PACKET;

	public $windowid;
	public $slot;
	public $hotbarSlot = 0;
	/** @var Item */
	public $item;
	public $selectSlot = 0;

	public function decodePayload(){
		$this->windowid = $this->getByte();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->slot = $this->getVarInt();
	    	$this->hotbarSlot = $this->getVarInt();
		}else{
		    $this->slot = $this->getShort();
		    $this->hotbarSlot = $this->getShort();
		}
		$this->item = $this->getSlot();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_92){
		    $this->selectSlot = $this->getByte();
		}
	}

	public function encodePayload(){
        $this->putByte($this->windowid);
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->putVarInt($this->slot);
	    	$this->putVarInt($this->hotbarSlot);
        }else{
            $this->putShort($this->slot);
            $this->putShort($this->hotbarSlot);
        }
		$this->putSlot($this->item);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_92){
            $this->putByte($this->selectSlot);
		}
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleContainerSetSlot($this);
	}

}
