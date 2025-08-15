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

class JigsawStructureDataPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::JIGSAW_STRUCTURE_DATA_PACKET;

	/** @phpstan-var CompoundTag */
	private CompoundTag $nbt;

	/**
	 * @generate-create-func
	 * @phpstan-param CompoundTag $nbt
	 */
	public static function create(CompoundTag $nbt) : self{
		$result = new self;
		$result->nbt = $nbt;
		return $result;
	}

	/** @phpstan-return CompoundTag */
	public function getNbt() : CompoundTag{ return $this->nbt; }

	protected function decodePayload() : void{
		$this->nbt = $this->getNbtCompoundRoot();
	}

	protected function encodePayload() : void{
        $this->put((new NetworkLittleEndianNBTStream())->write($this->nbt));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleJigsawStructureData($this);
	}
}
