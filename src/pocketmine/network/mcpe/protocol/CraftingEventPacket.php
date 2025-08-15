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

use pocketmine\item\Item;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\utils\UUID;
use function chr;
use function count;
use function ord;

class CraftingEventPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CRAFTING_EVENT_PACKET;

	/** @var int */
	public $windowId;
	/** @var int */
	public $type;
	/** @var UUID */
	public $id;
	/** @var Item[] */
	public $input = [];
	/** @var Item[] */
	public $output = [];

	protected function decodePayload(){
		$this->windowId = $this->getByte();
		$this->type = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getVarInt() : $this->getInt();
		$this->id = $this->getUUID();

		$size = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getUnsignedVarInt() : $this->getInt();
		for($i = 0; $i < $size and $i < 128; ++$i){
			$this->input[] = $this->getSlot();
		}

		$size = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getUnsignedVarInt() : $this->getInt();
		for($i = 0; $i < $size and $i < 128; ++$i){
			$this->output[] = $this->getSlot();
		}
	}

	protected function encodePayload(){
        $this->putByte($this->windowId);
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->putVarInt($this->type);
        }else{
            $this->putInt($this->type);
        }
		$this->putUUID($this->id);

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->putUnsignedVarInt(count($this->input));
		}else{
		    $this->putInt(count($this->input));
		}
		foreach($this->input as $item){
			$this->putSlot($item);
		}

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->putUnsignedVarInt(count($this->output));
		}else{
		    $this->putInt(count($this->output));
		}
		foreach($this->output as $item){
			$this->putSlot($item);
		}
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCraftingEvent($this);
	}
}
