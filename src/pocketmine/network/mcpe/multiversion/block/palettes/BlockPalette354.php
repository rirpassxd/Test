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

namespace pocketmine\network\mcpe\multiversion\block\palettes;

use pocketmine\block\BlockIds;
use pocketmine\network\mcpe\NetworkBinaryStream;
use function count;
use function file_get_contents;
use function getmypid;
use function json_decode;
use function mt_rand;
use function mt_srand;
use function shuffle;
use const pocketmine\RESOURCE_PATH;

/**
 * @internal
 */
final class BlockPalette354 extends Palette{

	/** @var int[] */
	private static $legacyToRuntimeMap = [];
	/** @var int[] */
	private static $runtimeToLegacyMap = [];
	/** @var mixed[]|null */
	private static $bedrockKnownStates = null;
    /** @var string|null */
    private static $encodedPalette = null;

	public function __construct(){
		//NOOP
	}

	public static function init() : void{
		$legacyIdMap = json_decode(file_get_contents(RESOURCE_PATH . "/vanilla/palette/block_id_map354.json"), true);

		$compressedTable = json_decode(file_get_contents(RESOURCE_PATH . "/vanilla/palette/354/BlockPalette.json"), true);
		$decompressed = [];

		foreach($compressedTable as $prefix => $entries){
			foreach($entries as $shortStringId => $states){
				foreach($states as $state){
					$decompressed[] = [
						"name" => "$prefix:$shortStringId",
						"data" => $state
					];
				}
			}
		}
		self::$bedrockKnownStates = self::randomizeTable($decompressed);

		foreach(self::$bedrockKnownStates as $k => $obj){
			//this has to use the json offset to make sure the mapping is consistent with what we send over network, even though we aren't using all the entries
			if(!isset($legacyIdMap[$obj["name"]])){
				continue;
			}
			self::registerMapping($k, $legacyIdMap[$obj["name"]], $obj["data"]);
		}

		$stream = new NetworkBinaryStream();
		$stream->putUnsignedVarInt(count(self::$bedrockKnownStates));
		foreach(self::$bedrockKnownStates as $v){
			$stream->putString($v["name"]);
			$stream->putLShort($v["data"]);
		}
		self::$encodedPalette = $stream->getBuffer();
	}

	private static function lazyInit() : void{
		if(self::$bedrockKnownStates === null){
			self::init();
		}
	}

	/**
	 * Randomizes the order of the runtimeID table to prevent plugins relying on them.
	 * Plugins shouldn't use this stuff anyway, but plugin devs have an irritating habit of ignoring what they
	 * aren't supposed to do, so we have to deliberately break it to make them stop.
	 *
	 * @param array $table
	 *
	 * @return array
	 */
	private static function randomizeTable(array $table) : array{
		$postSeed = mt_rand(); //save a seed to set afterwards, to avoid poor quality randoms
		mt_srand(getmypid() ?: 0); //Use a seed which is the same on all threads. This isn't a secure seed, but we don't care.
		shuffle($table);
		mt_srand($postSeed); //restore a good quality seed that isn't dependent on PID
		return $table;
	}

	/**
	 * @param int $id
	 * @param int $meta
	 *
	 * @return int
	 */
	public static function toStaticRuntimeId(int $id, int $meta = 0) : int{
	    self::lazyInit();
		/*
		 * try id+meta first
		 * if not found, try id+0 (strip meta)
		 * if still not found, return update! block
		 */
		return self::$legacyToRuntimeMap[($id << 4) | $meta] ?? self::$legacyToRuntimeMap[$id << 4] ?? self::$legacyToRuntimeMap[BlockIds::INFO_UPDATE << 4];
	}

	/**
	 * @param int $runtimeId
	 *
	 * @return int[] [id, meta]
	 */
	public static function fromStaticRuntimeId(int $runtimeId) : array{
	    self::lazyInit();
		if(isset(self::$runtimeToLegacyMap[$runtimeId])){
	    	$v = self::$runtimeToLegacyMap[$runtimeId];
	    	return [$v >> 4, $v & 0xf];
		}else{
		    return [0, 0];
		}
	}

	private static function registerMapping(int $staticRuntimeId, int $legacyId, int $legacyMeta) : void{
		self::$legacyToRuntimeMap[($legacyId << 4) | $legacyMeta] = $staticRuntimeId;
		self::$runtimeToLegacyMap[$staticRuntimeId] = ($legacyId << 4) | $legacyMeta;
	}

	/**
	 * @return array
	 */
	public static function getBedrockKnownStates() : array{
	    self::lazyInit();
		return self::$bedrockKnownStates;
	}

	/**
	 * @return string
	 */
	public static function getEncodedPalette() : string{
	    self::lazyInit();
		return self::$encodedPalette;
	}
}