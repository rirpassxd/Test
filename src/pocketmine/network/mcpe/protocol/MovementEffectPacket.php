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
use pocketmine\network\mcpe\protocol\types\MovementEffectType;

class MovementEffectPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::MOVEMENT_EFFECT_PACKET;

	private int $actorRuntimeId;
	private MovementEffectType $effectType;
	private int $duration;
	private int $tick;

	/**
	 * @generate-create-func
	 */
	public static function create(int $actorRuntimeId, MovementEffectType $effectType, int $duration, int $tick) : self{
		$result = new self;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->effectType = $effectType;
		$result->duration = $duration;
		$result->tick = $tick;

		return $result;
	}

	public function getActorRuntimeId() : int{ return $this->actorRuntimeId; }

	public function getEffectType() : MovementEffectType{ return $this->effectType; }

	public function getDuration() : int{ return $this->duration; }

	public function getTick() : int{ return $this->tick; }

	protected function decodePayload() : void{
		$this->actorRuntimeId = $this->getEntityRuntimeId();
		$this->effectType = MovementEffectType::fromPacket($this->getUnsignedVarInt());
		$this->duration = $this->getUnsignedVarInt();
		$this->tick = $this->getUnsignedVarLong();
	}

	protected function encodePayload() : void{
		$this->putEntityRuntimeId($this->actorRuntimeId);
		$this->putUnsignedVarInt($this->effectType->value);
		$this->putUnsignedVarInt($this->duration);
		$this->putUnsignedVarLong($this->tick);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleMovementEffect($this);
	}
}