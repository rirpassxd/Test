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
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\AssumptionFailedError;
use function file_get_contents;
use const pocketmine\RESOURCE_PATH;

/**
 * @internal
 */
final class BlockPalette766 extends Palette{

	/** @var int[] */
	private static $legacyToRuntimeMap = [];
	/** @var int[] */
	private static $runtimeToLegacyMap = [];
	/** @var CompoundTag[]|null */
	private static $bedrockKnownStates = null;

	public function __construct(){
		//NOOP
	}

	public static function init() : void{
		$runtimeBlockStatesFile = file_get_contents(RESOURCE_PATH . "/vanilla/palette/766/runtime_block_states.dat");
		if($runtimeBlockStatesFile === false){
			throw new AssumptionFailedError("Missing required resource file");
		}

		$stream = (new BigEndianNBTStream())->readCompressed($runtimeBlockStatesFile)->getValue();
		self::$bedrockKnownStates = $stream;

		foreach($stream as $tag){
			$id = $tag->getInt("id");
			$meta = $tag->getShort("data");
			if($meta > 15){
				continue;
			}
			$runtimeId = $tag->getInt("runtimeId");

			self::registerMapping($runtimeId, $id, $meta);
		}
	}

	private static function lazyInit() : void{
		if(self::$bedrockKnownStates === null){
			self::init();
		}
	}

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
	 * @return CompoundTag[]
	 */
	public static function getBedrockKnownStates() : array{
		self::lazyInit();
		return self::$bedrockKnownStates;
	}
}
