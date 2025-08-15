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

namespace pocketmine\network\mcpe\cache;

use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeGroupEntry;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeItemEntry;
use pocketmine\utils\SingletonTrait;
use function file_get_contents;
use function json_decode;
use function array_diff;
use function scandir;
use function array_keys;
use function krsort;
use const DIRECTORY_SEPARATOR;
use const pocketmine\RESOURCE_PATH;

final class CreativePacketCache{
    use SingletonTrait;

    /** @var int[]  */
    public static array $COUNT_ITEMS = [];
    /** @var int[]  */
    public static array $MAX_GROUP_ITEMS = [];

    private static function make() : self{
        $groups = $items  = [];

        $groupsDirectory = RESOURCE_PATH . DIRECTORY_SEPARATOR . "vanilla" . DIRECTORY_SEPARATOR . "creative_groups/";
        $itemsDirectory = RESOURCE_PATH . DIRECTORY_SEPARATOR . "vanilla" . DIRECTORY_SEPARATOR . "creative_items/";

        foreach(array_diff(scandir($groupsDirectory), ["..", "."]) as $protocol){
            $itemsProtocol = json_decode(file_get_contents($groupsDirectory . $protocol . '/creative_group.json'), true);

            $creativeGroupEntry = [];
            foreach($itemsProtocol as $group){
                $creativeGroupEntry[] = new CreativeGroupEntry(
                    (int) $group["category_id"],
                    $group["category_name"],
                    isset($group["icon"]) ? Item::jsonDeserialize($group["icon"]) : ItemFactory::get(Item::AIR)
                );

            }

            $groups[$protocol] = $creativeGroupEntry;
        }

        foreach(array_diff(scandir($itemsDirectory), ["..", "."]) as $protocol){
            if(!isset(self::$COUNT_ITEMS[$protocol])){
                self::$COUNT_ITEMS[$protocol] = 0;
            }

            if(!isset(self::$MAX_GROUP_ITEMS[$protocol])){
                self::$MAX_GROUP_ITEMS[$protocol] = 0;
            }

            $itemsProtocol = json_decode(file_get_contents($itemsDirectory . $protocol . '/creativeitems.json'), true);

            $creativeItemEntry = [];
            foreach($itemsProtocol as $itemJson){
                $item = Item::jsonDeserialize($itemJson);
                if($item->getName() === "Unknown"){
                    continue;
                }

                $groupId = isset($itemJson["groupId"]) ? (int) $itemJson["groupId"] : ($protocol < ProtocolInfo::PROTOCOL_406 ? CreativeContentPacket::CREATIVE_GROUP_NONE : 0);
                if($groupId > self::$MAX_GROUP_ITEMS[$protocol]){
                    self::$MAX_GROUP_ITEMS[$protocol] = $groupId;
                }

                $creativeItemEntry[] = new CreativeItemEntry(
                    ++self::$COUNT_ITEMS[$protocol],
                    $item,
                    $groupId
                );
            }

            $items[$protocol] = $creativeItemEntry;
        }

        krsort($groups);
        krsort($items);

        return new self($groups, $items);
    }

    /**
     * @param CreativeGroupEntry[][] $groups
     * @param CreativeItemEntry[][] $items
     */
    public function __construct(
        private array $groups = [],
        private array $items = []
    ) {}

    public function getGroups(int $protocolVersion) : array{
        foreach($this->groups as $protocol => $groups){
            if($protocolVersion >= $protocol){
                return $groups;
            }
        }

        return [];
    }

    public function getItems(int $protocolVersion) : array{
        foreach($this->items as $protocol => $items){
            if($protocolVersion >= $protocol){
                return $items;
            }
        }

        return [];
    }

    public function clearItems(?int $protocolVersion = null) : void{
        if($protocolVersion === null){
            foreach($this->items as $protocol => $items){
                if($protocolVersion >= $protocol){
                    unset($this->items[$protocol]);
                }
            }
        }else{
            foreach($this->items as $protocol => $items){
                unset($this->items[$protocol]);
            }
        }

        krsort($this->items);
    }

    public function addItem(Item $item, ?int $groupId = null, ?int $protocolVersion = null) : void{
        $addItemClosure = function(int $protocol, Item $item, ?int $groupId) : void {
            if(!isset(self::$COUNT_ITEMS[$protocol])){
                self::$COUNT_ITEMS[$protocol] = 0;
            }

            if(!isset(self::$MAX_GROUP_ITEMS[$protocol])){
                self::$MAX_GROUP_ITEMS[$protocol] = 0;
            }

            $groupId = $groupId ?? ($protocol < ProtocolInfo::PROTOCOL_406 ? CreativeContentPacket::CREATIVE_GROUP_NONE : ++self::$MAX_GROUP_ITEMS[$protocol]);

            $creativeItemEntry = new CreativeItemEntry(++self::$COUNT_ITEMS[$protocol], $item, $groupId);

            $this->items[$protocol][] = $creativeItemEntry;
        };

        if($protocolVersion !== null){
            foreach($this->items as $protocol => $items){
                if($protocolVersion >= $protocol){
                    $addItemClosure($protocol, $item, $groupId);
                }
            }
        }else{
            foreach($this->items as $protocol => $items){
                $addItemClosure($protocol, $item, $groupId);
            }
        }

        krsort($this->items);
    }

    public function removeItem(Item $item, ?int $protocolVersion = null) : void {
        if($protocolVersion !== null){
            foreach($this->items as $protocol => $items){
                if($protocolVersion >= $protocol){
                    foreach($items as $index => $itemEntry){
                        if($item->equals($itemEntry->getItem(), !($item instanceof Durable))){
                            unset($this->items[$protocol][$index]);
                        }
                    }
                }
            }
        }else{
            foreach($this->items as $protocol => $items){
                foreach($items as $index => $itemEntry){
                    if($item->equals($itemEntry->getItem(), !($item instanceof Durable))){
                        unset($this->items[$protocol][$index]);
                    }
                }
            }
        }

        krsort($this->items);
    }

    public function getItemIndex(Item $item, ?int $protocolVersion = null) : int{
        if($protocolVersion !== null){
            foreach($this->items as $protocol => $items){
                if($protocolVersion >= $protocol){
                    foreach($items as $index => $itemEntry){
                        if($item->equals($itemEntry->getItem(), !($item instanceof Durable))){
                            return $index;
                        }
                    }
                }
            }
        }else{
            foreach($this->items as $protocol => $items){
                foreach($items as $index => $itemEntry){
                    if($item->equals($itemEntry->getItem(), !($item instanceof Durable))){
                        return $index;
                    }
                }
            }
        }

        return -1;
    }

    /**
     * @return int[]
     */
    public function getCreativeItemProtocols() : array{
        return array_keys($this->items);
    }
}