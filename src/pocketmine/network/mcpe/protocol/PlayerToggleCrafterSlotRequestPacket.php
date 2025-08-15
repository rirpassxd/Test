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

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;

class PlayerToggleCrafterSlotRequestPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_TOGGLE_CRAFTER_SLOT_REQUEST_PACKET;

	private Vector3 $position;
	private int $slot;
	private bool $disabled;

	/**
	 * @generate-create-func
	 */
	public static function create(Vector3 $position, int $slot, bool $disabled) : self{
		$result = new self;
		$result->position = $position;
		$result->slot = $slot;
		$result->disabled = $disabled;
		return $result;
	}

	public function getPosition() : Vector3{ return $this->position; }

	public function getSelectedSlot() : int{ return $this->slot; }

	public function isDisabled() : bool{ return $this->disabled; }

	protected function decodePayload() : void{
		$x = $this->getLInt();
		$y = $this->getLInt();
		$z = $this->getLInt();
		$this->position = new Vector3($x, $y, $z);
		$this->slot = $this->getByte();
		$this->disabled = $this->getBool();
	}

	protected function encodePayload() : void{
		$this->putLInt($this->position->getX());
		$this->putLInt($this->position->getY());
		$this->putLInt($this->position->getZ());
		$this->putByte($this->slot);
		$this->putBool($this->disabled);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerToggleCrafterSlotRequest($this);
	}
}
