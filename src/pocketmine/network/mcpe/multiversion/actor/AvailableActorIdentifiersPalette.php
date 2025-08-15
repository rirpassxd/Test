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

namespace pocketmine\network\mcpe\multiversion\actor;

use function array_diff;
use function file_get_contents;
use function krsort;
use function scandir;
use const pocketmine\RESOURCE_PATH;

class AvailableActorIdentifiersPalette{
    /** @var string[] */
    protected static $actorIds = null;

    /**
     * @return void
     */
    public static function init() : void{
        self::$actorIds = [];
        $actorIdsDirectory = RESOURCE_PATH . '/vanilla/actor/';
        foreach(array_diff(scandir($actorIdsDirectory), ["..", "."]) as $protocol){
            self::$actorIds[$protocol] = file_get_contents($actorIdsDirectory . $protocol . '/entity_identifiers.nbt');
        }
        krsort(self::$actorIds);
    }

    /**
     * @param int $playerProtocol
     * 
     * @return string
     */
    public static function getActorIdsCache(int $playerProtocol) : string{
        foreach(self::$actorIds as $protocol => $cache){
            if($playerProtocol >= $protocol){
                return $cache;
            }
        }
        return "";
    }
}
