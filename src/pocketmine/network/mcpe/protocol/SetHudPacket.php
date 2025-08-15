<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\hud\HudElement;
use pocketmine\network\mcpe\protocol\types\hud\HudVisibility;
use function count;

class SetHudPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_HUD_PACKET;

	/** @var HudElement[] */
	private array $hudElements = [];
	private HudVisibility $visibility;

	/**
	 * @generate-create-func
	 * @param HudElement[] $hudElements
	 */
	public static function create(array $hudElements, HudVisibility $visibility) : self{
		$result = new self;
		$result->hudElements = $hudElements;
		$result->visibility = $visibility;
		return $result;
	}

	/** @return HudElement[] */
	public function getHudElements() : array{ return $this->hudElements; }

	public function getVisibility() : HudVisibility{ return $this->visibility; }

	protected function decodePayload(){
		$this->hudElements = [];
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$this->hudElements[] = HudElement::fromPacket($this->getProtocol() >= ProtocolInfo::PROTOCOL_786 ? $this->getVarInt() : $this->getByte());
		}
		$this->visibility = HudVisibility::fromPacket($this->getProtocol() >= ProtocolInfo::PROTOCOL_786 ? $this->getVarInt() : $this->getByte());
	}

	protected function encodePayload(){
		$this->putUnsignedVarInt(count($this->hudElements));
		foreach($this->hudElements as $element){
		    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_786){
		        $this->putVarInt($element->value);
		    }else{
			    $this->putByte($element->value);
		    }
		}
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_786){
		    $this->putVarInt($this->visibility->value);
		}else{
		    $this->putByte($this->visibility->value);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleSetHud($this);
	}
}
