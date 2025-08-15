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

use InvalidArgumentException;
use JsonSerializable;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\CommandData;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use function count;
use function sprintf;

final class CommandListJson implements JsonSerializable{
    public const PERMISSION_NORMAL = 0;
    public const PERMISSION_OPERATOR = 1;
    public const PERMISSION_HOST = 2;
    public const PERMISSION_AUTOMATION = 3;
    public const PERMISSION_ADMIN = 4;

    public const MAGIC = "overload%d";

    /**
     * @param int $permission
     * 
     * @return string
     */
    public static function permName(int $permission) : string{
        switch($permission){
            case self::PERMISSION_NORMAL:
                return "any";
            case self::PERMISSION_OPERATOR:
                return "gamemasters";
            case self::PERMISSION_HOST:
                return "host";
            case self::PERMISSION_ADMIN:
                return "admin";
        }

        throw new InvalidArgumentException("Permission $permission not exists");
    }

    /** @var array */
    protected $commandData;

    public function __construct(array $commandData){
        $this->commandData = $commandData;
    }

    /**
     * @return array
     */
    public function jsonSerialize() : array{
        $json = [];
        foreach($this->commandData as $data){
            $json[$data->commandName] = self::dataJson($data);
        }

        return $json;
    }

    /**
     * @param CommandData $commandData
     * 
     * @return array
     */
    public static function dataJson(CommandData $commandData) : array{
        $data = [
            "description" => $commandData->commandDescription,
            "permission" => self::permName($commandData->permission)
        ];

        if($commandData->aliases instanceof CommandEnum){
            $data["aliases"] = $commandData->aliases->enumValues;
        }

        $data["overloads"] = [];
        if(count($commandData->overloads) === 0){
            $data["overloads"]["default"] = [
                "input" => [
                    "parameters" => []
                ]
            ];
        }else{
            foreach($commandData->overloads as $i => $overload){
                $overloadData = [
                    "input" => [
                        "parameters" => self::overloadJson($overload->getParameters())
                    ],
                    "output" => [
                        "parameters" => []
                    ]
                ];

                $newData = null;
                foreach($overload->getParameters() as $parameter){
                    if($parameter->postfix !== null){
                        $newData = [];
                        foreach($overload->getParameters() as $key => $value){
                            $newData[] = "\{$key\}" . ($value->postfix === null ? "" : $value->postfix);
                        }

                        break;
                    }
                }

                if($newData !== null){
                    $overloadData["parser"] = implode(" ", $newData);
                }

                $data["overloads"][sprintf(self::MAGIC, $i)] = $overloadData;
            }
        }

        return ["versions" => [
            $data
        ]];
    }

    /**
     * @param array $overload
     * 
     * @return array
     */
    public static function overloadJson(array $overload) : array{
        $jsonOverload = [];
        foreach($overload as $param){
            $jsonOverload[] = self::parameterJson($param);
        }

        return $jsonOverload;
    }

    /**
     * @param CommandParameter $parameter
     * 
     * @return array
     */
    public static function parameterJson(CommandParameter $parameter) : array{
        $data = ["name" => $parameter->paramName];
        $data["type"] = self::getParameterType($parameter, $data);
        if($parameter->enum !== null && $data["type"] === "stringenum"){
            static $paramTypes = [
                "EntityType" => "entityType",
                "Effect" => "effectType",
                "Enchant" => "enchantmentType",
                "Item" => "itemType",
                "Block" => "blockType",
                "BoolGameRule" => "gameRuleTypes",
                "IntGameRule" => "gameRuleTypes",
                "Feature" => "featureType"
            ];

            $data["type"] = "stringenum";
            $data["enum_type"] = $paramTypes[$parameter->enum->enumName] ?? $parameter->enum->enumName;

            if(count($parameter->enum->enumValues) > 0){
                $data["enum_values"] = $parameter->enum->enumValues;
            }
        }

        $data["optional"] = true;

        return $data;
    }

    /**
     * @param CommandParameter $parameter
     * @param array|null &$data
     * 
     * @return string
     */
    public static function getParameterType(CommandParameter $parameter, ?array &$data = []) : string{
        if($parameter->enum !== null){
            if($parameter->enum->enumName === "Boolean"){
                return "bool";
            }

            return "stringenum";
        }

        switch($parameter->paramType & 0xffff){
            case AvailableCommandsPacket::ARG_TYPE_INT:
                return "int";
            case AvailableCommandsPacket::ARG_TYPE_FLOAT:
                return "float";
            case AvailableCommandsPacket::ARG_TYPE_VALUE:
                return "rotation";
            case AvailableCommandsPacket::ARG_TYPE_TARGET:
                return "target";
            case AvailableCommandsPacket::ARG_TYPE_STRING:
                return "string";
            case AvailableCommandsPacket::ARG_TYPE_POSITION:
                return "blockpos";
            case AvailableCommandsPacket::ARG_TYPE_MESSAGE:
                return "rawtext";
            case AvailableCommandsPacket::ARG_TYPE_RAWTEXT:
                return "rawtext";
            case AvailableCommandsPacket::ARG_TYPE_JSON:
                return "components";
            case AvailableCommandsPacket::ARG_TYPE_COMMAND:
                $data["enum_type"] = "commandName";
                return "stringenum";
            default:
                return "unknown";
        }
    }
}
