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

/**
 * This is the first packet sent in a game session. It contains the client's protocol version.
 * The server is expected to respond to this with network settings, which will instruct the client which compression
 * type to use, amongst other things.
 */
class RequestNetworkSettingsPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::REQUEST_NETWORK_SETTINGS_PACKET;

	private int $protocolVersion;

	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	/**
	 * @generate-create-func
	 */
	public static function create(int $protocolVersion) : self{
		$result = new self;
		$result->protocolVersion = $protocolVersion;
		return $result;
	}

	public function getProtocolVersion() : int{ return $this->protocolVersion; }

	protected function decodeHeader(){
	    $this->getUnsignedVarInt();
	}

	protected function decodePayload() : void{
		$this->protocolVersion = $this->getInt();
	}

	protected function encodePayload() : void{
		$this->putInt($this->protocolVersion);
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleRequestNetworkSettings($this);
	}
}
