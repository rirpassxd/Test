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
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use function count;
use function strlen;

class LevelChunkPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::LEVEL_CHUNK_PACKET;

	public const ORDER_COLUMNS = 0;
	public const ORDER_LAYERED = 1;

    /** @var ChunkPosition */
    private $chunkPosition;
    /** @var int */
    private $order = self::ORDER_COLUMNS;
	/** @phpstan-var DimensionIds::* */
	private $dimensionId;
	/** @var int */
	private $subChunkCount;
	/** @var bool */
	private $cacheEnabled;
	/** @var int[] */
	private $usedBlobHashes = [];
	/** @var string */
	private $extraPayload;

    //this appears large enough for a world height of 1024 blocks - it may need to be increased in the future
    private const MAX_BLOB_HASHES = 64;

	public static function withoutCache(ChunkPosition $chunkPosition, int $order, int $dimensionId, int $subChunkCount, string $payload) : self{
		$result = new self;
		$result->chunkPosition = $chunkPosition;
		$result->order = $order;
		$result->dimensionId = $dimensionId;
		$result->subChunkCount = $subChunkCount;
		$result->extraPayload = $payload;

		$result->cacheEnabled = false;

		return $result;
	}

	public static function withCache(ChunkPosition $chunkPosition, int $order, int $dimensionId, int $subChunkCount, array $usedBlobHashes, string $extraPayload) : self{
		(static function(int ...$hashes){})(...$usedBlobHashes);
		$result = new self;
		$result->chunkPosition = $chunkPosition;
		$result->order = $order;
		$result->dimensionId = $dimensionId;
		$result->subChunkCount = $subChunkCount;
		$result->extraPayload = $extraPayload;

		$result->cacheEnabled = true;
		$result->usedBlobHashes = $usedBlobHashes;

		return $result;
	}

    /**
     * @var ChunkPosition
     */
    public function getChunkPosition() : ChunkPosition{
        return $this->chunkPosition;
    }

	/**
	 * @return int
	 */
	public function getOrder() : int{
		return $this->order;
	}

	/**
	 * @return int
	 */
	public function getDimensionId() : int{
		return $this->dimensionId;
	}

	/**
	 * @return int
	 */
	public function getSubChunkCount() : int{
		return $this->subChunkCount;
	}

	/**
	 * @return bool
	 */
	public function isCacheEnabled() : bool{
		return $this->cacheEnabled;
	}

	/**
	 * @return int[]
	 */
	public function getUsedBlobHashes() : array{
		return $this->usedBlobHashes;
	}

	/**
	 * @return string
	 */
	public function getExtraPayload() : string{
		return $this->extraPayload;
	}

	protected function decodePayload() : void{
	    $this->chunkPosition = ChunkPosition::read($this);
	    if($this->getProtocol() < ProtocolInfo::PROTOCOL_92){
	        $this->order = $this->getByte();
	    }
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_360){
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_649){
		    	$this->dimensionId = $this->getVarInt();
			}
	    	$this->subChunkCount = $this->getUnsignedVarInt();
	    	$this->cacheEnabled = $this->getBool();
	    	if($this->cacheEnabled){
				$count = $this->getUnsignedVarInt();
				if($count > self::MAX_BLOB_HASHES){
					throw new \InvalidArgumentException("Expected at most " . self::MAX_BLOB_HASHES . " blob hashes, got " . $count);
				}
				for($i = 0; $i < $count; ++$i){
			    	$this->usedBlobHashes[] = $this->getLLong();
		    	}
		    }
		}
		$this->extraPayload = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getString() : $this->get($this->getInt());
	}

	protected function encodePayload() : void{
	    $this->chunkPosition->write($this);
	    if($this->getProtocol() < ProtocolInfo::PROTOCOL_92){
	        $this->putByte($this->order);
	    }
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_360){
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_649){
				$this->putVarInt($this->dimensionId);
			}
	    	$this->putUnsignedVarInt($this->subChunkCount);
            $this->putBool($this->cacheEnabled);
	    	if($this->cacheEnabled){
		    	$this->putUnsignedVarInt(count($this->usedBlobHashes));
		    	foreach($this->usedBlobHashes as $hash){
                    $this->putLLong($hash);
		    	}
			}
		}
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->putString($this->extraPayload);
		}else{
		    $this->putInt(strlen($this->extraPayload));
		    $this->put($this->extraPayload);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleLevelChunk($this);
	}
}
