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
use pocketmine\network\mcpe\protocol\types\CommandOriginData;

class CommandRequestPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::COMMAND_REQUEST_PACKET;

	/** @var string */
	public $command;
	/** @var int */
	public $playerUniqueId;
	/** @var CommandOriginData */
	public $originData;
	/** @var bool */
	public $isInternal;
    /** @var int */
    public $version;

	protected function decodePayload(){
		$this->command = $this->getString();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_140){
	    	$this->originData = $this->getCommandOriginData();
	    	$this->isInternal = $this->getBool();
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_567){
	    	    $this->version = $this->getVarInt();
	    	}
		}else{
		    $this->playerUniqueId = $this->getEntityUniqueId();
		}
	}

	protected function encodePayload(){
		$this->putString($this->command);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_140){
	    	$this->putCommandOriginData($this->originData);
            $this->putBool($this->isInternal);
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_567){
	    	    $this->putVarInt($this->version);
	    	}
		}else{
		    $this->putEntityUniqueId($this->playerUniqueId);
		}
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCommandRequest($this);
	}
}
