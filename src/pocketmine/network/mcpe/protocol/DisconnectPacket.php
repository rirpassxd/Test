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

class DisconnectPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::DISCONNECT_PACKET;

	/** @var int */
	public $reason; //TODO: add constants / enum
	/** @var bool */
	public $hideDisconnectionScreen = false;
	/** @var string */
	public $message = "";
    /** @var ?string */
	public $filteredMessage = null;

	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	protected function decodePayload(){
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_622){
			$this->reason = $this->getVarInt();
		}

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->hideDisconnectionScreen = $this->getBool();
		}
		if($this->getProtocol() < ProtocolInfo::PROTOCOL_110){
			$this->message = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getString() : $this->getShortString();
		}elseif(!$this->hideDisconnectionScreen){
           if($this->getProtocol() >= ProtocolInfo::PROTOCOL_712){
               $this->message = $this->getString();
			   $this->filteredMessage = $this->getString();
           }else{
               $this->message = $this->getString();
           }
        }
	}

	protected function encodePayload(){
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_622){
			$this->putVarInt($this->reason);
		}

        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
            $this->putBool($this->hideDisconnectionScreen);
        }
        if($this->getProtocol() < ProtocolInfo::PROTOCOL_110){
            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
                $this->putString($this->message);
            }else{
                $this->putShortString($this->message);
            }
        }elseif(!$this->hideDisconnectionScreen){
            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_712){
                $this->putString($this->message);
                $this->putString($this->filteredMessage ?? "");
            }else{
                $this->putString($this->message);
            }
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleDisconnect($this);
	}
}
