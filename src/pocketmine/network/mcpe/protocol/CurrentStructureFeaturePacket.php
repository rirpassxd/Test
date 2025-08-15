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

class CurrentStructureFeaturePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CURRENT_STRUCTURE_FEATURE_PACKET;

	public string $currentStructureFeature;

	/**
	 * @generate-create-func
	 */
	public static function create(string $currentStructureFeature) : self{
		$result = new self;
		$result->currentStructureFeature = $currentStructureFeature;
		return $result;
	}

	protected function decodePayload() : void{
		$this->currentStructureFeature = $this->getString();
	}

	protected function encodePayload() : void{
		$this->putString($this->currentStructureFeature);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCurrentStructureFeature($this);
	}

    public function mustBeDecoded() : bool{
        return true;
    }
}
