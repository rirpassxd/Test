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

class UpdateTradePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::UPDATE_TRADE_PACKET;

	//TODO: find fields

	/** @var int */
	public $windowId;
	/** @var int */
	public $windowType = WindowTypes::TRADING; //Mojang hardcoded this -_-
	/** @var int */
	public $thisIsAlwaysZero = 0; //hardcoded to 0
	/** @var int */
	public $uvarint;
	/** @var int */
	public $tradeTier;
	/** @var int */
	public $traderEid;
	/** @var int */
	public $playerEid;
	/** @var string */
	public $displayName;
	/** @var bool */
	public $isWilling;
	/** @var bool */
	public $isV2Trading;
	/** @var string */
	public $offers;

	protected function decodePayload(){
		$this->windowId = $this->getByte();
		$this->windowType = $this->getByte();
		$this->thisIsAlwaysZero = $this->getVarInt();
		if($this->getProtocol() < ProtocolInfo::PROTOCOL_350){
		    $this->uvarint = $this->getVarInt();
		    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_310){
		        $this->tradeTier = $this->getVarInt();
		    }
            $this->isWilling = $this->getBool();
		}else{
		    $this->tradeTier = $this->getVarInt();
		}
		$this->traderEid = $this->getEntityUniqueId();
		$this->playerEid = $this->getEntityUniqueId();
		$this->displayName = $this->getString();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_350){
            $this->isWilling = $this->getBool();
            $this->isV2Trading = $this->getBool();
		}
		$this->offers = $this->getRemaining();
	}

	protected function encodePayload(){
        $this->putByte($this->windowId);
        $this->putByte($this->windowType);
		$this->putVarInt($this->thisIsAlwaysZero);
		if($this->getProtocol() < ProtocolInfo::PROTOCOL_350){
		    $this->putVarInt($this->uvarint);
		    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_310){
		        $this->putVarInt($this->tradeTier);
		    }

            $this->putBool($this->isWilling);
		}else{
	    	$this->putVarInt($this->tradeTier);
		}
		$this->putEntityUniqueId($this->traderEid);
		$this->putEntityUniqueId($this->playerEid);
		$this->putString($this->displayName);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_350){
            $this->putBool($this->isWilling);
            $this->putBool($this->isV2Trading);
		}
        $this->put($this->offers);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdateTrade($this);
	}
}
