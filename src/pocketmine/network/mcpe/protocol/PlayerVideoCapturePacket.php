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
use LogicException;

class PlayerVideoCapturePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_VIDEO_CAPTURE_PACKET;

	private bool $recording;
	private ?int $frameRate;
	private ?string $filePrefix;

	/**
	 * @generate-create-func
	 */
	private static function create(bool $recording, ?int $frameRate, ?string $filePrefix) : self{
		$result = new self;
		$result->recording = $recording;
		$result->frameRate = $frameRate;
		$result->filePrefix = $filePrefix;
		return $result;
	}

	public static function createStartRecording(int $frameRate, string $filePrefix) : self{
		return self::create(true, $frameRate, $filePrefix);
	}

	public static function createStopRecording() : self{
		return self::create(false, null, null);
	}

	public function isRecording() : bool{ return $this->recording; }

	public function getFrameRate() : ?int{ return $this->frameRate; }

	public function getFilePrefix() : ?string{ return $this->filePrefix; }

	protected function decodePayload() : void{
		$this->recording = $this->getBool();
		if($this->recording){
			$this->frameRate = $this->getLInt();
			$this->filePrefix = $this->getString();
		}
	}

	protected function encodePayload() : void{
		$this->putBool($this->recording);
		if($this->recording){
			if($this->frameRate === null){ // this should never be the case
				throw new LogicException("PlayerUpdateEntityOverridesPacket with recording=true require a frame rate to be provided");
			}

			if($this->filePrefix === null){ // this should never be the case
				throw new LogicException("PlayerUpdateEntityOverridesPacket with recording=true require a file prefix to be provided");
			}

			$this->putLInt($this->frameRate);
			$this->putString($this->filePrefix);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerVideoCapturePacket($this);
	}
}
