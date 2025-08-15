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
use function count;

class ExplodePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::EXPLODE_PACKET;

	/** @var Vector3 */
	public $position;
	/** @var float */
	public $radius;
	/** @var Vector3[] */
	public $records = [];

	public function clean(){
		$this->records = [];
		return parent::clean();
	}

	protected function decodePayload(){
		$this->position = $this->getVector3();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
	    	$this->radius = (float) ($this->getVarInt() / 32);
		}else{
			$this->radius = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getLFloat() : $this->getFloat();
		}
		$count = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getUnsignedVarInt() : $this->getInt();
		for($i = 0; $i < $count; ++$i){
			$x = $y = $z = 0;
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
		    	$this->getSignedBlockPosition($x, $y, $z);
			}else{
			    $x = $this->getByte();
			    $y = $this->getByte();
			    $z = $this->getByte();
			}
			$this->records[$i] = new Vector3($x, $y, $z);
		}
	}

	protected function encodePayload(){
		$this->putVector3($this->position);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
	    	$this->putVarInt((int) ($this->radius * 32));
		}else{
		    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
		    	$this->putLFloat($this->radius);
		    }else{
		        $this->putFloat($this->radius);
		    }
		}
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->putUnsignedVarInt(count($this->records));
		}else{
		    $this->putInt(count($this->records));
		}
		if(count($this->records) > 0){
			foreach($this->records as $record){
			    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
			    	$this->putSignedBlockPosition((int) $record->x, (int) $record->y, (int) $record->z);
			    }else{
			        $this->putByte((int) $record->x);
			    	$this->putByte((int) $record->y);
			    	$this->putByte((int) $record->z);
			    }
			}
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleExplode($this);
	}
}
