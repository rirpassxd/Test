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
use pocketmine\network\mcpe\protocol\types\pocketedition\CommandOriginData;

class CommandStepPacket extends DataPacket{
	const NETWORK_ID = ProtocolInfo::COMMAND_STEP_PACKET;

	/** @var string */
	public $command;
	/** @var string */
	public $overload;
	/** @var int */
	public $uvarint1;
	/** @var int */
	public $currentStep;
	/** @var bool */
	public $done;
	/** @var int */
	public $clientId;
	/** @var array|null */
	public $inputJson;
	/** @var array|null */
	public $outputJson;
	/** @var CommandOriginData */
	public $originData;

	public function decodePayload(){
		$this->command = $this->getString();
		$this->overload = $this->getString();
		$this->uvarint1 = $this->getUnsignedVarInt();
		$this->currentStep = $this->getUnsignedVarInt();
		$this->done = $this->getBool();
		$this->clientId = $this->getUnsignedVarLong();
		$this->inputJson = json_decode($this->getString());
		$this->outputJson = json_decode($this->getString());
		$this->originData = CommandOriginData::read($this);
	}

	public function encodePayload(){
		$this->putString($this->command);
		$this->putString($this->overload);
		$this->putUnsignedVarInt($this->uvarint1);
		$this->putUnsignedVarInt($this->currentStep);
        $this->putBool($this->done);
		$this->putUnsignedVarLong($this->clientId);
		$this->putString(json_encode($this->inputJson));
		$this->putString(json_encode($this->outputJson));
		$this->originData->write($this);
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCommandStep($this);
	}

}