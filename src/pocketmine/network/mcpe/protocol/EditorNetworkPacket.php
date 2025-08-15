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

use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkSession;

/**
 * Unclear purpose, not used in vanilla Bedrock. Seems to be related to a new Minecraft "editor" edition or mode.
 */
class EditorNetworkPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::EDITOR_NETWORK_PACKET;

    private bool $isRouteToManager;
	/** @phpstan-var CompoundTag */
	private CompoundTag $payload;

	/**
	 * @generate-create-func
	 * @phpstan-param CompoundTag $payload
	 */
	public static function create(bool $isRouteToManager, CompoundTag $payload) : self{
		$result = new self;
        $result->isRouteToManager = $isRouteToManager;
        $result->payload = $payload;
		return $result;
	}

	/** @phpstan-return CompoundTag */
	public function getPayload() : CompoundTag{ return $this->payload; }

    public function isRouteToManager() : bool{ return $this->isRouteToManager; }

	protected function decodePayload(){
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_712){
            $this->isRouteToManager = $this->getBool();
        }
		$this->payload = $this->getNbtCompoundRoot();
	}

	protected function encodePayload(){
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_712){
            $this->putBool($this->isRouteToManager);
        }
		$this->put((new NetworkLittleEndianNBTStream())->write($this->payload));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleEditorNetwork($this);
	}
}
