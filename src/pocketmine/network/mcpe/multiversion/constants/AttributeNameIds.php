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

use pocketmine\entity\Attribute;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class AttributeNameIds{
	private function __construct(){
		//NOOP
	}

	public const ATTRIBUTE_NAMES = [
	    ProtocolInfo::PROTOCOL_90 => [
	        Attribute::ABSORPTION => "minecraft:absorption",
	        Attribute::SATURATION => "minecraft:player.saturation",
	        Attribute::EXHAUSTION => "minecraft:player.exhaustion",
	        Attribute::KNOCKBACK_RESISTANCE => "minecraft:knockback_resistance",
	        Attribute::HEALTH => "minecraft:health",
	        Attribute::MOVEMENT_SPEED => "minecraft:movement",
	        Attribute::FOLLOW_RANGE => "minecraft:follow_range",
	        Attribute::HUNGER => "minecraft:player.hunger",
	        Attribute::ATTACK_DAMAGE => "minecraft:attack_damage",
	        Attribute::EXPERIENCE_LEVEL => "minecraft:player.level",
	        Attribute::EXPERIENCE => "minecraft:player.experience",
	        Attribute::UNDERWATER_MOVEMENT => "minecraft:underwater_movement",
	        Attribute::LUCK => "minecraft:luck",
	        Attribute::FALL_DAMAGE => "minecraft:fall_damage",
	        Attribute::HORSE_JUMP_STRENGTH => "minecraft:horse.jump_strength",
	        Attribute::ZOMBIE_SPAWN_REINFORCEMENTS => "minecraft:zombie.spawn_reinforcements"
	    ],
	    ProtocolInfo::PROTOCOL_81 => [
	        Attribute::ABSORPTION => "generic.absorption",
	        Attribute::SATURATION => "player.saturation",
	        Attribute::EXHAUSTION => "player.exhaustion",
	        Attribute::KNOCKBACK_RESISTANCE => "generic.knockbackResistance",
	        Attribute::HEALTH => "generic.health",
	        Attribute::MOVEMENT_SPEED => "generic.movementSpeed",
	        Attribute::FOLLOW_RANGE => "generic.followRange",
	        Attribute::HUNGER => "player.hunger",
	        Attribute::ATTACK_DAMAGE => "generic.attackDamage",
	        Attribute::EXPERIENCE_LEVEL => "player.level",
	        Attribute::EXPERIENCE => "player.experience"
	    ]
	];
}
