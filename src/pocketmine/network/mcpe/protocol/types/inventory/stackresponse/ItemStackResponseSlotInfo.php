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

namespace pocketmine\network\mcpe\protocol\types\inventory\stackresponse;

use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class ItemStackResponseSlotInfo{

	/** @var int */
	private $slot;
	/** @var int */
	private $hotbarSlot;
	/** @var int */
	private $count;
	/** @var int */
	private $itemStackId;
	/** @var string */
	private $customName;
	/** @var string */
	private $filteredCustomName;
	/** @var int */
	private $durabilityCorrection;

	public function __construct(int $slot, int $hotbarSlot, int $count, int $itemStackId, string $customName, string $filteredCustomName, int $durabilityCorrection){
		$this->slot = $slot;
		$this->hotbarSlot = $hotbarSlot;
		$this->count = $count;
		$this->itemStackId = $itemStackId;
		$this->customName = $customName;
		$this->filteredCustomName = $filteredCustomName;
		$this->durabilityCorrection = $durabilityCorrection;
	}

	public function getSlot() : int{ return $this->slot; }

	public function getHotbarSlot() : int{ return $this->hotbarSlot; }

	public function getCount() : int{ return $this->count; }

	public function getItemStackId() : int{ return $this->itemStackId; }

	public function getCustomName() : string{ return $this->customName; }

    public function getFilteredCustomName() : string{ return $this->filteredCustomName; }

	public function getDurabilityCorrection() : int{ return $this->durabilityCorrection; }

	public static function read(NetworkBinaryStream $in) : self{
		$slot = $in->getByte();
		$hotbarSlot = $in->getByte();
		$count = $in->getByte();
		$itemStackId = $in->readServerItemStackId();
		if($in->getProtocol() >= ProtocolInfo::PROTOCOL_422){
		    $customName = $in->getString();
			if($in->getProtocol() >= ProtocolInfo::PROTOCOL_422){
				if($in->getProtocol() >= ProtocolInfo::PROTOCOL_766){
					$filteredCustomName = $in->getString();
				}
	        	$durabilityCorrection = $in->getVarInt();
			}
		}
		return new self($slot, $hotbarSlot, $count, $itemStackId, $customName ?? "", $filteredCustomName ?? "", $durabilityCorrection ?? 0);
	}

	public function write(NetworkBinaryStream $out) : void{
		$out->putByte($this->slot);
		$out->putByte($this->hotbarSlot);
		$out->putByte($this->count);
		$out->writeServerItemStackId($this->itemStackId);
		if($out->getProtocol() >= ProtocolInfo::PROTOCOL_422){
		    $out->putString($this->customName);
			if($out->getProtocol() >= ProtocolInfo::PROTOCOL_422){
				if($out->getProtocol() >= ProtocolInfo::PROTOCOL_766){
					$out->putString($this->filteredCustomName);
				}
	        	$out->putVarInt($this->durabilityCorrection);
			}
		}
	}
}
