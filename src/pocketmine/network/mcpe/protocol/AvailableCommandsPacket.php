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

namespace pocketmine\network\mcpe\protocol;

use InvalidStateException;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\multiversion\CommandListJson;
use pocketmine\network\mcpe\multiversion\MultiversionEnums;
use pocketmine\network\mcpe\protocol\types\ChainedSubCommandData;
use pocketmine\network\mcpe\protocol\types\ChainedSubCommandValue;
use pocketmine\network\mcpe\protocol\types\CommandData;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandEnumConstraint;
use pocketmine\network\mcpe\protocol\types\CommandOverload;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use pocketmine\utils\BinaryDataException;
use UnexpectedValueException;
use function chr;
use function count;
use function dechex;
use function json_encode;
use function ord;
use function pack;
use function unpack;

class AvailableCommandsPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::AVAILABLE_COMMANDS_PACKET;


	/**
	 * This flag is set on all types EXCEPT the POSTFIX type. Not completely sure what this is for, but it is required
	 * for the argtype to work correctly. VALID seems as good a name as any.
	 */
	public const ARG_FLAG_VALID = 0x100000;

	/**
	 * Basic parameter types. These must be combined with the ARG_FLAG_VALID constant.
	 * ARG_FLAG_VALID | (type const)
	 */
	public const ARG_TYPE_INT = 0x01;
	public const ARG_TYPE_FLOAT = 0x03;
	public const ARG_TYPE_VALUE = 0x04;
	public const ARG_TYPE_WILDCARD_INT = 0x05;
	public const ARG_TYPE_OPERATOR = 0x06;
	public const ARG_TYPE_COMPARE_OPERATOR = 0x07;
	public const ARG_TYPE_TARGET = 0x08;

	public const ARG_TYPE_WILDCARD_TARGET = 0x0a;

	public const ARG_TYPE_FILEPATH = 0x11;

	public const ARG_TYPE_FULL_INTEGER_RANGE = 0x17;

	public const ARG_TYPE_EQUIPMENT_SLOT = 0x26;
	public const ARG_TYPE_STRING = 0x27;

	public const ARG_TYPE_INT_POSITION = 0x2f;
	public const ARG_TYPE_POSITION = 0x30;

	public const ARG_TYPE_MESSAGE = 0x33;

	public const ARG_TYPE_RAWTEXT = 0x35;

	public const ARG_TYPE_JSON = 0x39;

	public const ARG_TYPE_BLOCK_STATES = 0x43;

	public const ARG_TYPE_COMMAND = 0x46;

	/**
	 * Enums are a little different: they are composed as follows:
	 * ARG_FLAG_ENUM | ARG_FLAG_VALID | (enum index)
	 */
	public const ARG_FLAG_ENUM = 0x200000;

	/**
	 * This is used for /xp <level: int>L. It can only be applied to integer parameters.
	 */
	public const ARG_FLAG_POSTFIX = 0x1000000;

	public const HARDCODED_ENUM_NAMES = [
		"CommandName" => true
	];

	/**
	 * @var string|CommandData[]
	 * List of command data, including name, description, alias indexes and parameters.
	 */
	public $commandData = [];

	/**
	 * @var CommandEnum[]
	 * List of enums which aren't directly referenced by any vanilla command.
	 * This is used for the `CommandName` enum, which is a magic enum used by the `command` argument type.
	 */
	public $hardcodedEnums = [];

	/**
	 * @var CommandEnum[]
	 * List of dynamic command enums, also referred to as "soft" enums. These can by dynamically updated mid-game
	 * without resending this packet.
	 */
	public $softEnums = [];

	/**
	 * @var CommandEnumConstraint[]
	 * List of constraints for enum members. Used to constrain gamerules that can bechanged in nocheats mode and more.
	 */
	public $enumConstraints = [];

    /** @var string */
	public $unknown = "";

	protected function decodePayload(){
	    if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_130) {
	    	/** @var string[] $enumValues */
	    	$enumValues = [];
	    	for($i = 0, $enumValuesCount = $this->getUnsignedVarInt(); $i < $enumValuesCount; ++$i){
		    	$enumValues[] = $this->getString();
	    	}

			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_594){
	            /** @var string[] $chainedSubcommandValueNames */
	            $chainedSubcommandValueNames = [];
	            for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
		            $chainedSubcommandValueNames[] = $this->getString();
	            }
			}

	    	/** @var string[] $postfixes */
	    	$postfixes = [];
	    	for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
		    	$postfixes[] = $this->getString();
	    	}

	    	/** @var CommandEnum[] $enums */
	    	$enums = [];
	    	for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
		    	$enums[] = $enum = $this->getEnum($enumValues);
		        //TODO: Bedrock may provide some enums which are not referenced by any command, and can't reasonably be
			    //considered "hardcoded". This happens with various Edu command enums, and other enums which are probably
			    //intended to be used by commands which aren't present in public releases.
			    //We should probably store these somewhere, since we'll need them to be able to correctly re-encode the
			    //packet for testing.
		    	if(isset(self::HARDCODED_ENUM_NAMES[$enum->enumName])){
			    	$this->hardcodedEnums[] = $enum;
		    	}
	    	}

			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_594){
			    $chainedSubCommandData = [];
			    for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
					$name = $this->getString();
			 	    $values = [];
				    for($j = 0, $valueCount = $this->getUnsignedVarInt(); $j < $valueCount; ++$j){
				    	$valueName = $chainedSubcommandValueNames[$this->getLShort()];
					    $valueType = $this->getLShort();
				        $values[] = new ChainedSubCommandValue($valueName, $valueType);
				    }
				    $chainedSubCommandData[] = new ChainedSubCommandData($name, $values);
				}
			}

	    	for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
		    	$this->commandData[] = $this->getCommandData($enums, $postfixes, $chainedSubCommandData ?? []);
	    	}

            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_280){
	        	for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
		        	$this->softEnums[] = $this->getSoftEnum();
	        	}

                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_385){
		            for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
		            	$this->enumConstraints[] = $this->getEnumConstraint($enums, $enumValues);
		            }
	        	}
	    	}
		} else {
		    $this->commandData = $this->getString();
		    $this->unknown = $this->getString();
		}
	}

	/**
	 * @param string[] $enumValueList
	 *
	 * @return CommandEnum
	 * @throws UnexpectedValueException
	 * @throws BinaryDataException
	 */
	protected function getEnum(array $enumValueList) : CommandEnum{
		$retval = new CommandEnum();
		$retval->enumName = $this->getString();

		$listSize = count($enumValueList);

		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$index = $this->getEnumValueIndex($listSize);
			if(!isset($enumValueList[$index])){
				throw new UnexpectedValueException("Invalid enum value index $index");
			}
			//Get the enum value from the initial pile of mess
			$retval->enumValues[] = $enumValueList[$index];
		}

		return $retval;
	}

	protected function getSoftEnum() : CommandEnum{
		$retval = new CommandEnum();
		$retval->enumName = $this->getString();

		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			//Get the enum value from the initial pile of mess
			$retval->enumValues[] = $this->getString();
		}

		return $retval;
	}

	/**
	 * @param CommandEnum $enum
	 * @param int[]       $enumValueMap string enum name -> int index
	 */
	protected function putEnum(CommandEnum $enum, array $enumValueMap) : void{
		$this->putString($enum->enumName);

		$this->putUnsignedVarInt(count($enum->enumValues));
		$listSize = count($enumValueMap);
		foreach($enum->enumValues as $value){
			$index = $enumValueMap[$value] ?? -1;
			if($index === -1){
				throw new InvalidStateException("Enum value '$value' not found");
			}
			$this->putEnumValueIndex($index, $listSize);
		}
	}

	protected function putSoftEnum(CommandEnum $enum) : void{
		$this->putString($enum->enumName);

		$this->putUnsignedVarInt(count($enum->enumValues));
		foreach($enum->enumValues as $value){
			$this->putString($value);
		}
	}

	/**
	 * @param int $valueCount
	 *
	 * @return int
	 * @throws BinaryDataException
	 */
	protected function getEnumValueIndex(int $valueCount) : int{
		if($valueCount < 256){
			return $this->getByte();
		}elseif($valueCount < 65536){
			return $this->getLShort();
		}else{
			return $this->getLInt();
		}
	}

	protected function putEnumValueIndex(int $index, int $valueCount) : void{
		if($valueCount < 256){
            $this->putByte($index);
		}elseif($valueCount < 65536){
            $this->putLShort($index);
		}else{
            $this->putLInt($index);
		}
	}

	/**
	 * @param CommandEnum[] $enums
	 * @param string[]      $enumValues
	 *
	 * @return CommandEnumConstraint
	 */
	protected function getEnumConstraint(array $enums, array $enumValues) : CommandEnumConstraint{
		//wtf, what was wrong with an offset inside the enum? :(
		$valueIndex = $this->getLInt();
		if(!isset($enumValues[$valueIndex])){
			throw new UnexpectedValueException("Enum constraint refers to unknown enum value index $valueIndex");
		}
		$enumIndex = $this->getLInt();
		if(!isset($enums[$enumIndex])){
			throw new UnexpectedValueException("Enum constraint refers to unknown enum index $enumIndex");
		}
		$enum = $enums[$enumIndex];
		$valueOffset = array_search($enumValues[$valueIndex], $enum->enumValues, true);
		if($valueOffset === false){
			throw new UnexpectedValueException("Value \"" . $enumValues[$valueIndex] . "\" does not belong to enum \"$enum->enumName\"");
		}

		$constraintIds = [];
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$constraintIds[] = $this->getByte();
		}

		return new CommandEnumConstraint($enum, $valueOffset, $constraintIds);
	}

	/**
	 * @param CommandEnumConstraint $constraint
	 * @param int[]                 $enumIndexes string enum name -> int index
	 * @param int[]                 $enumValueIndexes string value -> int index
	 */
	protected function putEnumConstraint(CommandEnumConstraint $constraint, array $enumIndexes, array $enumValueIndexes) : void{
        $this->putLInt($enumValueIndexes[$constraint->getAffectedValue()]);
        $this->putLInt($enumIndexes[$constraint->getEnum()->enumName]);
		$this->putUnsignedVarInt(count($constraint->getConstraints()));
		foreach($constraint->getConstraints() as $v){
            $this->putByte($v);
		}
	}

	/**
	 * @param CommandEnum[] $enums
	 * @param string[]      $postfixes
     * @param ChainedSubCommandData[] $allChainedSubCommandData
	 *
	 * @return CommandData
	 * @throws UnexpectedValueException
	 * @throws BinaryDataException
	 */
	protected function getCommandData(array $enums, array $postfixes, array $allChainedSubCommandData) : CommandData{
		$retval = new CommandData();
		$retval->commandName = $this->getString();
		$retval->commandDescription = $this->getString();
		if($this->getProtocol() < ProtocolInfo::PROTOCOL_448){
	    	$retval->flags = $this->getByte();
		}else{
		    $retval->flags = $this->getLShort();
		}
		$retval->permission = $this->getByte();
		$retval->aliases = $enums[$this->getLInt()] ?? null;

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_594){
		    $chainedSubCommandData = [];
		    for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			    $index = $this->getLShort();
			    $chainedSubCommandData[] = $allChainedSubCommandData[$index] ?? throw new UnexpectedValueException("Unknown chained subcommand data index $index");
		    }
		}

        $retval->chainedSubCommandData = $chainedSubCommandData ?? [];

		$retval->overloads = [];

		for($overloadIndex = 0, $overloadCount = $this->getUnsignedVarInt(); $overloadIndex < $overloadCount; ++$overloadIndex){
			$parameters = [];
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_594){
			    $isChaining = $this->getBool();
			}
			for($paramIndex = 0, $paramCount = $this->getUnsignedVarInt(); $paramIndex < $paramCount; ++$paramIndex){
				$parameter = new CommandParameter();
				$parameter->paramName = $this->getString();
				$parameter->paramType = $this->getLInt();
				$parameter->isOptional = $this->getBool();
				if($this->getProtocol() === ProtocolInfo::PROTOCOL_340 || $this->getProtocol() >= ProtocolInfo::PROTOCOL_350){
			    	$parameter->flags = $this->getByte();
				}

				if($parameter->paramType & self::ARG_FLAG_ENUM){
					$index = ($parameter->paramType & 0xffff);
					$parameter->enum = $enums[$index] ?? null;
					if($parameter->enum === null){
						throw new UnexpectedValueException("deserializing $retval->commandName parameter $parameter->paramName: expected enum at $index, but got none");
					}
				}elseif($parameter->paramType & self::ARG_FLAG_POSTFIX){
					$index = ($parameter->paramType & 0xffff);
					$parameter->postfix = $postfixes[$index] ?? null;
					if($parameter->postfix === null){
						throw new UnexpectedValueException("deserializing $retval->commandName parameter $parameter->paramName: expected postfix at $index, but got none");
					}
				}elseif(($parameter->paramType & self::ARG_FLAG_VALID) === 0){
					throw new UnexpectedValueException("deserializing $retval->commandName parameter $parameter->paramName: Invalid parameter type 0x" . dechex($parameter->paramType));
				}

				$parameters[$paramIndex] = $parameter;
			}
            $retval->overloads[$overloadIndex] = new CommandOverload($isChaining ?? false, $parameters);
		}

		return $retval;
	}

	/**
	 * @param CommandData $data
	 * @param int[]       $enumIndexes string enum name -> int index
	 * @param int[]       $postfixIndexes
     * @param int[]       $chainedSubCommandDataIndexes
	 */
	protected function putCommandData(CommandData $data, array $enumIndexes, array $postfixIndexes, array $chainedSubCommandDataIndexes) : void{
		$this->putString($data->commandName);
		$this->putString($data->commandDescription);
		if($this->getProtocol() < ProtocolInfo::PROTOCOL_448){
            $this->putByte($data->flags);
		}else{
		    $this->putLShort($data->flags);
		}
        $this->putByte($data->permission);

		if($data->aliases !== null){
            $this->putLInt($enumIndexes[$data->aliases->enumName] ?? -1);
		}else{
            $this->putLInt(-1);
		}

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_594){
		    $this->putUnsignedVarInt(count($data->chainedSubCommandData));
		    foreach($data->chainedSubCommandData as $chainedSubCommandData){
			    $index = $chainedSubCommandDataIndexes[$chainedSubCommandData->getName()] ??
			    	throw new \LogicException("Chained subcommand data {$chainedSubCommandData->getName()} does not have an index (this should be impossible)");
		    	$this->putLShort($index);
			}
		}

		$this->putUnsignedVarInt(count($data->overloads));
		foreach($data->overloads as $overload){
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_594){
			    $this->putBool($overload->isChaining());
			}
			$this->putUnsignedVarInt(count($overload->getParameters()));
			foreach($overload->getParameters() as $parameter){
				$this->putString($parameter->paramName);

				if($parameter->enum !== null){
					$type = self::ARG_FLAG_ENUM | self::ARG_FLAG_VALID | ($enumIndexes[$parameter->enum->enumName] ?? -1);
				}elseif($parameter->postfix !== null){
					$key = $postfixIndexes[$parameter->postfix] ?? -1;
					if($key === -1){
						throw new InvalidStateException("Postfix '$parameter->postfix' not in postfixes array");
					}
					$type = self::ARG_FLAG_POSTFIX | $key;
				}else{
					$type = $parameter->paramType;
                    if(($type & self::ARG_FLAG_VALID) !== 0x0){
                        $type &=~ self::ARG_FLAG_VALID;
                    }
                    $type = MultiversionEnums::getCommandArgType($type, $this->getProtocol());
                    $type |= self::ARG_FLAG_VALID;
				}

                $this->putLInt($type);
                $this->putBool($parameter->isOptional);
				if($this->getProtocol() === ProtocolInfo::PROTOCOL_340 || $this->getProtocol() >= ProtocolInfo::PROTOCOL_350){
                    $this->putByte($parameter->flags);
				}
			}
		}
	}

	private function argTypeToString(int $argtype, array $postfixes) : string{
		if($argtype & self::ARG_FLAG_VALID){
			if($argtype & self::ARG_FLAG_ENUM){
				return "stringenum (" . ($argtype & 0xffff) . ")";
			}

			switch($argtype & 0xffff){
				case self::ARG_TYPE_INT:
					return "int";
				case self::ARG_TYPE_FLOAT:
					return "float";
				case self::ARG_TYPE_VALUE:
					return "mixed";
				case self::ARG_TYPE_TARGET:
					return "target";
				case self::ARG_TYPE_STRING:
					return "string";
				case self::ARG_TYPE_POSITION:
					return "xyz";
				case self::ARG_TYPE_MESSAGE:
					return "message";
				case self::ARG_TYPE_RAWTEXT:
					return "text";
				case self::ARG_TYPE_JSON:
					return "json";
				case self::ARG_TYPE_COMMAND:
					return "command";
			}
		}elseif($argtype & self::ARG_FLAG_POSTFIX){
			$postfix = $postfixes[$argtype & 0xffff];

			return "int (postfix $postfix)";
		}else{
			throw new UnexpectedValueException("Unknown arg type 0x" . dechex($argtype));
		}

		return "unknown ($argtype)";
	}

	protected function encodePayload(){
	    if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_130) {
	    	/** @var int[] $enumValueIndexes */
	    	$enumValueIndexes = [];
	    	/** @var int[] $postfixIndexes */
	    	$postfixIndexes = [];
	    	/** @var int[] $enumIndexes */
	    	$enumIndexes = [];
	    	/** @var CommandEnum[] $enums */
	    	$enums = [];

	    	$addEnumFn = static function(CommandEnum $enum) use (&$enums, &$enumIndexes, &$enumValueIndexes){
		   	if(!isset($enumIndexes[$enum->enumName])){
			    	$enums[$enumIndexes[$enum->enumName] = count($enumIndexes)] = $enum;
		    	}
		    	foreach($enum->enumValues as $str){
			    	$enumValueIndexes[$str] = $enumValueIndexes[$str] ?? count($enumValueIndexes);
		    	}
	    	};


		    /**
		     * @var ChainedSubCommandData[] $allChainedSubCommandData
		     * @phpstan-var array<string, ChainedSubCommandData> $allChainedSubCommandData
		     */
		    $allChainedSubCommandData = [];
		    /**
		     * @var int[] $chainedSubCommandDataIndexes
		     * @phpstan-var array<string, int> $chainedSubCommandDataIndexes
		     */
		    $chainedSubCommandDataIndexes = [];

		    /**
		     * @var int[] $chainedSubCommandValueNameIndexes
		     * @phpstan-var array<string, int> $chainedSubCommandValueNameIndexes
		     */
		    $chainedSubCommandValueNameIndexes = [];

	    	foreach($this->hardcodedEnums as $enum){
		    	$addEnumFn($enum);
	    	}
	    	foreach($this->commandData as $commandData){
		    	if($commandData->aliases !== null){
		    	    $addEnumFn($commandData->aliases);
		    	}
		    	foreach($commandData->overloads as $overload){
			    	foreach($overload->getParameters() as $parameter){
				    	if($parameter->enum !== null){
				    	    $addEnumFn($parameter->enum);
				    	}

				    	if($parameter->postfix !== null){
					    	$postfixIndexes[$parameter->postfix] = $postfixIndexes[$parameter->postfix] ?? count($postfixIndexes);
				    	}
			    	}
		    	}
				foreach($commandData->chainedSubCommandData as $chainedSubCommandData){
				    if(!isset($allChainedSubCommandData[$chainedSubCommandData->getName()])){
					    $allChainedSubCommandData[$chainedSubCommandData->getName()] = $chainedSubCommandData;
					    $chainedSubCommandDataIndexes[$chainedSubCommandData->getName()] = count($chainedSubCommandDataIndexes);
	
					    foreach($chainedSubCommandData->getValues() as $value){
						    $chainedSubCommandValueNameIndexes[$value->getName()] ??= count($chainedSubCommandValueNameIndexes);
					    }
				    }
			    }
	    	}

	    	$this->putUnsignedVarInt(count($enumValueIndexes));
	    	foreach($enumValueIndexes as $enumValue => $index){
		    	$this->putString((string) $enumValue); //stupid PHP key casting D:
	    	}

			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_594){
		    	$this->putUnsignedVarInt(count($chainedSubCommandValueNameIndexes));
		    	foreach($chainedSubCommandValueNameIndexes as $chainedSubCommandValueName => $index){
		    		$this->putString((string) $chainedSubCommandValueName); //stupid PHP key casting D:
				}
			}

	    	$this->putUnsignedVarInt(count($postfixIndexes));
	    	foreach($postfixIndexes as $postfix => $index){
		    	$this->putString((string) $postfix); //stupid PHP key casting D:
	    	}

	    	$this->putUnsignedVarInt(count($enums));
	    	foreach($enums as $enum){
		    	$this->putEnum($enum, $enumValueIndexes);
	    	}

			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_594){
		    	$this->putUnsignedVarInt(count($allChainedSubCommandData));
			    foreach($allChainedSubCommandData as $chainedSubCommandData){
			    	$this->putString($chainedSubCommandData->getName());
			    	$this->putUnsignedVarInt(count($chainedSubCommandData->getValues()));
			    	foreach($chainedSubCommandData->getValues() as $value){
				    	$valueNameIndex = $chainedSubCommandValueNameIndexes[$value->getName()] ??
					    	throw new \LogicException("Chained subcommand value name index for \"" . $value->getName() . "\" not found (this should never happen)");
				    	$this->putLShort($valueNameIndex);
				    	$this->putLShort($value->getType());
					}
				}
			}

	    	$this->putUnsignedVarInt(count($this->commandData));
	    	foreach($this->commandData as $data){
		    	$this->putCommandData($data, $enumIndexes, $postfixIndexes, $chainedSubCommandDataIndexes);
	    	}

            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_280){
	        	$this->putUnsignedVarInt(count($this->softEnums));
	        	foreach($this->softEnums as $enum){
		        	$this->putSoftEnum($enum);
	        	}

                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_385){
	            	$this->putUnsignedVarInt(count($this->enumConstraints));
	            	foreach($this->enumConstraints as $constraint){
	        	    	$this->putEnumConstraint($constraint, $enumIndexes, $enumValueIndexes);
	            	}
	        	}
	    	}
	    } else {
	        $this->putString(json_encode(new CommandListJson($this->commandData)));
	        $this->putString($this->unknown);
	    }
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAvailableCommands($this);
	}
}
