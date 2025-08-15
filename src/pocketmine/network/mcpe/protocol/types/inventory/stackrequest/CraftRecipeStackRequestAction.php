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

use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;

/**
 * Tells that the current transaction crafted the specified recipe.
 */
final class CraftRecipeStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CRAFTING_RECIPE;

	/** @var int */
	private $recipeId;
	/** @var int */
	private $repetitions;

	final public function __construct(int $recipeId, int $repetitions){
		$this->recipeId = $recipeId;
		$this->repetitions = $repetitions;
	}

	public function getRecipeId() : int{ return $this->recipeId; }

    public function getRepetitions() : int{ return $this->repetitions; }

	public static function read(NetworkBinaryStream $in) : self{
		$recipeId = $in->readRecipeNetId();
		if($in->getProtocol() >= ProtocolInfo::PROTOCOL_712){
			$repetitions = $in->getByte();
		}
		return new self($recipeId, $repetitions ?? 0);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->writeRecipeNetId($this->recipeId);
		if($out->getProtocol() >= ProtocolInfo::PROTOCOL_712){
			$out->putByte($this->repetitions);
		}
	}
}
