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

class PlayerStartItemCooldownPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_START_ITEM_COOLDOWN_PACKET;

	private string $itemCategory;
	private int $cooldownTicks;

	/**
	 * @generate-create-func
	 */
	public static function create(string $itemCategory, int $cooldownTicks) : self{
		$result = new self;
		$result->itemCategory = $itemCategory;
		$result->cooldownTicks = $cooldownTicks;
		return $result;
	}

	public function getItemCategory() : string{ return $this->itemCategory; }

	public function getCooldownTicks() : int{ return $this->cooldownTicks; }

	protected function decodePayload(){
		$this->itemCategory = $this->getString();
		$this->cooldownTicks = $this->getVarInt();
	}

	protected function encodePayload(){
		$this->putString($this->itemCategory);
		$this->putVarInt($this->cooldownTicks);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerStartItemCooldown($this);
	}
}
