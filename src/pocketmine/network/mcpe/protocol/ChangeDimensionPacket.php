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


use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;

class ChangeDimensionPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CHANGE_DIMENSION_PACKET;

	/** @var int */
	public $dimension;
	/** @var Vector3 */
	public $position;
	/** @var bool */
	public $respawn = false;
    /** @var ?int */
    public $loadingScreenId = null;

	protected function decodePayload(){
		$this->dimension = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getVarInt() : $this->getByte();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->position = $this->getVector3();
		}
		$this->respawn = $this->getBool();
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_712){
            $this->loadingScreenId = $this->readOptional(fn() => $this->getLInt());
        }
	}

	protected function encodePayload(){
	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->putVarInt($this->dimension);
	    	$this->putVector3($this->position);
	    }else{
	        $this->putByte($this->dimension);
	    }
        $this->putBool($this->respawn);
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_712){
            $this->writeOptional($this->loadingScreenId, $this->putLInt(...));
        }
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleChangeDimension($this);
	}
}
