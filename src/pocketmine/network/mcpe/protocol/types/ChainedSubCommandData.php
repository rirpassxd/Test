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

final class ChainedSubCommandData{
    /** @var string */
	public $name;
	/** @var ChainedSubCommandValue[] */
	public $values;

	/**
	 * @param ChainedSubCommandValue[] $values
	 */
	public function __construct(
		string $name,
		array $values
	){
		$this->name = $name;
		$this->values = $values;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		 return $this->name;
	}

	/**
	 * @return ChainedSubCommandValue[]
	 */
	public function getValues() : array{
		return $this->values;
	}

}
