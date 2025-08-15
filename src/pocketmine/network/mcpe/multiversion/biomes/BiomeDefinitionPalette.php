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

namespace pocketmine\network\mcpe\multiversion\biomes;

use pocketmine\utils\Color;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\types\biome\BiomeDefinitionEntry;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use RuntimeException;
use function array_diff;
use function scandir;
use function file_get_contents;
use function json_decode;
use function krsort;
use function count;
use const pocketmine\RESOURCE_PATH;

class BiomeDefinitionPalette{
    /** @var BiomeDefinitionListPacket[] */
    protected static $biomeDefs = null;

    /**
     * @return void
     */
    public static function init() : void{
        self::$biomeDefs = [];
        $biomeDefsDirectory = RESOURCE_PATH . '/vanilla/biomes/';
        foreach(array_diff(scandir($biomeDefsDirectory), ["..", "."]) as $protocol){
            $protocol = (int) $protocol;
            if($protocol >= ProtocolInfo::PROTOCOL_800){
                $data = json_decode(file_get_contents($biomeDefsDirectory . $protocol . '/biome_definitions.json'), true);

				$entries = [];
                foreach($data as $biomeName => $biomeDefinition){
                    $mapWaterColour = $biomeDefinition["mapWaterColour"];
			    	$entries[] = new BiomeDefinitionEntry(
				    	(string) $biomeName,
				    	$biomeDefinition["id"],
				    	$biomeDefinition["temperature"],
				    	$biomeDefinition["downfall"],
				    	$biomeDefinition["redSporeDensity"],
				    	$biomeDefinition["blueSporeDensity"],
				    	$biomeDefinition["ashDensity"],
				    	$biomeDefinition["whiteAshDensity"],
				    	$biomeDefinition["depth"],
					    $biomeDefinition["scale"],
					    new Color(
					    	$mapWaterColour["r"],
					    	$mapWaterColour["g"],
					    	$mapWaterColour["b"],
					    	$mapWaterColour["a"]
				    	),
				    	$biomeDefinition["rain"],
				    	count($biomeDefinition["tags"]) > 0 ? $biomeDefinition["tags"] : null,
			    	);
                }
                $packet = BiomeDefinitionListPacket::fromDefinitions($entries);
            }else{
                $packet = BiomeDefinitionListPacket::create(file_get_contents($biomeDefsDirectory . $protocol . '/biome_definitions.nbt'));
            }
            self::$biomeDefs[$protocol] = $packet;
        }
        krsort(self::$biomeDefs);
    }

    /**
     * @param int $playerProtocol
     * 
     * @return BiomeDefinitionListPacket
     */
    public static function getBiomeDefinitionListPacket(int $playerProtocol) : BiomeDefinitionListPacket{
        foreach(self::$biomeDefs as $protocol => $biomeDefinitionList){
            if($playerProtocol >= $protocol){
                return $biomeDefinitionList;
            }
        }
        throw new RuntimeException("Not founded biome definition list packet for protocol $playerProtocol, MCBE library need to update");
    }
}
