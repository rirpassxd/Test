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

namespace pocketmine\network\mcpe\multiversion;

use pocketmine\entity\Entity;
use pocketmine\network\mcpe\multiversion\utils\ActorMetadataConvertor;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use ReflectionClass;

final class MetadataConvertor{
    public static function init() : void{
        ActorMetadataConvertor::init(
            (new ReflectionClass(EntityMetadataProperties::class))->getConstants(),
            (new ReflectionClass(EntityMetadataFlags::class))->getConstants()
        );
    }

    /**
     * @param array $metadata
     * @param int $playerProtocol
     * 
     * @return array
     */
    public static function updateMeta(array $metadata, int $playerProtocol) : array{
        $flags = $flags2 = null;

        if(isset($metadata[EntityMetadataProperties::DATA_FLAGS])){
            $flags = $metadata[EntityMetadataProperties::DATA_FLAGS][1];
            unset($metadata[EntityMetadataProperties::DATA_FLAGS][1]);
        }
        if(isset($metadata[EntityMetadataProperties::DATA_FLAGS2])){
            $flags2 = $metadata[EntityMetadataProperties::DATA_FLAGS2][1];
            unset($metadata[EntityMetadataProperties::DATA_FLAGS2][1]);
        }

        if($flags !== null){
            $metadata[EntityMetadataProperties::DATA_ALWAYS_SHOW_NAMETAG] = [Entity::DATA_TYPE_BYTE, ($flags & (1 << EntityMetadataFlags::DATA_FLAG_ALWAYS_SHOW_NAMETAG)) > 0 ? 1 : 0];
            if($playerProtocol < ProtocolInfo::PROTOCOL_90){
                $metadata[EntityMetadataProperties::DATA_NO_AI] = [Entity::DATA_TYPE_BYTE, ($flags & (1 << EntityMetadataFlags::DATA_FLAG_IMMOBILE)) > 0 ? 1 : 0];
                $metadata[EntityMetadataProperties::DATA_SHOW_NAMETAG] = [Entity::DATA_TYPE_BYTE, ($flags & (1 << EntityMetadataFlags::DATA_FLAG_ALWAYS_SHOW_NAMETAG)) > 0 ? 1 : 0];
                $metadata[EntityMetadataProperties::DATA_SILENT] = [Entity::DATA_TYPE_BYTE, ($flags & (1 << EntityMetadataFlags::DATA_FLAG_SILENT)) > 0 ? 1 : 0];
                $metadata[EntityMetadataProperties::DATA_FLAGS][0] = Entity::DATA_TYPE_BYTE;
            }
        }

        (ActorMetadataConvertor::getMetadataFlags($playerProtocol))($flags, $flags2);
        if($flags !== null){
            $metadata[EntityMetadataProperties::DATA_FLAGS][1] = $flags;
        }else{
            unset($metadata[EntityMetadataProperties::DATA_FLAGS]);
        }
        if($flags2 !== null){
            $metadata[EntityMetadataProperties::DATA_FLAGS2][1] = $flags2;
        }else{
            unset($metadata[EntityMetadataProperties::DATA_FLAGS2]);
        }
        if($playerProtocol < ProtocolInfo::PROTOCOL_370 && isset($metadata[EntityMetadataProperties::DATA_TARGET_EID]) && $metadata[EntityMetadataProperties::DATA_TARGET_EID][1] === 0){
            $metadata[EntityMetadataProperties::DATA_TARGET_EID][1] = -1;
        }

        return (ActorMetadataConvertor::getMetadataProperties($playerProtocol))($metadata);
    }

    /**
     * @param array $metadata
     * @param int $playerProtocol
     * 
     * @return array
     */
    public static function rollbackMeta(array $metadata, int $playerProtocol) : array{
        $metadata = (ActorMetadataConvertor::getMetadataProperties($playerProtocol, true))($metadata);
        $flags = $flags2 = null;

        if(isset($metadata[EntityMetadataProperties::DATA_FLAGS])){
            $flags = $metadata[EntityMetadataProperties::DATA_FLAGS][1];
            unset($metadata[EntityMetadataProperties::DATA_FLAGS][1]);
        }
        if(isset($metadata[EntityMetadataProperties::DATA_FLAGS2])){
            $flags2 = $metadata[EntityMetadataProperties::DATA_FLAGS2][1];
            unset($metadata[EntityMetadataProperties::DATA_FLAGS2][1]);
        }

        if($flags !== null){
            if($playerProtocol < ProtocolInfo::PROTOCOL_90){
                $metadata[EntityMetadataProperties::DATA_FLAGS][0] = Entity::DATA_TYPE_LONG;
            }
        }

        (ActorMetadataConvertor::getMetadataFlags($playerProtocol, true))($flags, $flags2);
        if($flags !== null){
            $metadata[EntityMetadataProperties::DATA_FLAGS][1] = $flags;
        }else{
            unset($metadata[EntityMetadataProperties::DATA_FLAGS]);
        }
        if($flags2 !== null){
            $metadata[EntityMetadataProperties::DATA_FLAGS2][1] = $flags2;
        }else{
            unset($metadata[EntityMetadataProperties::DATA_FLAGS2]);
        }
        if($playerProtocol < ProtocolInfo::PROTOCOL_370 && isset($metadata[EntityMetadataProperties::DATA_TARGET_EID]) && $metadata[EntityMetadataProperties::DATA_TARGET_EID][1] === -1){
            $metadata[EntityMetadataProperties::DATA_TARGET_EID][1] = 0;
        }

        return $metadata;
    }
}
