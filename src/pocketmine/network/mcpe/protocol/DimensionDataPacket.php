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
use pocketmine\network\mcpe\protocol\types\DimensionData;
use pocketmine\network\mcpe\protocol\types\DimensionNameIds;
use function count;

/**
 * Sets properties of different dimensions of the world, such as its Y axis bounds and generator used
 */
class DimensionDataPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::DIMENSION_DATA_PACKET;

	/**
	 * @var DimensionData[]
	 * @phpstan-var array<DimensionNameIds::*, DimensionData>
	 */
	private array $definitions;

	/**
	 * @generate-create-func
	 * @param DimensionData[] $definitions
	 * @phpstan-param array<DimensionNameIds::*, DimensionData> $definitions
	 */
	public static function create(array $definitions) : self{
		$result = new self;
		$result->definitions = $definitions;
		return $result;
	}

	/**
	 * @return DimensionData[]
	 * @phpstan-return array<DimensionNameIds::*, DimensionData>
	 */
	public function getDefinitions() : array{ return $this->definitions; }

	protected function decodePayload(){
		$this->definitions = [];

		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; $i++){
			$dimensionNameId = $this->getString();
			$dimensionData = DimensionData::read($this);

			if(isset($this->definitions[$dimensionNameId])){
				throw new \InvalidArgumentException("Repeated dimension data for key \"$dimensionNameId\"");
			}
			if($dimensionNameId !== DimensionNameIds::OVERWORLD && $dimensionNameId !== DimensionNameIds::NETHER && $dimensionNameId !== DimensionNameIds::THE_END){
				throw new \InvalidArgumentException("Invalid dimension name ID \"$dimensionNameId\"");
			}
			$this->definitions[$dimensionNameId] = $dimensionData;
		}
	}

	protected function encodePayload(){
		$this->putUnsignedVarInt(count($this->definitions));

		foreach($this->definitions as $dimensionNameId => $definition){
			$this->putString((string) $dimensionNameId); //@phpstan-ignore-line
			$definition->write($this);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleDimensionData($this);
	}
}
