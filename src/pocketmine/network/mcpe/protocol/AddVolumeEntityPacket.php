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

#include <rules/DataPacket.h>

use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkSession;

class AddVolumeEntityPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_VOLUME_ENTITY_PACKET;

	/** @var int */
	private $entityNetId;
	/** @var CompoundTag */
	private $data;
    /** @var string */
	private $jsonIdentifier;
    /** @var string */
	private $instanceName;
	/** @var int */
	private $minX;
	/** @var int */
	private $minY;
	/** @var int */
	private $minZ;
	/** @var int */
	private $maxX;
	/** @var int */
	private $maxY;
	/** @var int */
	private $maxZ;
	/** @var int */
	private $dimension;
    /** @var string */
    private $engineVersion;

	public static function create(
	    int $entityNetId,
	    CompoundTag $data,
	    string $jsonIdentifier,
	    string $instanceName,
	    Vector3 $minBound,
	    Vector3 $maxBound,
	    int $dimension,
	    string $engineVersion
	) : self{
		$result = new self;
		$result->entityNetId = $entityNetId;
		$result->data = $data;
		$result->jsonIdentifier = $jsonIdentifier;
		$result->instanceName = $instanceName;
		[$result->minX, $result->minY, $result->minZ] = [$minBound->x, $minBound->y, $minBound->z];
		[$result->maxX, $result->maxY, $result->maxZ] = [$maxBound->x, $maxBound->y, $maxBound->z];
		$result->dimension = $dimension;
		$result->engineVersion = $engineVersion;
		return $result;
	}

	public function getEntityNetId() : int{ return $this->entityNetId; }

	public function getData() : CompoundTag{ return $this->data; }

	public function getJsonIdentifier() : string{ return $this->jsonIdentifier; }

	public function getInstanceName() : string{ return $this->instanceName; }

	public function getMinBound() : Vector3{ return new Vector3($this->minX, $this->minY, $this->minZ); }

	public function getMaxBound() : Vector3{ return new Vector3($this->maxX, $this->maxY, $this->maxZ); }

	public function getDimension() : int{ return $this->dimension; }

    public function getEngineVersion() : string{ return $this->engineVersion; }

	protected function decodePayload() : void{
		$this->entityNetId = $this->getUnsignedVarInt();
		$this->data = $this->getNbtCompoundRoot();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_465){
		    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_486){
	        	$this->jsonIdentifier = $this->getString();
	        	$this->instanceName = $this->getString();
	        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_503){
	        	    $this->getBlockPosition($this->minX, $this->minY, $this->minZ);
	        	    $this->getBlockPosition($this->maxX, $this->maxY, $this->maxZ);
	        	    $this->dimension = $this->getVarInt();
	        	}
		    }
			$this->engineVersion = $this->getString();
		}
	}

	protected function encodePayload() : void{
		$this->putUnsignedVarInt($this->entityNetId);
		$this->put((new NetworkLittleEndianNBTStream())->write($this->data));
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_465){
		    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_486){
                $this->putString($this->jsonIdentifier);
                $this->putString($this->instanceName);
	        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_503){
	            	$this->putBlockPosition($this->minX, $this->minY, $this->minZ);
	            	$this->putBlockPosition($this->maxX, $this->maxY, $this->maxZ);
	            	$this->putVarInt($this->dimension);
	        	}
		    }
			$this->putString($this->engineVersion);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddVolumeEntity($this);
	}
}
