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

class ScriptMessagePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SCRIPT_MESSAGE_PACKET;

	private string $messageId;
	private string $value;

	/**
	 * @generate-create-func
	 */
	public static function create(string $messageId, string $value) : self{
		$result = new self;
		$result->messageId = $messageId;
		$result->value = $value;
		return $result;
	}

	public function getMessageId() : string{ return $this->messageId; }

	public function getValue() : string{ return $this->value; }

	protected function decodePayload(){
		$this->messageId = $this->getString();
		$this->value = $this->getString();
	}

	protected function encodePayload(){
		$this->putString($this->messageId);
		$this->putString($this->value);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleScriptMessage($this);
	}
}
