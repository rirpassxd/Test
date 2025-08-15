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

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;

final class ChainedSubCommandValue{
    /** @var string */
	public $name;
	/** @var int */
	public $type;

	public function __construct(
		string $name,
		int $type
	){
		$this->name = $name;
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @see AvailableCommandsPacket::ARG_TYPE_*
	 */
	public function getType() : int{
		return $this->type;
	}

}
