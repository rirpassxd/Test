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

class GameTestRequestPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::GAME_TEST_REQUEST_PACKET;

	public const ROTATION_0 = 0;
	public const ROTATION_90 = 1;
	public const ROTATION_180 = 2;
	public const ROTATION_270 = 3;

	private int $maxTestsPerBatch;
	private int $repeatCount;
	private int $rotation;
	private bool $stopOnFailure;
	private int $x = 0;
    private int $y = 0;
    private int $z = 0;
	private int $testsPerRow;
	private string $testName;

	/**
	 * @generate-create-func
	 */
	public static function create(
		int $maxTestsPerBatch,
		int $repeatCount,
		int $rotation,
		bool $stopOnFailure,
		int $x,
        int $y,
        int $z,
		int $testsPerRow,
		string $testName,
	) : self{
		$result = new self;
		$result->maxTestsPerBatch = $maxTestsPerBatch;
		$result->repeatCount = $repeatCount;
		$result->rotation = $rotation;
		$result->stopOnFailure = $stopOnFailure;
		$result->x = $x;
        $result->y = $y;
        $result->z = $z;
		$result->testsPerRow = $testsPerRow;
		$result->testName = $testName;
		return $result;
	}

	public function getMaxTestsPerBatch() : int{ return $this->maxTestsPerBatch; }

	public function getRepeatCount() : int{ return $this->repeatCount; }

	/**
	 * @see self::ROTATION_*
	 */
	public function getRotation() : int{ return $this->rotation; }

	public function isStopOnFailure() : bool{ return $this->stopOnFailure; }

    public function getX() : int{ return $this->x; }

    public function getY() : int{ return $this->y; }

    public function getZ() : int{ return $this->z; }

	public function getTestsPerRow() : int{ return $this->testsPerRow; }

	public function getTestName() : string{ return $this->testName; }

	protected function decodePayload() : void{
		$this->maxTestsPerBatch = $this->getVarInt();
		$this->repeatCount = $this->getVarInt();
		$this->rotation = $this->getByte();
		$this->stopOnFailure = $this->getBool();
		$this->getSignedBlockPosition($this->x, $this->y, $this->z);
		$this->testsPerRow = $this->getVarInt();
		$this->testName = $this->getString();
	}

	protected function encodePayload() : void{
		$this->putVarInt($this->maxTestsPerBatch);
		$this->putVarInt($this->repeatCount);
		$this->putByte($this->rotation);
		$this->putBool($this->stopOnFailure);
		$this->putSignedBlockPosition($this->x, $this->y, $this->z);
		$this->putVarInt($this->testsPerRow);
		$this->putString($this->testName);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleGameTestRequest($this);
	}
}
