<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\biome\BiomeDefinitionData;
use pocketmine\network\mcpe\protocol\types\biome\BiomeDefinitionEntry;
use InvalidArgumentException;
use function array_map;
use function count;

class BiomeDefinitionListPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::BIOME_DEFINITION_LIST_PACKET;

	/** @var string */
	private string $namedtag;

	/**
	 * @var BiomeDefinitionData[]
	 * @phpstan-var list<BiomeDefinitionData>
	 */
	private array $definitionData = [];

	/**
	 * @var string[]
	 * @phpstan-var list<string>
	 */
	private array $strings = [];

	/**
	 * @generate-create-func
	 * @param string                $namedtag
	 * @param BiomeDefinitionData[] $definitionData
	 * @param string[]              $strings
	 * @phpstan-param list<BiomeDefinitionData> $definitionData
	 * @phpstan-param list<string>              $strings
	 */
	public static function create(string $namedtag, array $definitionData = [], array $strings = []) : self{
		$result = new self;
		$result->namedtag = $namedtag;
		$result->definitionData = $definitionData;
		$result->strings = $strings;
		return $result;
	}

	/**
	 * @phpstan-param list<BiomeDefinitionEntry> $definitions
	 */
	public static function fromDefinitions(array $definitions) : self{
		/**
		 * @var int[]                      $stringIndexLookup
		 * @phpstan-var array<string, int> $stringIndexLookup
		 */
		$stringIndexLookup = [];
		$strings = [];
		$addString = function(string $string) use (&$stringIndexLookup, &$strings) : int{
			if(isset($stringIndexLookup[$string])){
				return $stringIndexLookup[$string];
			}

			$stringIndexLookup[$string] = count($stringIndexLookup);
			$strings[] = $string;
			return $stringIndexLookup[$string];
		};

		$definitionData = array_map(fn(BiomeDefinitionEntry $entry) => new BiomeDefinitionData(
			$addString($entry->getBiomeName()),
			$entry->getId(),
			$entry->getTemperature(),
			$entry->getDownfall(),
			$entry->getRedSporeDensity(),
			$entry->getBlueSporeDensity(),
			$entry->getAshDensity(),
			$entry->getWhiteAshDensity(),
			$entry->getDepth(),
			$entry->getScale(),
			$entry->getMapWaterColor(),
			$entry->hasRain(),
			$entry->getTags() === null ? null : array_map($addString, $entry->getTags()),
			$entry->getChunkGenData(),
		), $definitions);

		return self::create("", $definitionData, $strings);
	}

	/**
	 * @throws PacketDecodeException
	 */
	private function locateString(int $index) : string{
		return $this->strings[$index] ?? throw new InvalidArgumentException("Unknown string index $index");
	}

	/**
	 * Returns biome definition data with all string indexes resolved to actual strings.
	 *
	 * @return BiomeDefinitionEntry[]
	 * @phpstan-return list<BiomeDefinitionEntry>
	 *
	 * @throws PacketDecodeException
	 */
	public function buildDefinitionsFromData() : array{
		return array_map(fn(BiomeDefinitionData $data) => new BiomeDefinitionEntry(
			$this->locateString($data->getNameIndex()),
			$data->getId(),
			$data->getTemperature(),
			$data->getDownfall(),
			$data->getRedSporeDensity(),
			$data->getBlueSporeDensity(),
			$data->getAshDensity(),
			$data->getWhiteAshDensity(),
			$data->getDepth(),
			$data->getScale(),
			$data->getMapWaterColor(),
			$data->hasRain(),
			($tagIndexes = $data->getTagIndexes()) === null ? null : array_map($this->locateString(...), $tagIndexes),
			$data->getChunkGenData(),
		), $this->definitionData);
	}

	protected function decodePayload(){
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_800){
            for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			    $this->definitionData[] = BiomeDefinitionData::read($this);
		    }

	    	for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
		    	$this->strings[] = $this->getString();
	    	}
		}else{
		    $this->namedtag = $this->getRemaining();
		}
	}

	protected function encodePayload(){
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_800){
            $this->putUnsignedVarInt(count($this->definitionData));
		    foreach($this->definitionData as $data){
		    	$data->write($this);
    		}

		    $this->putUnsignedVarInt(count($this->strings));
		    foreach($this->strings as $string){
		    	$this->putString($string);
		    }
		}else{
		    $this->put($this->namedtag);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleBiomeDefinitionList($this);
	}
}
