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

final class CommandArgumentTypeIds{
	private function __construct(){
		//NOOP
	}

	public const COMMAND_ARGUMENT_TYPE_IDS = [
		ProtocolInfo::PROTOCOL_662 => [
            "ARG_TYPE_INT" => 1,
			"ARG_TYPE_FLOAT" => 3,
            "ARG_TYPE_VALUE" => 4,
            "ARG_TYPE_WILDCARD_INT" => 5,
            "ARG_TYPE_OPERATOR" => 6,
            "ARG_TYPE_COMPARE_OPERATOR" => 7,
            "ARG_TYPE_TARGET" => 8,
            "ARG_TYPE_WILDCARD_TARGET" => 10,
            "ARG_TYPE_FILEPATH" => 17,
            "ARG_TYPE_FULL_INTEGER_RANGE" => 23,
            "ARG_TYPE_EQUIPMENT_SLOT" => 47,
            "ARG_TYPE_STRING" => 48,
            "ARG_TYPE_INT_POSITION" => 64,
            "ARG_TYPE_POSITION" => 65,
            "ARG_TYPE_MESSAGE" => 67,
            "ARG_TYPE_RAWTEXT" => 70,
            "ARG_TYPE_JSON" => 74,
            "ARG_TYPE_BLOCK_STATES" => 84,
            "ARG_TYPE_COMMAND" => 87
		],
		ProtocolInfo::PROTOCOL_582 => [
            "ARG_TYPE_INT" => 1,
			"ARG_TYPE_FLOAT" => 3,
            "ARG_TYPE_VALUE" => 4,
            "ARG_TYPE_WILDCARD_INT" => 5,
            "ARG_TYPE_OPERATOR" => 6,
            "ARG_TYPE_COMPARE_OPERATOR" => 7,
            "ARG_TYPE_TARGET" => 8,
            "ARG_TYPE_WILDCARD_TARGET" => 10,
            "ARG_TYPE_FILEPATH" => 17,
            "ARG_TYPE_FULL_INTEGER_RANGE" => 23,
            "ARG_TYPE_EQUIPMENT_SLOT" => 43,
            "ARG_TYPE_STRING" => 44,
            "ARG_TYPE_INT_POSITION" => 52,
            "ARG_TYPE_POSITION" => 53,
            "ARG_TYPE_MESSAGE" => 55,
            "ARG_TYPE_RAWTEXT" => 58,
            "ARG_TYPE_JSON" => 62,
            "ARG_TYPE_BLOCK_STATES" => 71,
            "ARG_TYPE_COMMAND" => 74
		],
		ProtocolInfo::PROTOCOL_526 => [
			"ARG_TYPE_INT" => 0x01,
			"ARG_TYPE_FLOAT" => 0x03,
			"ARG_TYPE_VALUE" => 0x04,
			"ARG_TYPE_WILDCARD_INT" => 0x05,
			"ARG_TYPE_OPERATOR" => 0x06,
			"ARG_TYPE_COMPARE_OPERATOR" => 0x07,
			"ARG_TYPE_TARGET" => 0x08,
			"ARG_TYPE_WILDCARD_TARGET" => 0x0a,
			"ARG_TYPE_FILEPATH" => 0x11,
			"ARG_TYPE_FULL_INTEGER_RANGE" => 0x17,
			"ARG_TYPE_EQUIPMENT_SLOT" => 0x26,
			"ARG_TYPE_STRING" => 0x27,
			"ARG_TYPE_INT_POSITION" => 0x2f,
			"ARG_TYPE_POSITION" => 0x30,
			"ARG_TYPE_MESSAGE" => 0x33,
			"ARG_TYPE_RAWTEXT" => 0x35,
			"ARG_TYPE_JSON" => 0x39,
			"ARG_TYPE_BLOCK_STATES" => 0x43,
			"ARG_TYPE_COMMAND" => 0x46
		],
		ProtocolInfo::PROTOCOL_503 => [
			"ARG_TYPE_INT" => 0x01,
			"ARG_TYPE_FLOAT" => 0x03,
			"ARG_TYPE_VALUE" => 0x04,
			"ARG_TYPE_WILDCARD_INT" => 0x05,
			"ARG_TYPE_OPERATOR" => 0x06,
			"ARG_TYPE_TARGET" => 0x07,
			"ARG_TYPE_WILDCARD_TARGET" => 0x09,
			"ARG_TYPE_FILEPATH" => 0x10,
			"ARG_TYPE_EQUIPMENT_SLOT" => 0x25,
			"ARG_TYPE_STRING" => 0x26,
			"ARG_TYPE_INT_POSITION" => 0x2e,
			"ARG_TYPE_POSITION" => 0x2f,
			"ARG_TYPE_MESSAGE" => 0x32,
			"ARG_TYPE_RAWTEXT" => 0x34,
			"ARG_TYPE_JSON" => 0x38,
			"ARG_TYPE_COMMAND" => 0x45
		],
		ProtocolInfo::PROTOCOL_428 => [
			"ARG_TYPE_INT" => 0x01,
			"ARG_TYPE_FLOAT" => 0x03,
			"ARG_TYPE_VALUE" => 0x04,
			"ARG_TYPE_WILDCARD_INT" => 0x05,
			"ARG_TYPE_OPERATOR" => 0x06,
			"ARG_TYPE_TARGET" => 0x07,
			"ARG_TYPE_WILDCARD_TARGET" => 0x08,
			"ARG_TYPE_FILEPATH" => 0x10,
			"ARG_TYPE_STRING" => 0x20,
			"ARG_TYPE_POSITION" => 0x28,
			"ARG_TYPE_MESSAGE" => 0x2c,
			"ARG_TYPE_RAWTEXT" => 0x2e,
			"ARG_TYPE_JSON" => 0x32,
			"ARG_TYPE_COMMAND" => 0x3f
		],
		ProtocolInfo::PROTOCOL_385 => [
			"ARG_TYPE_INT" => 0x01,
			"ARG_TYPE_FLOAT" => 0x02,
			"ARG_TYPE_VALUE" => 0x03,
			"ARG_TYPE_WILDCARD_INT" => 0x04,
			"ARG_TYPE_OPERATOR" => 0x05,
			"ARG_TYPE_TARGET" => 0x06,
			"ARG_TYPE_WILDCARD_TARGET" => 0x07,
			"ARG_TYPE_FILEPATH" => 0x0e,
			"ARG_TYPE_STRING" => 0x1d,
			"ARG_TYPE_POSITION" => 0x25,
			"ARG_TYPE_MESSAGE" => 0x29,
			"ARG_TYPE_RAWTEXT" => 0x2b,
			"ARG_TYPE_JSON" => 0x2f,
			"ARG_TYPE_COMMAND" => 0x36
		],
		ProtocolInfo::PROTOCOL_370 => [
			"ARG_TYPE_INT" => 0x01,
			"ARG_TYPE_FLOAT" => 0x02,
			"ARG_TYPE_VALUE" => 0x03,
			"ARG_TYPE_WILDCARD_INT" => 0x04,
			"ARG_TYPE_OPERATOR" => 0x05,
			"ARG_TYPE_TARGET" => 0x06,
			"ARG_TYPE_WILDCARD_TARGET" => 0x07,
			"ARG_TYPE_FILEPATH" => 0x0e,
			"ARG_TYPE_STRING" => 0x1b,
			"ARG_TYPE_POSITION" => 0x23,
			"ARG_TYPE_MESSAGE" => 0x27,
			"ARG_TYPE_RAWTEXT" => 0x29,
			"ARG_TYPE_JSON" => 0x2c,
			"ARG_TYPE_COMMAND" => 0x33
		],
		ProtocolInfo::PROTOCOL_340 => [
			"ARG_TYPE_INT" => 0x01,
			"ARG_TYPE_FLOAT" => 0x02,
			"ARG_TYPE_VALUE" => 0x03,
			"ARG_TYPE_WILDCARD_INT" => 0x04,
			"ARG_TYPE_OPERATOR" => 0x05,
			"ARG_TYPE_TARGET" => 0x06,
			"ARG_TYPE_WILDCARD_TARGET" => 0x07,
			"ARG_TYPE_FILEPATH" => 0x0e,
			"ARG_TYPE_STRING" => 0x1b,
			"ARG_TYPE_POSITION" => 0x1d,
			"ARG_TYPE_MESSAGE" => 0x20,
			"ARG_TYPE_RAWTEXT" => 0x22,
			"ARG_TYPE_JSON" => 0x25,
			"ARG_TYPE_COMMAND" => 0x2c
		],
		ProtocolInfo::PROTOCOL_332 => [
			"ARG_TYPE_INT" => 0x01,
			"ARG_TYPE_FLOAT" => 0x02,
			"ARG_TYPE_VALUE" => 0x03,
			"ARG_TYPE_WILDCARD_INT" => 0x04,
			"ARG_TYPE_OPERATOR" => 0x05,
			"ARG_TYPE_TARGET" => 0x06,
			"ARG_TYPE_WILDCARD_TARGET" => 0x07,
			"ARG_TYPE_FILEPATH" => 0x0f,
			"ARG_TYPE_STRING" => 0x1c,
			"ARG_TYPE_POSITION" => 0x1e,
			"ARG_TYPE_MESSAGE" => 0x21,
			"ARG_TYPE_RAWTEXT" => 0x23,
			"ARG_TYPE_JSON" => 0x26,
			"ARG_TYPE_COMMAND" => 0x2d
		],
		ProtocolInfo::PROTOCOL_270 => [
			"ARG_TYPE_INT" => 0x01,
			"ARG_TYPE_FLOAT" => 0x02,
			"ARG_TYPE_VALUE" => 0x03,
			"ARG_TYPE_WILDCARD_INT" => 0x04,
			"ARG_TYPE_TARGET" => 0x05,
			"ARG_TYPE_WILDCARD_TARGET" => 0x06,
			"ARG_TYPE_STRING" => 0x0f,
			"ARG_TYPE_POSITION" => 0x10,
			"ARG_TYPE_MESSAGE" => 0x13,
			"ARG_TYPE_RAWTEXT" => 0x15,
			"ARG_TYPE_JSON" => 0x18,
			"ARG_TYPE_COMMAND" => 0x1f
		],
		ProtocolInfo::PROTOCOL_136 => [
			"ARG_TYPE_INT" => 0x01,
			"ARG_TYPE_FLOAT" => 0x02,
			"ARG_TYPE_VALUE" => 0x03,
			"ARG_TYPE_TARGET" => 0x04,
			"ARG_TYPE_STRING" => 0x0d,
			"ARG_TYPE_POSITION" => 0x0e,
			"ARG_TYPE_RAWTEXT" => 0x11,
			"ARG_TYPE_TEXT" => 0x13,
			"ARG_TYPE_JSON" => 0x16,
			"ARG_TYPE_COMMAND" => 0x1d
		],
		ProtocolInfo::PROTOCOL_130 => [
			"ARG_TYPE_INT" => 0x01,
			"ARG_TYPE_FLOAT" => 0x02,
			"ARG_TYPE_VALUE" => 0x03,
			"ARG_TYPE_TARGET" => 0x04,
			"ARG_TYPE_STRING" => 0x0c,
			"ARG_TYPE_POSITION" => 0x0d,
			"ARG_TYPE_RAWTEXT" => 0x10,
			"ARG_TYPE_TEXT" => 0x12,
			"ARG_TYPE_JSON" => 0x15,
			"ARG_TYPE_COMMAND" => 0x1c
		]
	];
}
