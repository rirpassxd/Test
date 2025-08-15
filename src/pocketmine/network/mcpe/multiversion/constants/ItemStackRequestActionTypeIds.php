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

final class ItemStackRequestActionTypeIds{
	private function __construct(){
		//NOOP
	}

    public const ITEM_STACK_REQUEST_ACTION_TYPE_IDS = [
        ProtocolInfo::PROTOCOL_712 => [
	        "TAKE" => 0,
	        "PLACE" => 1,
	        "SWAP" => 2,
	        "DROP" => 3,
	        "DESTROY" => 4,
	        "CRAFTING_CONSUME_INPUT" => 5,
	        "CRAFTING_CREATE_SPECIFIC_RESULT" => 6,
            "LAB_TABLE_COMBINE" => 9,
            "BEACON_PAYMENT" => 10,
            "MINE_BLOCK" => 11,
            "CRAFTING_RECIPE" => 12,
            "CRAFTING_RECIPE_AUTO" => 13,
            "CREATIVE_CREATE" => 14,
            "CRAFTING_RECIPE_OPTIONAL" => 15,
            "CRAFTING_GRINDSTONE" => 16,
            "CRAFTING_LOOM" => 17,
            "CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING" => 18,
            "CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING" => 19
        ],
        ProtocolInfo::PROTOCOL_486 => [
	        "TAKE" => 0,
	        "PLACE" => 1,
	        "SWAP" => 2,
	        "DROP" => 3,
	        "DESTROY" => 4,
	        "CRAFTING_CONSUME_INPUT" => 5,
	        "CRAFTING_CREATE_SPECIFIC_RESULT" => 6,
            "PLACE_INTO_BUNDLE" => 7,
            "TAKE_FROM_BUNDLE" => 8,
            "LAB_TABLE_COMBINE" => 9,
            "BEACON_PAYMENT" => 10,
            "MINE_BLOCK" => 11,
            "CRAFTING_RECIPE" => 12,
            "CRAFTING_RECIPE_AUTO" => 13,
            "CREATIVE_CREATE" => 14,
            "CRAFTING_RECIPE_OPTIONAL" => 15,
            "CRAFTING_GRINDSTONE" => 16,
            "CRAFTING_LOOM" => 17,
            "CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING" => 18,
            "CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING" => 19
        ],
        ProtocolInfo::PROTOCOL_428 => [
	        "TAKE" => 0,
	        "PLACE" => 1,
	        "SWAP" => 2,
	        "DROP" => 3,
	        "DESTROY" => 4,
	        "CRAFTING_CONSUME_INPUT" => 5,
	        "CRAFTING_CREATE_SPECIFIC_RESULT" => 6,
	        "LAB_TABLE_COMBINE" => 7,
	        "BEACON_PAYMENT" => 8,
            "MINE_BLOCK" => 9,
            "CRAFTING_RECIPE" => 10,
            "CRAFTING_RECIPE_AUTO" => 11,
            "CREATIVE_CREATE" => 12,
            "CRAFTING_RECIPE_OPTIONAL" => 13,
            "CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING" => 14,
            "CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING" => 15
        ],
        ProtocolInfo::PROTOCOL_422 => [
	        "TAKE" => 0,
	        "PLACE" => 1,
	        "SWAP" => 2,
	        "DROP" => 3,
	        "DESTROY" => 4,
	        "CRAFTING_CONSUME_INPUT" => 5,
	        "CRAFTING_CREATE_SPECIFIC_RESULT" => 6,
	        "LAB_TABLE_COMBINE" => 7,
	        "BEACON_PAYMENT" => 8,
	        "CRAFTING_RECIPE" => 9,
	        "CRAFTING_RECIPE_AUTO" => 10,
	        "CREATIVE_CREATE" => 11,
            "CRAFTING_RECIPE_OPTIONAL" => 12,
	        "CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING" => 13,
	        "CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING" => 14
        ],
        ProtocolInfo::PROTOCOL_401 => [
	        "TAKE" => 0,
	        "PLACE" => 1,
	        "SWAP" => 2,
	        "DROP" => 3,
	        "DESTROY" => 4,
	        "CRAFTING_CONSUME_INPUT" => 5,
	        "CRAFTING_CREATE_SPECIFIC_RESULT" => 6,
	        "LAB_TABLE_COMBINE" => 7,
	        "BEACON_PAYMENT" => 8,
	        "CRAFTING_RECIPE" => 9,
	        "CRAFTING_RECIPE_AUTO" => 10,
	        "CREATIVE_CREATE" => 11,
	        "CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING" => 12,
	        "CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING" => 13
        ]
    ];
}
