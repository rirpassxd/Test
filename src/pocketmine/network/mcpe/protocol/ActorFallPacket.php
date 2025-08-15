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

class ActorFallPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ACTOR_FALL_PACKET;

	/** @var int */
	public $entityRuntimeId;
	/** @var float */
	public $fallDistance;
	/** @var bool */
	public $isInVoid;

	protected function decodePayload(){
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
	    	$this->entityRuntimeId = $this->getEntityRuntimeId();
	    	$this->fallDistance = $this->getLFloat();
	    	$this->isInVoid = $this->getBool();
		}else{
			$this->fallDistance = $this->getLFloat();
		}
	}

	protected function encodePayload(){
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
		    $this->putEntityRuntimeId($this->entityRuntimeId);
            $this->putLFloat($this->fallDistance);
            $this->putBool($this->isInVoid);
		}else{
            $this->putLFloat($this->fallDistance);
		}
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleActorFall($this);
	}
}
