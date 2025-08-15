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
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use function chr;
use function ord;

class ContainerOpenPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CONTAINER_OPEN_PACKET;

	/** @var int */
	public $windowId;
	/** @var int */
	public $type;
	/** @var int|null */
	public $slots = null;
	/** @var int */
	public $x;
	/** @var int */
	public $y;
	/** @var int */
	public $z;
	/** @var int */
	public $entityUniqueId = -1;

    private const HARDCODED_WINDOW_SLOTS = [
		WindowTypes::INVENTORY => 36,
	    WindowTypes::WORKBENCH => 9,
		WindowTypes::FURNACE => 3,
		WindowTypes::ENCHANTMENT => 2,
		WindowTypes::BREWING_STAND => 5,
		WindowTypes::ANVIL => 2,
		WindowTypes::DISPENSER => 9,
		WindowTypes::DROPPER => 9,
		WindowTypes::HOPPER => 5,
		WindowTypes::CAULDRON => 1,
		WindowTypes::MINECART_CHEST => 9,
		WindowTypes::MINECART_HOPPER => 5,
		WindowTypes::HORSE => 2,
		WindowTypes::BEACON => 1,
		WindowTypes::STRUCTURE_EDITOR => 0,
		WindowTypes::TRADING => 3
	];

	protected function decodePayload(){
		$this->windowId = $this->getByte();
		$this->type = $this->getByte();
		if($this->getProtocol() < ProtocolInfo::PROTOCOL_110){
			$this->slots = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getVarInt() : $this->getShort();
		}
		$this->getBlockPosition($this->x, $this->y, $this->z);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->entityUniqueId = $this->getEntityUniqueId();
		}
	}

	protected function encodePayload(){
        $this->putByte($this->windowId);
        $this->putByte($this->type);
		if($this->getProtocol() < ProtocolInfo::PROTOCOL_110){
		    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
		    	$this->putVarInt($this->slots ?? self::HARDCODED_WINDOW_SLOTS[$this->type]);
		    }else{
		        $this->putShort($this->slots ?? self::HARDCODED_WINDOW_SLOTS[$this->type]);
		    }
		}
		$this->putBlockPosition($this->x, $this->y, $this->z);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	        $this->putEntityUniqueId($this->entityUniqueId);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleContainerOpen($this);
	}
}
