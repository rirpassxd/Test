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

class CodeBuilderSourcePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CODE_BUILDER_SOURCE_PACKET;

	private int $operation;
	private int $category;
	private string $value;
	private int $codeStatus;

	/**
	 * @generate-create-func
	 */
	public static function create(int $operation, int $category, string $value, int $codeStatus) : self{
		$result = new self;
		$result->operation = $operation;
		$result->category = $category;
		$result->value = $value;
		$result->codeStatus = $codeStatus;
		return $result;
	}

	public function getOperation() : int{ return $this->operation; }

	public function getCategory() : int{ return $this->category; }

	public function getValue() : string{ return $this->value; }

	public function getCodeStatus() : int{ return $this->codeStatus; }

	protected function decodePayload(){
		$this->operation = $this->getByte();
		$this->category = $this->getByte();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_685){
			$this->codeStatus = $this->getByte();
		}else{
			$this->value = $this->getString();
		}
	}

	protected function encodePayload(){
		$this->putByte($this->operation);
		$this->putByte($this->category);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_685){
			$this->putByte($this->codeStatus);
		}else{
	    	$this->putString($this->value);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCodeBuilderSource($this);
	}
}
