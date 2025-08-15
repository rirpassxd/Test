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
use function chr;
use function ord;
use function pack;
use function unpack;

class MoveActorDeltaPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::MOVE_ACTOR_DELTA_PACKET;

	public const FLAG_HAS_X = 0x01;
	public const FLAG_HAS_Y = 0x02;
	public const FLAG_HAS_Z = 0x04;
	public const FLAG_HAS_ROT_X = 0x08;
	public const FLAG_HAS_ROT_Y = 0x10;
	public const FLAG_HAS_ROT_Z = 0x20;

	/** @var int */
	public $entityRuntimeId;
	/** @var int */
	public $flags;
	/** @var float|int */
	public $xPos = 0;
	/** @var float|int */
	public $yPos = 0;
	/** @var float|int */
	public $zPos = 0;
	/** @var float */
	public $xRot = 0.0;
	/** @var float */
	public $yRot = 0.0;
	/** @var float */
	public $zRot = 0.0;

	private function maybeReadCoord(int $flag) {
		if($this->flags & $flag){
		    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
		        return $this->getLFloat();
		    }else{
		    	return $this->getVarInt();
		    }
		}
		return 0;
	}

	private function maybeReadRotation(int $flag) : float{
		if($this->flags & $flag){
			return $this->getByteRotation();
		}
		return 0.0;
	}

	protected function decodePayload(){
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_370){
		    $this->flags = $this->getLShort();
		}else{
	    	$this->flags = $this->getByte();
		}
		$this->xPos = $this->maybeReadCoord(self::FLAG_HAS_X);
		$this->yPos = $this->maybeReadCoord(self::FLAG_HAS_Y);
		$this->zPos = $this->maybeReadCoord(self::FLAG_HAS_Z);
		$this->xRot = $this->maybeReadRotation(self::FLAG_HAS_ROT_X);
		$this->yRot = $this->maybeReadRotation(self::FLAG_HAS_ROT_Y);
		$this->zRot = $this->maybeReadRotation(self::FLAG_HAS_ROT_Z);
	}

	private function maybeWriteCoord(int $flag, $val) : void{
		if($this->flags & $flag){
		    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
                $this->putLFloat($val);
		    }else{
		    	$this->putVarInt($val);
		    }
		}
	}

	private function maybeWriteRotation(int $flag, float $val) : void{
		if($this->flags & $flag){
			$this->putByteRotation($val);
		}
	}

	protected function encodePayload(){
		$this->putEntityRuntimeId($this->entityRuntimeId);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_370){
            $this->putLShort($this->flags);
		}else{
            $this->putByte($this->flags);
		}
		$this->maybeWriteCoord(self::FLAG_HAS_X, $this->xPos);
		$this->maybeWriteCoord(self::FLAG_HAS_Y, $this->yPos);
		$this->maybeWriteCoord(self::FLAG_HAS_Z, $this->zPos);
		$this->maybeWriteRotation(self::FLAG_HAS_ROT_X, $this->xRot);
		$this->maybeWriteRotation(self::FLAG_HAS_ROT_Y, $this->yRot);
		$this->maybeWriteRotation(self::FLAG_HAS_ROT_Z, $this->zRot);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleMoveActorDelta($this);
	}
}
