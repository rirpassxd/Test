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

namespace pocketmine\network\mcpe\protocol\types\inventory\stackrequest;

use pocketmine\network\mcpe\multiversion\MultiversionEnums;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\NetworkBinaryStream;
use function count;

final class ItemStackRequest{

	/** @var int */
	private $requestId;
	/** @var ItemStackRequestAction[] */
	private $actions;
	/**
	 * @var string[]
	 * @phpstan-var list<string>
	 */
	private $filterStrings;
	/** @var int */
	private $filterStringCause;

	/**
	 * @param ItemStackRequestAction[] $actions
	 * @param string[]                 $filterStrings
	 * @phpstan-param list<string> $filterStrings
	 * @param int                      $filterStringCause
	 */
	public function __construct(int $requestId, array $actions, array $filterStrings, int $filterStringCause){
		$this->requestId = $requestId;
		$this->actions = $actions;
		$this->filterStrings = $filterStrings;
		$this->filterStringCause = $filterStringCause;
	}

	public function getRequestId() : int{ return $this->requestId; }

	/** @return ItemStackRequestAction[] */
	public function getActions() : array{ return $this->actions; }

	/**
	 * @return string[]
	 * @phpstan-return list<string>
	 */
	public function getFilterStrings() : array{ return $this->filterStrings; }

    public function getFilterStringCause() : int{ return $this->filterStringCause; }

	private static function readAction(NetworkBinaryStream $in, int $typeId) : ItemStackRequestAction{
		return match($typeId){
			TakeStackRequestAction::ID => TakeStackRequestAction::read($in),
			PlaceStackRequestAction::ID => PlaceStackRequestAction::read($in),
			SwapStackRequestAction::ID => SwapStackRequestAction::read($in),
			DropStackRequestAction::ID => DropStackRequestAction::read($in),
			DestroyStackRequestAction::ID => DestroyStackRequestAction::read($in),
			CraftingConsumeInputStackRequestAction::ID => CraftingConsumeInputStackRequestAction::read($in),
			CraftingCreateSpecificResultStackRequestAction::ID => CraftingCreateSpecificResultStackRequestAction::read($in),
			PlaceIntoBundleStackRequestAction::ID => PlaceIntoBundleStackRequestAction::read($in),
			TakeFromBundleStackRequestAction::ID => TakeFromBundleStackRequestAction::read($in),
			LabTableCombineStackRequestAction::ID => LabTableCombineStackRequestAction::read($in),
			BeaconPaymentStackRequestAction::ID => BeaconPaymentStackRequestAction::read($in),
			MineBlockStackRequestAction::ID => MineBlockStackRequestAction::read($in),
			CraftRecipeStackRequestAction::ID => CraftRecipeStackRequestAction::read($in),
			CraftRecipeAutoStackRequestAction::ID => CraftRecipeAutoStackRequestAction::read($in),
			CreativeCreateStackRequestAction::ID => CreativeCreateStackRequestAction::read($in),
			CraftRecipeOptionalStackRequestAction::ID => CraftRecipeOptionalStackRequestAction::read($in),
			GrindstoneStackRequestAction::ID => GrindstoneStackRequestAction::read($in),
			LoomStackRequestAction::ID => LoomStackRequestAction::read($in),
			DeprecatedCraftingNonImplementedStackRequestAction::ID => DeprecatedCraftingNonImplementedStackRequestAction::read($in),
			DeprecatedCraftingResultsStackRequestAction::ID => DeprecatedCraftingResultsStackRequestAction::read($in),
			default => throw new \UnexpectedValueException("Unhandled item stack request action type $typeId for protocol " . $in->getProtocol()),
		};
	}

	public static function read(NetworkBinaryStream $in) : self{
		$requestId = $in->readItemStackRequestId();
		$actions = [];
		for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
			$typeId = $in->getByte();
			$actions[] = self::readAction($in, MultiversionEnums::getItemStackRequestActionType($in->getProtocol(), $typeId));
		}
		if($in->getProtocol() >= ProtocolInfo::PROTOCOL_422){
	    	$filterStrings = [];
	    	for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
		    	$filterStrings[] = $in->getString();
	    	}
			if($in->getProtocol() >= ProtocolInfo::PROTOCOL_554){
				$filterStringCause = $in->getLInt();
			}
		}
		return new self($requestId, $actions, $filterStrings ?? [], $filterStringCause ?? 0);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->writeItemStackRequestId($this->requestId);
		$out->putUnsignedVarInt(count($this->actions));
		foreach($this->actions as $action){
			$out->putByte(MultiversionEnums::getItemStackRequestActionTypeId($out->getProtocol(), $action->getTypeId()));
			$action->write($out);
		}
		if($out->getProtocol() >= ProtocolInfo::PROTOCOL_422){
	    	$out->putUnsignedVarInt(count($this->filterStrings));
	    	foreach($this->filterStrings as $string){
		    	$out->putString($string);
			}
			if($out->getProtocol() >= ProtocolInfo::PROTOCOL_554){
				$out->putLInt($this->filterStringCause);
			}
		}
	}
}
