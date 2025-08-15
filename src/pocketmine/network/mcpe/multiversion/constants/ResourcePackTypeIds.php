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

namespace pocketmine\network\mcpe\multiversion\constants;

use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class ResourcePackTypeIds{
	private function __construct(){
		//NOOP
	}

	public const RESOURCE_PACK_TYPE_IDS = [
		ProtocolInfo::PROTOCOL_370 => [
			"INVALID" => 0,
			"ADDON" => 1,
			"CACHED" => 2,
			"COPY_PROTECTED" => 3,
			"BEHAVIORS" => 4,
			"PERSONA_PIECE" => 5,
			"RESOURCES" => 6,
			"SKINS" => 7,
			"WORLD_TEMPLATE" => 8,
			"COUNT" => 9
		],
		ProtocolInfo::PROTOCOL_360 => [
			"INVALID" => 0,
			"RESOURCES" => 1,
			"BEHAVIORS" => 2,
			"WORLD_TEMPLATE" => 3,
			"ADDON" => 4,
			"SKINS" => 5,
			"CACHED" => 6,
			"COPY_PROTECTED" => 7,
			"COUNT" => 8
		]
	];
}
