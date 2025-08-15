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

class TickingAreasLoadStatusPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::TICKING_AREAS_LOAD_STATUS_PACKET;

	private bool $waitingForPreload;

	/**
	 * @generate-create-func
	 */
	public static function create(bool $waitingForPreload) : self{
		$result = new self;
		$result->waitingForPreload = $waitingForPreload;
		return $result;
	}

	public function isWaitingForPreload() : bool{ return $this->waitingForPreload; }

	protected function decodePayload(){
		$this->waitingForPreload = $this->getBool();
	}

	protected function encodePayload(){
		$this->putBool($this->waitingForPreload);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleTickingAreasLoadStatus($this);
	}
}
