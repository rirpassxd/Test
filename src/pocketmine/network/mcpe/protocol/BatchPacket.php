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

use ErrorException;
use Generator;
use InvalidArgumentException;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\utils\Binary;
use UnexpectedValueException;
use function assert;
use function chr;
use function get_class;
use function ord;
use function strlen;
use function zlib_decode;
use function zlib_encode;
use const ZLIB_ENCODING_RAW;


class BatchPacket extends DataPacket{
	public const NETWORK_ID = 0xfe;

	/** @var string */
	public $payload = "";
	/** @var bool */
	public $compressionEnabled = true;
	/** @var int */
	protected $compressionLevel = 7;

	public function canBeBatched() : bool{
		return false;
	}

	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	protected function decodeHeader(){
		$pid = $this->getByte();
		assert($pid === static::NETWORK_ID);
	}

	protected function decodePayload(){
		$this->payload = $this->getRemaining();
	}

	protected function encodeHeader(){
        $this->putByte(static::NETWORK_ID);
	}

	protected function encodePayload(){
	    if($this->compressionEnabled){
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_649){
				$payload = chr(0x00) . zlib_encode($this->payload, ZLIB_ENCODING_RAW, $this->compressionLevel);
			}else{
	        	$payload = zlib_encode($this->payload, ($this->getProtocol() >= ProtocolInfo::PROTOCOL_401 ? ZLIB_ENCODING_RAW : ZLIB_ENCODING_DEFLATE), $this->compressionLevel);
			}
	    }else{
	        $payload = $this->payload;
	    }
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
            $this->put($payload);
		}else{
		    if($this->getProtocol() < ProtocolInfo::PROTOCOL_90){
		        $this->putInt(strlen($payload));
		        $this->put($payload);
		    }else{
		        $this->putString($payload);
		    }
		}
	}

	/**
	 * @param DataPacket $packet
	 */
	public function addPacket(DataPacket $packet){
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
			yield $stream->getString();
		}
	}

	public function getCompressionLevel() : int{
		return $this->compressionLevel;
	}

	public function setCompressionLevel(int $level){
		$this->compressionLevel = $level;
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
	    if($session->isCompressionEnabled()){
	        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	            try{
			    	if($this->getProtocol() < ProtocolInfo::PROTOCOL_649){
				    	$this->payload = zlib_decode($this->payload, 1024 * 1024 * 2); //Max 2MB
			    	}else{
			        	if((ord($this->payload[0])) === 0x0){
	                        $this->payload = zlib_decode((substr($this->payload, 1)), 1024 * 1024 * 2); //Max 2MB
			        	}elseif($this->payload[0] === "\xff"){
					        $this->payload = substr($this->payload, 1);
				    	}
			    	}
	            }catch(ErrorException $e){ //zlib decode error
	                $this->payload = Binary::writeUnsignedVarInt(strlen($this->payload)) . $this->payload; // for <= 1.0
	            }
	        }else{
	            $this->payload = Binary::writeUnsignedVarInt(strlen($this->payload)) . $this->payload; // for <= 1.0
	        }
	    }

		foreach($this->getPackets() as $buf){
			$pk = PacketPool::getPacket($buf, $session->getProtocol());
            $pk->setProtocol($session->getProtocol());

			if(!$pk->canBeBatched()){
				throw new UnexpectedValueException("Received invalid " . get_class($pk) . " inside BatchPacket");
			}

			$session->handleDataPacket($pk);
		}

		return true;
	}
}
