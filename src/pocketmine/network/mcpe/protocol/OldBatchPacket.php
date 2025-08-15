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

use Generator;
use InvalidArgumentException;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\utils\Binary;
use UnexpectedValueException;
use function get_class;
use function strlen;
use function unpack;
use function substr;
use function zlib_decode;
use function zlib_encode;
use const ZLIB_ENCODING_DEFLATE;

class OldBatchPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::BATCH_PACKET;

	public string $payload = "";
	protected int $compressionLevel = 7;

	public function canBeBatched() : bool{
		return true;
	}

	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	protected function decodePayload(){
	    $length = (unpack("N", substr($this->buffer, 1, 4))[1] << 32 >> 32);
	    $payload = substr($this->buffer, 5, $length);

	    if(isset($payload[0]) && $payload[0] === "\x78"){
	        $payload = $this->get($this->getInt());
	        $this->protocol = ProtocolInfo::CURRENT_PROTOCOL;
	    }else{
	        $payload = $this->getString();
	        $this->protocol = ProtocolInfo::PROTOCOL_90;
	    }

		$this->payload = @zlib_decode($payload, 1024 * 1024 * 2); //Max 2MB
	}

	protected function encodePayload(){
		$payload = @zlib_encode($this->payload, ZLIB_ENCODING_DEFLATE, $this->compressionLevel);
		if($this->getProtocol() < ProtocolInfo::PROTOCOL_90){
		    $this->putInt(strlen($payload));
		    $this->put($payload);
		}else{
		    $this->putString($payload);
		}
	}

	public function addPacket(DataPacket $packet) : void{
		if(!$packet->canBeBatched()){
			throw new InvalidArgumentException(get_class($packet) . " cannot be put inside a BatchPacket");
		}
		if(!$packet->isEncoded){
			$packet->encode();
		}

		if($this->getProtocol() < ProtocolInfo::PROTOCOL_90){
		    $this->payload .= Binary::writeInt(strlen($packet->buffer)) . $packet->buffer;
		}else{
	    	$this->payload .= Binary::writeUnsignedVarInt(strlen($packet->buffer)) . $packet->buffer;
		}
	}

	/**
	 * @return Generator
	 */
	public function getPackets(){
	    $stream = new NetworkBinaryStream($this->payload);
	    $count = 0;
	    while(!$stream->feof()){
		    if($count++ > 1024){
			    throw new UnexpectedValueException("Too many packets in a single batch");
		    }
		    yield ($this->getProtocol() < ProtocolInfo::PROTOCOL_90 ? $stream->get($stream->getInt()) : $stream->getString());
	    }
	}

	public function getCompressionLevel() : int{
		return $this->compressionLevel;
	}

	public function setCompressionLevel(int $level) : void{
		$this->compressionLevel = $level;
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		foreach($this->getPackets() as $buf){
			$pk = PacketPool::getPacket($buf, $session->getProtocol());
            $pk->setProtocol($session->getProtocol());

			if(!$pk->canBeBatched() || $pk instanceof OldBatchPacket){
				throw new UnexpectedValueException("Received invalid " . get_class($pk) . " inside BatchPacket");
			}

			$session->handleDataPacket($pk);
		}

		return true;
	}
}
