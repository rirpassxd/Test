<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\OverrideUpdateType;
use LogicException;

class PlayerUpdateEntityOverridesPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_UPDATE_ENTITY_OVERRIDES_PACKET;

	private int $actorRuntimeId;
	private int $propertyIndex;
	private OverrideUpdateType $updateType;
	private ?int $intOverrideValue;
	private ?float $floatOverrideValue;

	/**
	 * @generate-create-func
	 */
	private static function create(int $actorRuntimeId, int $propertyIndex, OverrideUpdateType $updateType, ?int $intOverrideValue, ?float $floatOverrideValue) : self{
		$result = new self;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->propertyIndex = $propertyIndex;
		$result->updateType = $updateType;
		$result->intOverrideValue = $intOverrideValue;
		$result->floatOverrideValue = $floatOverrideValue;
		return $result;
	}

	public static function createIntOverride(int $actorRuntimeId, int $propertyIndex, int $value) : self{
		return self::create($actorRuntimeId, $propertyIndex, OverrideUpdateType::SET_INT_OVERRIDE, $value, null);
	}

	public static function createFloatOverride(int $actorRuntimeId, int $propertyIndex, float $value) : self{
		return self::create($actorRuntimeId, $propertyIndex, OverrideUpdateType::SET_FLOAT_OVERRIDE, null, $value);
	}

	public static function createClearOverrides(int $actorRuntimeId, int $propertyIndex) : self{
		return self::create($actorRuntimeId, $propertyIndex, OverrideUpdateType::CLEAR_OVERRIDES, null, null);
	}

	public static function createRemoveOverride(int $actorRuntimeId, int $propertyIndex) : self{
		return self::create($actorRuntimeId, $propertyIndex, OverrideUpdateType::REMOVE_OVERRIDE, null, null);
	}

	public function getActorRuntimeId() : int{ return $this->actorRuntimeId; }

	public function getPropertyIndex() : int{ return $this->propertyIndex; }

	public function getUpdateType() : OverrideUpdateType{ return $this->updateType; }

	public function getIntOverrideValue() : ?int{ return $this->intOverrideValue; }

	public function getFloatOverrideValue() : ?float{ return $this->floatOverrideValue; }

	protected function decodePayload() : void{
		$this->actorRuntimeId = $this->getActorRuntimeId();
		$this->propertyIndex = $this->getUnsignedVarInt();
		$this->updateType = OverrideUpdateType::fromPacket($this->getByte());
		if($this->updateType === OverrideUpdateType::SET_INT_OVERRIDE){
			$this->intOverrideValue = $this->getLInt();
		}elseif($this->updateType === OverrideUpdateType::SET_FLOAT_OVERRIDE){
			$this->floatOverrideValue = $this->getLFloat();
		}
	}

	protected function encodePayload() : void{
		$this->putActorRuntimeId($this->actorRuntimeId);
		$this->putUnsignedVarInt($this->propertyIndex);
		$this->putByte($this->updateType->value);
		if($this->updateType === OverrideUpdateType::SET_INT_OVERRIDE){
			if($this->intOverrideValue === null){ // this should never be the case
				throw new LogicException("PlayerUpdateEntityOverridesPacket with type SET_INT_OVERRIDE require an intOverrideValue to be provided");
			}
			$this->putLInt($this->intOverrideValue);
		}elseif($this->updateType === OverrideUpdateType::SET_FLOAT_OVERRIDE){
			if($this->floatOverrideValue === null){ // this should never be the case
				throw new LogicException("PlayerUpdateEntityOverridesPacket with type SET_INT_OVERRIDE require an intOverrideValue to be provided");
			}
			$this->putLFloat($this->floatOverrideValue);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerUpdateEntityOverridesPacket($this);
	}
}
