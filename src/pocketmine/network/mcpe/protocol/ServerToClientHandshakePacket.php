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

class ServerToClientHandshakePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SERVER_TO_CLIENT_HANDSHAKE_PACKET;

	public $publicKey;
	public $serverToken;

	/**
	 * @var string
	 * Server pubkey and token is contained in the JWT.
	 */
	public $jwt;

	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	protected function decodePayload(){
	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
	    	$this->jwt = $this->getString();
	    }else{
	        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	            $this->publicKey = $this->getString();
	            $this->serverToken = $this->getString();
	        }else{
	            $this->publicKey = $this->getShortString();
	            $this->serverToken = $this->getShortString();
	        }
	    }
	}

	protected function encodePayload(){
	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
	    	$this->putString($this->jwt);
	    }else{
	        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	            $this->putString($this->publicKey);
	            $this->putString($this->serverToken);
	        }else{
	            $this->putShortString($this->publicKey);
	            $this->putShortString($this->serverToken);
	        }
	    }
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleServerToClientHandshake($this);
	}
}
