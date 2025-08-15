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

use pocketmine\network\mcpe\protocol\serializer\BitSet;
use pocketmine\network\mcpe\NetworkSession;
use InvalidArgumentException;

class ClientMovementPredictionSyncPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENT_MOVEMENT_PREDICTION_SYNC_PACKET;

	private BitSet $flags;

	private float $scale;
	private float $width;
	private float $height;

	private float $movementSpeed;
	private float $underwaterMovementSpeed;
	private float $lavaMovementSpeed;
	private float $jumpStrength;
	private float $health;
	private float $hunger;

	private int $actorUniqueId;
	private bool $actorFlyingState;

	/**
	 * @generate-create-func
	 */
	private static function internalCreate(
		BitSet $flags,
		float $scale,
		float $width,
		float $height,
		float $movementSpeed,
		float $underwaterMovementSpeed,
		float $lavaMovementSpeed,
		float $jumpStrength,
		float $health,
		float $hunger,
		int $actorUniqueId,
		bool $actorFlyingState,
	) : self{
		$result = new self;
		$result->flags = $flags;
		$result->scale = $scale;
		$result->width = $width;
		$result->height = $height;
		$result->movementSpeed = $movementSpeed;
		$result->underwaterMovementSpeed = $underwaterMovementSpeed;
		$result->lavaMovementSpeed = $lavaMovementSpeed;
		$result->jumpStrength = $jumpStrength;
		$result->health = $health;
		$result->hunger = $hunger;
		$result->actorUniqueId = $actorUniqueId;
		$result->actorFlyingState = $actorFlyingState;
		return $result;
	}

	public static function create(
		BitSet $flags,
		float $scale,
		float $width,
		float $height,
		float $movementSpeed,
		float $underwaterMovementSpeed,
		float $lavaMovementSpeed,
		float $jumpStrength,
		float $health,
		float $hunger,
		int $actorUniqueId,
		bool $actorFlyingState,
	) : self{
		return self::internalCreate($flags, $scale, $width, $height, $movementSpeed, $underwaterMovementSpeed, $lavaMovementSpeed, $jumpStrength, $health, $hunger, $actorUniqueId, $actorFlyingState);
	}

	public function getFlags() : BitSet{ return $this->flags; }

	public function getScale() : float{ return $this->scale; }

	public function getWidth() : float{ return $this->width; }

	public function getHeight() : float{ return $this->height; }

	public function getMovementSpeed() : float{ return $this->movementSpeed; }

	public function getUnderwaterMovementSpeed() : float{ return $this->underwaterMovementSpeed; }

	public function getLavaMovementSpeed() : float{ return $this->lavaMovementSpeed; }

	public function getJumpStrength() : float{ return $this->jumpStrength; }

	public function getHealth() : float{ return $this->health; }

	public function getHunger() : float{ return $this->hunger; }

	public function getActorUniqueId() : int{ return $this->actorUniqueId; }

    public function getActorFlyingState() : bool{ return $this->actorFlyingState; }

	protected function decodePayload() : void{
		$this->flags = BitSet::read($this, $this->getProtocol() >= ProtocolInfo::PROTOCOL_800 ? 124 : (
			$this->getProtocol() >= ProtocolInfo::PROTOCOL_786 ? 123 : 120
		));
		$this->scale = $this->getLFloat();
		$this->width = $this->getLFloat();
		$this->height = $this->getLFloat();
		$this->movementSpeed = $this->getLFloat();
		$this->underwaterMovementSpeed = $this->getLFloat();
		$this->lavaMovementSpeed = $this->getLFloat();
		$this->jumpStrength = $this->getLFloat();
		$this->health = $this->getLFloat();
		$this->hunger = $this->getLFloat();
		$this->actorUniqueId = $this->getActorUniqueId();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_786){
		    $this->actorFlyingState = $this->getBool();
		}
	}

	protected function encodePayload() : void{
		$this->flags->write($this);
		$this->putLFloat($this->scale);
		$this->putLFloat($this->width);
		$this->putLFloat($this->height);
		$this->putLFloat($this->movementSpeed);
		$this->putLFloat($this->underwaterMovementSpeed);
		$this->putLFloat($this->lavaMovementSpeed);
		$this->putLFloat($this->jumpStrength);
		$this->putLFloat($this->health);
		$this->putLFloat($this->hunger);
		$this->putActorUniqueId($this->actorUniqueId);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_786){
		    $this->putBool($this->actorFlyingState);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleClientMovementPredictionSync($this);
	}
}