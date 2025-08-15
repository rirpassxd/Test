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
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;

final class MineBlockStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::MINE_BLOCK;

	/** @var int */
	private $hotbarSlot;
	/** @var int */
	private $predictedDurability;
	/** @var int */
	private $stackId;

	public function __construct(int $hotbarSlot, int $predictedDurability, int $stackId){
		$this->hotbarSlot = $hotbarSlot;
		$this->predictedDurability = $predictedDurability;
		$this->stackId = $stackId;
	}

	public function getHotbarSlot() : int{ return $this->hotbarSlot; }

	public function getPredictedDurability() : int{ return $this->predictedDurability; }

	public function getStackId() : int{ return $this->stackId; }

	public static function read(NetworkBinaryStream $in) : self{
		$hotbarSlot = $in->getVarInt();
		$predictedDurability = $in->getVarInt();
		$stackId = $in->readItemStackNetIdVariant();
		return new self($hotbarSlot, $predictedDurability, $stackId);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putVarInt($this->hotbarSlot);
		$out->putVarInt($this->predictedDurability);
		$out->writeItemStackNetIdVariant($this->stackId);
	}
}
