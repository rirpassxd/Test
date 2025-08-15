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

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use function chr;
use function ord;
use UnexpectedValueException;

/**
 * One of the most useless packets.
 */
class PlayerHotbarPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_HOTBAR_PACKET;

	/** @var int */
	public $selectedHotbarSlot;
	/** @var int */
	public $windowId = ContainerIds::INVENTORY;
	/** @var array */
	public $slots = [];
	/** @var bool */
	public $selectHotbarSlot = true;

	protected function decodePayload(){
		$this->selectedHotbarSlot = $this->getUnsignedVarInt();
		$this->windowId = $this->getByte();
		if ($this->getProtocol() === ProtocolInfo::PROTOCOL_201 || $this->getProtocol() < ProtocolInfo::PROTOCOL_200) {
            $slots = $this->getUnsignedVarInt();
	    if($slots > 128){
	        throw new UnexpectedValueException("Too many slots count: $slots");
	    }
            for ($slot = 0; $slot < $slots; ++$slot) {
                $this->slots[$slot] = $this->getUnsignedVarInt();
            }
		}
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_135) {
	    	$this->selectHotbarSlot = $this->getBool();
		}
	}

	protected function encodePayload(){
		$this->putUnsignedVarInt($this->selectedHotbarSlot);
        $this->putByte($this->windowId);
		if ($this->getProtocol() === ProtocolInfo::PROTOCOL_201 || $this->getProtocol() < ProtocolInfo::PROTOCOL_200) {
	    	$this->putUnsignedVarInt(count($this->slots));
	    	foreach ($this->slots as $slot) {
		    	$this->putUnsignedVarInt($slot);
	    	}
		}
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_135) {
            $this->putBool($this->selectHotbarSlot);
		}
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerHotbar($this);
	}
}
