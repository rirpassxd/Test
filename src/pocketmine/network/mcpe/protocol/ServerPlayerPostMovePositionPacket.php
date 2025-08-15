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

class ServerPlayerPostMovePositionPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SERVER_PLAYER_POST_MOVE_POSITION_PACKET;

	private Vector3 $position;

	/**
	 * @generate-create-func
	 */
	public static function create(Vector3 $position) : self{
		$result = new self;
		$result->position = $position;
		return $result;
	}

	public function getPosition() : Vector3{ return $this->position; }

	protected function decodePayload(){
		$this->position = $this->getVector3();
	}

	protected function encodePayload(){
		$this->putVector3($this->position);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleServerPlayerPostMovePosition($this);
	}
}
