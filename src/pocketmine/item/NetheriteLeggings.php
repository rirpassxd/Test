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

namespace pocketmine\item;

use pocketmine\inventory\ArmorInventory;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

class NetheriteLeggings extends Armor{
    public function __construct(int $meta = 0){
        parent::__construct(ItemIds::NETHERITE_LEGGINGS, $meta, "Netherite Leggings");
    }

    public function getDefensePoints() : int{
        return 6;
    }

    public function getMaxDurability() : int{
        return 556;
    }

    public function getArmorSlot() : int{
        return ArmorInventory::SLOT_LEGS;
    }

    public function getItemProtocol(int $protocol) : ?Item{
        if($protocol < ProtocolInfo::PROTOCOL_406){
            return ItemFactory::get(ItemIds::CHAIN_LEGGINGS, 0);
        }

        return parent::getItemProtocol($protocol);
    }
}