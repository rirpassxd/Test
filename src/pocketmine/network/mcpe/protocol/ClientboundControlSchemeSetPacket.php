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
use pocketmine\network\mcpe\protocol\types\ControlScheme;

class ClientboundControlSchemeSetPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_CONTROL_SCHEME_SET_PACKET;

	private ControlScheme $scheme;

	/**
	 * @generate-create-func
	 */
	public static function create(ControlScheme $scheme) : self{
		$result = new self;
		$result->scheme = $scheme;
		return $result;
	}

	public function getScheme() : ControlScheme{ return $this->scheme; }

	protected function decodePayload() : void{
		$this->scheme = ControlScheme::fromPacket($this->getByte());
	}

	protected function encodePayload() : void{
		$this->putByte($this->scheme->value);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleClientboundControlSchemeSet($this);
	}
}
