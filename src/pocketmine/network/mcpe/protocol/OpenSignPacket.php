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

/**
 * Sent by the server to open the sign GUI for a sign.
 */
class OpenSignPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::OPEN_SIGN_PACKET;

	/** @var int */
	private $x;
	/** @var int */
	private $y;
	/** @var int */
	private $z;
	/** @var bool */
	private $front;

	/**
	 * @generate-create-func
	 */
	public static function create(int $x, int $y, int $z, bool $front) : self{
		$result = new self;
		$result->x = $x;
		$result->y = $y;
		$result->z = $z;
		$result->front = $front;
		return $result;
	}

	public function getX() : int{ return $this->x; }

	public function getY() : int{ return $this->y; }

	public function getZ() : int{ return $this->z; }

	public function isFront() : bool{ return $this->front; }

	protected function decodePayload(){
		$this->getBlockPosition($this->x, $this->y, $this->z);
		$this->front = $this->getBool();
	}

	protected function encodePayload(){
		$this->putBlockPosition($this->x, $this->y, $this->z);
		$this->putBool($this->front);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleOpenSign($this);
	}
}
