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

class AddPaintingPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_PAINTING_PACKET;

	/** @var int|null */
	public $entityUniqueId = null;
	/** @var int */
	public $entityRuntimeId;
	/** @var int */
	public $x;
	/** @var int */
	public $y;
	/** @var int */
	public $z;
	/** @var int */
	public $direction;
	/** @var string */
	public $title;

	protected function decodePayload(){
	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->entityUniqueId = $this->getEntityUniqueId();
	    }
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_360) {
	    	$position = $this->getVector3();
	    	$this->x = $position->x;
	    	$this->y = $position->y;
	    	$this->z = $position->z;
		} else {
		    $this->getBlockPosition($this->x, $this->y, $this->z);
		}
		$this->direction = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getVarInt() : $this->getInt();
		$this->title = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getString() : $this->getShortString();
	}

	protected function encodePayload(){
	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->putEntityUniqueId($this->entityUniqueId ?? $this->entityRuntimeId);
	    }
		$this->putEntityRuntimeId($this->entityRuntimeId);
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_360) {
		    $this->putVector3(new Vector3($this->x, $this->y, $this->z));
		} else {
		    $this->putBlockPosition($this->x, $this->y, $this->z);
		}
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
		    $this->putVarInt($this->direction);
	    	$this->putString($this->title);
		}else{
		    $this->putInt($this->direction);
		    $this->putShortString($this->title);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddPainting($this);
	}
}
