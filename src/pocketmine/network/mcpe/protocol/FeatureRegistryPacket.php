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
use pocketmine\network\mcpe\protocol\types\FeatureRegistryPacketEntry;
use function count;

/**
 * Syncs world generator settings from server to client, for client-sided chunk generation.
 */
class FeatureRegistryPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::FEATURE_REGISTRY_PACKET;

	/** @var FeatureRegistryPacketEntry[] */
	private array $entries;

	/**
	 * @generate-create-func
	 * @param FeatureRegistryPacketEntry[] $entries
	 */
	public static function create(array $entries) : self{
		$result = new self;
		$result->entries = $entries;
		return $result;
	}

	/** @return FeatureRegistryPacketEntry[] */
	public function getEntries() : array{ return $this->entries; }

	protected function decodePayload(){
		for($this->entries = [], $i = 0, $count = $this->getUnsignedVarInt(); $i < $count; $i++){
			$this->entries[] = FeatureRegistryPacketEntry::read($this);
		}
	}

	protected function encodePayload(){
		$this->putUnsignedVarInt(count($this->entries));
		foreach($this->entries as $entry){
			$entry->write($this);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleFeatureRegistry($this);
	}
}
