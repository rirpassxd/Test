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

use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class ChunkPosition{

	public function __construct(
		private int $x,
		private int $z
	){}

	public function getX() : int{ return $this->x; }

	public function getZ() : int{ return $this->z; }

	public static function read(NetworkBinaryStream $in) : self{
	    if($in->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$x = $in->getVarInt();
	    	$z = $in->getVarInt();
	    }else{
	        $x = $in->getInt();
	        $z = $in->getInt();
	    }

		return new self($x, $z);
	}

	public function write(NetworkBinaryStream $out) : void{
	    if($out->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$out->putVarInt($this->x);
	    	$out->putVarInt($this->z);
	    }else{
	    	$out->putInt($this->x);
	    	$out->putInt($this->z);
	    }
	}
}
