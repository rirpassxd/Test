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

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\TransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use UnexpectedValueException;
use function count;

class InventoryTransactionPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::INVENTORY_TRANSACTION_PACKET;

	public const TYPE_NORMAL = 0;
	public const TYPE_MISMATCH = 1;
	public const TYPE_USE_ITEM = 2;
	public const TYPE_USE_ITEM_ON_ENTITY = 3;
	public const TYPE_RELEASE_ITEM = 4;

    /** @var int */
	public $requestId;
	/** @var InventoryTransactionChangedSlotsHack[] */
	public $requestChangedSlots;
	/** @var TransactionData */
	public $trData;

	protected function decodePayload(){
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392){
	    	$this->requestId = $this->readLegacyItemStackRequestId();
	    	$this->requestChangedSlots = [];
		    if($this->requestId !== 0){
				$len = $this->getUnsignedVarInt();
                if($len > 128){
                    throw new UnexpectedValueException("Too many inventories count in inventory transaction: " . $len);
                }
			    for($i = 0, $len; $i < $len; ++$i){
				    $this->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($this);
			    }
		    }
        }

		$transactionType = $this->getUnsignedVarInt();

		$this->trData = match($transactionType){
			NormalTransactionData::ID => new NormalTransactionData(),
			MismatchTransactionData::ID => new MismatchTransactionData(),
			UseItemTransactionData::ID => new UseItemTransactionData(),
			UseItemOnEntityTransactionData::ID => new UseItemOnEntityTransactionData(),
			ReleaseItemTransactionData::ID => new ReleaseItemTransactionData(),
			default => throw new UnexpectedValueException("Unknown transaction type $transactionType"),
		};

		$this->trData->decode($this);
	}

	protected function encodePayload(){
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392){
	    	$this->writeLegacyItemStackRequestId($this->requestId);
		    if($this->requestId !== 0){
			    $this->putUnsignedVarInt(count($this->requestChangedSlots));
			    foreach($this->requestChangedSlots as $changedSlots){
				    $changedSlots->write($this);
			    }
		    }
		}

		$this->putUnsignedVarInt($this->trData->getTypeId());

		$this->trData->encode($this);
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleInventoryTransaction($this);
	}
}
