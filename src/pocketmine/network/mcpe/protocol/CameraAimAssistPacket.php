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

use pocketmine\math\Vector2;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistActionType;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistTargetMode;

class CameraAimAssistPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CAMERA_AIM_ASSIST_PACKET;

	private Vector2 $viewAngle;
	private float $distance;
	private CameraAimAssistTargetMode $targetMode;
	private CameraAimAssistActionType $actionType;

	/**
	 * @generate-create-func
	 */
	public static function create(Vector2 $viewAngle, float $distance, CameraAimAssistTargetMode $targetMode, CameraAimAssistActionType $actionType) : self{
		$result = new self;
		$result->viewAngle = $viewAngle;
		$result->distance = $distance;
		$result->targetMode = $targetMode;
		$result->actionType = $actionType;
		return $result;
	}

	public function getViewAngle() : Vector2{ return $this->viewAngle; }

	public function getDistance() : float{ return $this->distance; }

	public function getTargetMode() : CameraAimAssistTargetMode{ return $this->targetMode; }

	public function getActionType() : CameraAimAssistActionType{ return $this->actionType; }

	protected function decodePayload() : void{
		$this->viewAngle = $this->getVector2();
		$this->distance = $this->getLFloat();
		$this->targetMode = CameraAimAssistTargetMode::fromPacket($this->getByte());
		$this->actionType = CameraAimAssistActionType::fromPacket($this->getByte());
	}

	protected function encodePayload() : void{
		$this->putVector2($this->viewAngle);
		$this->putLFloat($this->distance);
		$this->putByte($this->targetMode->value);
		$this->putByte($this->actionType->value);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCameraAimAssist($this);
	}
}
