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
use function json_decode;
use const pocketmine\RESOURCE_PATH;

/**
 * @internal
 */
final class BlockPalette270 extends Palette{

	/** @var int[] */
	private static $staticRuntimeIdMap = [];
	/** @var int[] */
	private static $legacyIdMap = [];
    /** @var string|null */
    private static $encodedPalette = null;

	public function __construct(){
		//NOOP
	}

	public static function init() : void{
		/** @var mixed[] $runtimeIdMap */
		$runtimeIdMap = json_decode(file_get_contents(RESOURCE_PATH . "/vanilla/palette/270/BlockPalette.json"), true);
		foreach($runtimeIdMap as $obj){
			self::registerMapping($obj["runtimeID"], $obj["id"], $obj["data"]);
		}

		$stream = new NetworkBinaryStream();
		$stream->putUnsignedVarInt(count($runtimeIdMap));
		foreach($runtimeIdMap as $v){
			$stream->putString($v["name"]);
			$stream->putLShort($v["data"]);
		}
		self::$encodedPalette = $stream->getBuffer();
	}

	private static function lazyInit() : void{
		if(self::$encodedPalette === null){
			self::init();
		}
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
		return self::$staticRuntimeIdMap[($id << 4) | $meta] ?? self::$staticRuntimeIdMap[$id << 4] ?? self::$staticRuntimeIdMap[BlockIds::INFO_UPDATE << 4];
	}

	/**
	 * @param int $runtimeId
	 *
	 * @return int[] [id, meta]
	 */
	public static function fromStaticRuntimeId(int $runtimeId) : array{
	    self::lazyInit();
	    if(isset(self::$legacyIdMap[$runtimeId])){
	    	$v = self::$legacyIdMap[$runtimeId];
	    	return [$v >> 4, $v & 0xf];
    	}else{
    	    return [0, 0];
    	}
	}

	private static function registerMapping(int $staticRuntimeId, int $legacyId, int $legacyMeta) : void{
		self::$staticRuntimeIdMap[($legacyId << 4) | $legacyMeta] = $staticRuntimeId;
		self::$legacyIdMap[$staticRuntimeId] = ($legacyId << 4) | $legacyMeta;
	}

	/**
	 * @return string
	 */
	public static function getEncodedPalette() : string{
	    self::lazyInit();
		return self::$encodedPalette;
	}
}