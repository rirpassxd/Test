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

class UpdateClientInputLocksPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::UPDATE_CLIENT_INPUT_LOCKS_PACKET;

	private int $flags;
	private Vector3 $position;

	/**
	 * @generate-create-func
	 */
	public static function create(int $flags, Vector3 $position) : self{
		$result = new self;
		$result->flags = $flags;
		$result->position = $position;
		return $result;
	}

	protected function decodePayload() : void{
		$this->flags = $this->getUnsignedVarInt();
		$this->position = $this->getVector3();
	}

	protected function encodePayload() : void{
        $this->putUnsignedVarInt($this->flags);
        $this->putVector3($this->position);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdateClientInputLocks($this);
	}
}
