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
 * One of the most cursed packets to ever exist in Minecraft Bedrock Edition.
 *
 * This packet is only sent by the server when client-side chunk generation is enabled in vanilla. It contains NBT data
 * for biomes, similar to the BiomeDefinitionListPacket, but with a large amount of extra data for client-side chunk
 * generation.
 *
 * The data is compressed with a cursed home-brewed compression format, and it's a miracle it even works.
 * Hopefully this packet gets removed before I have to implement it...
 */
class CompressedBiomeDefinitionListPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::COMPRESSED_BIOME_DEFINITION_LIST_PACKET;

	/** @var string */
	private $payload;

	/**
	 * @generate-create-func
	 */
	public static function create(string $payload) : self{
		$result = new self;
		$result->payload = $payload;
		return $result;
	}

	public function getPayload() : string{ return $this->payload; }

	protected function decodePayload(){
		$this->payload = $this->getString();
	}

	protected function encodePayload(){
		$this->putString($this->payload);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCompressedBiomeDefinitionList($this);
	}
}
