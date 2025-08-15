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

namespace pocketmine\network\mcpe\multiversion\inventory;

use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette419;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette431;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette440;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette448;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette465;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette475;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette486;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette503;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette526;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette534;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette560;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette567;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette575;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette582;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette589;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette594;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette618;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette630;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette649;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette662;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette671;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette685;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette712;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette729;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette748;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette766;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette776;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette786;
use pocketmine\network\mcpe\multiversion\inventory\palettes\ItemPalette800;
use pocketmine\network\mcpe\multiversion\inventory\palettes\Palette;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

class ItemPalette{
	/** @var Palette[] */
    protected static $palettes = null;

    /**
     * @return void
     */
    public static function init() : void{
        $pool = [
            ProtocolInfo::PROTOCOL_800 => new ItemPalette800(),
            ProtocolInfo::PROTOCOL_786 => new ItemPalette786(),
            ProtocolInfo::PROTOCOL_776 => new ItemPalette776(),
            ProtocolInfo::PROTOCOL_766 => new ItemPalette766(),
            ProtocolInfo::PROTOCOL_748 => new ItemPalette748(),
            ProtocolInfo::PROTOCOL_729 => new ItemPalette729(),
            ProtocolInfo::PROTOCOL_712 => new ItemPalette712(),
            ProtocolInfo::PROTOCOL_685 => new ItemPalette685(),
            ProtocolInfo::PROTOCOL_671 => new ItemPalette671(),
            ProtocolInfo::PROTOCOL_662 => new ItemPalette662(),
            ProtocolInfo::PROTOCOL_649 => new ItemPalette649(),
            ProtocolInfo::PROTOCOL_630 => new ItemPalette630(),
            ProtocolInfo::PROTOCOL_618 => new ItemPalette618(),
            ProtocolInfo::PROTOCOL_594 => new ItemPalette594(),
            ProtocolInfo::PROTOCOL_589 => new ItemPalette589(),
            ProtocolInfo::PROTOCOL_582 => new ItemPalette582(),
            ProtocolInfo::PROTOCOL_575 => new ItemPalette575(),
            ProtocolInfo::PROTOCOL_567 => new ItemPalette567(),
            ProtocolInfo::PROTOCOL_560 => new ItemPalette560(),
            ProtocolInfo::PROTOCOL_534 => new ItemPalette534(),
            ProtocolInfo::PROTOCOL_526 => new ItemPalette526(),
            ProtocolInfo::PROTOCOL_503 => new ItemPalette503(),
            ProtocolInfo::PROTOCOL_475 => new ItemPalette486(),
			ProtocolInfo::PROTOCOL_475 => new ItemPalette475(),
			ProtocolInfo::PROTOCOL_465 => new ItemPalette465(),
            ProtocolInfo::PROTOCOL_448 => new ItemPalette448(),
            ProtocolInfo::PROTOCOL_440 => new ItemPalette440(),
            ProtocolInfo::PROTOCOL_431 => new ItemPalette431(),
            ProtocolInfo::PROTOCOL_419 => new ItemPalette419()
        ];

        self::$palettes = [];
        foreach($pool as $protocol => $itemPalette){
            $itemPalette::init();
            self::$palettes[$protocol] = $itemPalette;
        }
    }

    /**
     * @param int $playerProtocol
     * 
     * @return Palette
     */
    public static function getPalette(int $playerProtocol) : Palette{
        foreach(self::$palettes as $protocol => $paletteClass){
            if($playerProtocol >= $protocol){
                return clone $paletteClass;
            }
        }
        return new ItemPalette419();
    }
}
