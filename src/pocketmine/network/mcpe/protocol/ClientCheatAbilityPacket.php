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
use pocketmine\network\mcpe\protocol\types\AbilitiesData;

class ClientCheatAbilityPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENT_CHEAT_ABILITY_PACKET;

	private AbilitiesData $data;

	/**
	 * @generate-create-func
	 */
	public static function create(AbilitiesData $data) : self{
		$result = new self;
		$result->data = $data;
		return $result;
	}

	public function getData() : AbilitiesData{ return $this->data; }

	protected function decodePayload() : void{
		$this->data = AbilitiesData::decode($this);
	}

	protected function encodePayload() : void{
		$this->data->encode($this);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleClientCheatAbility($this);
	}
}
