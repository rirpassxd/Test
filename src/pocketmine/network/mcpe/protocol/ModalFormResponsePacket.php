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

class ModalFormResponsePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::MODAL_FORM_RESPONSE_PACKET;

	public const CANCEL_REASON_CLOSED = 0;
	/** Sent if a form is sent when the player is on a loading screen */
	public const CANCEL_REASON_USER_BUSY = 1;

	/** @var int */
	public $formId;
	/** @var ?string */
	public $formData; //json
    /** @var ?int */
    public $cancelReason;

	protected function decodePayload(){
		$this->formId = $this->getUnsignedVarInt();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_544){
		    $this->formData = $this->getBool() ? $this->getString() : null;
		    $this->cancelReason = $this->getBool() ? $this->getByte() : null;
		}else{
	    	$this->formData = $this->getString();
		}
	}

	protected function encodePayload(){
		$this->putUnsignedVarInt($this->formId);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_544){
	        if($this->formData !== null){
		        $this->putBool(true);
	        	$this->putString($this->formData);
	        }else{
		        $this->putBool(false);
	       	}
	        if($this->cancelReason !== null){
		        $this->putBool(true);
	        	$this->putByte($this->cancelReason);
	        }else{
		        $this->putBool(false);
	       	}
		}else{
	    	$this->putString($this->formData);
		}
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleModalFormResponse($this);
	}
}
