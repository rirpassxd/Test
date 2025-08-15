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

/**
 * Implementation of MCPE-style chunks with subchunks with XZY ordering.
 */
declare(strict_types=1);

namespace pocketmine\level\format;

use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\entity\Entity;
use pocketmine\level\biome\Biome;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\chunk\ChunkConverter;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\Player;
use pocketmine\tile\Tile;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Utils;
use pocketmine\world\format\PalettedBlockArray;
use Closure;
use SplFixedArray;
use Throwable;
use function array_fill;
use function array_filter;
use function array_flip;
use function array_values;
use function assert;
use function chr;
use function count;
use function file_get_contents;
use function is_array;
use function json_decode;
use function ord;
use function pack;
use function str_repeat;
use function strlen;
use function unpack;

class Chunk{

	public const MAX_SUBCHUNKS = 16;

	/** @var int */
	protected $x;
	/** @var int */
	protected $z;

	/** @var bool */
	protected $hasChanged = false;

	/** @var bool */
	protected $isInit = false;

	/** @var bool */
	protected $lightPopulated = false;
	/** @var bool */
	protected $terrainGenerated = false;
	/** @var bool */
	protected $terrainPopulated = false;

	/** @var int */
	protected $height = Chunk::MAX_SUBCHUNKS;

	/** @var SplFixedArray|SubChunkInterface[] */
	protected $subChunks;

	/** @var EmptySubChunk */
	protected $emptySubChunk;

	/** @var Tile[] */
	protected $tiles = [];
	/** @var Tile[] */
	protected $tileList = [];

	/** @var Entity[] */
	protected $entities = [];

	/** @var SplFixedArray|int[] */
	protected $heightMap;

	/** @var string */
	protected $biomeIds;

	/** @var CompoundTag[] */
	protected $NBTtiles = [];

	/** @var CompoundTag[] */
	protected $NBTentities = [];

	/**
	 * @param int                 $chunkX
	 * @param int                 $chunkZ
	 * @param SubChunkInterface[] $subChunks
	 * @param CompoundTag[]       $entities
	 * @param CompoundTag[]       $tiles
	 * @param string              $biomeIds
	 * @param int[]               $heightMap
	 */
	public function __construct(int $chunkX, int $chunkZ, array $subChunks = [], array $entities = [], array $tiles = [], string $biomeIds = "", array $heightMap = []){
		$this->x = $chunkX;
		$this->z = $chunkZ;

		$this->height = Chunk::MAX_SUBCHUNKS; //TODO: add a way of changing this

		$this->subChunks = new SplFixedArray($this->height);
		$this->emptySubChunk = EmptySubChunk::getInstance();

		foreach($this->subChunks as $y => $null){
			$this->subChunks[$y] = $subChunks[$y] ?? $this->emptySubChunk;
		}

		if(count($heightMap) === 256){
			$this->heightMap = SplFixedArray::fromArray($heightMap);
		}else{
			assert(count($heightMap) === 0, "Wrong HeightMap value count, expected 256, got " . count($heightMap));
			$val = ($this->height * 16);
			$this->heightMap = SplFixedArray::fromArray(array_fill(0, 256, $val));
		}

		if(strlen($biomeIds) === 256){
			$this->biomeIds = $biomeIds;
		}else{
			assert($biomeIds === "", "Wrong BiomeIds value count, expected 256, got " . strlen($biomeIds));
			$this->biomeIds = str_repeat("\x00", 256);
		}

		$this->NBTtiles = $tiles;
		$this->NBTentities = $entities;
	}

	/**
	 * @return int
	 */
	public function getX() : int{
		return $this->x;
	}

	/**
	 * @return int
	 */
	public function getZ() : int{
		return $this->z;
	}

	public function setX(int $x){
		$this->x = $x;
	}

	/**
	 * @param int $z
	 */
	public function setZ(int $z){
		$this->z = $z;
	}

	/**
	 * Returns the chunk height in count of subchunks.
	 *
	 * @return int
	 */
	public function getHeight() : int{
		return $this->height;
	}

	/**
	 * Returns a bitmap of block ID and meta at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 *
	 * @return int bitmap, (id << 4) | meta
	 */
	public function getFullBlock(int $x, int $y, int $z) : int{
		return $this->getSubChunk($y >> 4)->getFullBlock($x, $y & 0x0f, $z);
	}

	/**
	 * Sets block ID and meta in one call at the specified chunk block coordinates
	 *
	 * @param int      $x 0-15
	 * @param int      $y
	 * @param int      $z 0-15
	 * @param int|null $blockId 0-255 if null, does not change
	 * @param int|null $meta 0-15 if null, does not change
	 *
	 * @return bool
	 */
	public function setBlock(int $x, int $y, int $z, ?int $blockId = null, ?int $meta = null) : bool{
		if($this->getSubChunk($y >> 4, true)->setBlock($x, $y & 0x0f, $z, $blockId, $meta)){
			$this->hasChanged = true;
			return true;
		}
		return false;
	}

	/**
	 * Returns the block ID at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 *
	 * @return int 0-255
	 */
	public function getBlockId(int $x, int $y, int $z) : int{
		return $this->getSubChunk($y >> 4)->getBlockId($x, $y & 0x0f, $z);
	}

	/**
	 * Sets the block ID at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 * @param int $id 0-255
	 */
	public function setBlockId(int $x, int $y, int $z, int $id){
		if($this->getSubChunk($y >> 4, true)->setBlockId($x, $y & 0x0f, $z, $id)){
			$this->hasChanged = true;
		}
	}

	/**
	 * Returns the block meta value at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 *
	 * @return int 0-15
	 */
	public function getBlockData(int $x, int $y, int $z) : int{
		return $this->getSubChunk($y >> 4)->getBlockData($x, $y & 0x0f, $z);
	}

	/**
	 * Sets the block meta value at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 * @param int $data 0-15
	 */
	public function setBlockData(int $x, int $y, int $z, int $data){
		if($this->getSubChunk($y >> 4, true)->setBlockData($x, $y & 0x0f, $z, $data)){
			$this->hasChanged = true;
		}
	}

	/**
	 * Returns the sky light level at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 *
	 * @return int 0-15
	 */
	public function getBlockSkyLight(int $x, int $y, int $z) : int{
		return $this->getSubChunk($y >> 4)->getBlockSkyLight($x, $y & 0x0f, $z);
	}

	/**
	 * Sets the sky light level at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 * @param int $level 0-15
	 */
	public function setBlockSkyLight(int $x, int $y, int $z, int $level){
		if($this->getSubChunk($y >> 4, true)->setBlockSkyLight($x, $y & 0x0f, $z, $level)){
			$this->hasChanged = true;
		}
	}

	/**
	 * @param int $level
	 */
	public function setAllBlockSkyLight(int $level){
		$char = chr(($level & 0x0f) | ($level << 4));
		$data = str_repeat($char, 2048);
		for($y = $this->getHighestSubChunkIndex(); $y >= 0; --$y){
			$this->getSubChunk($y, true)->setBlockSkyLightArray($data);
		}
	}

	/**
	 * Returns the block light level at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y 0-15
	 * @param int $z 0-15
	 *
	 * @return int 0-15
	 */
	public function getBlockLight(int $x, int $y, int $z) : int{
		return $this->getSubChunk($y >> 4)->getBlockLight($x, $y & 0x0f, $z);
	}

	/**
	 * Sets the block light level at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y 0-15
	 * @param int $z 0-15
	 * @param int $level 0-15
	 */
	public function setBlockLight(int $x, int $y, int $z, int $level){
		if($this->getSubChunk($y >> 4, true)->setBlockLight($x, $y & 0x0f, $z, $level)){
			$this->hasChanged = true;
		}
	}

	/**
	 * @param int $level
	 */
	public function setAllBlockLight(int $level){
		$char = chr(($level & 0x0f) | ($level << 4));
		$data = str_repeat($char, 2048);
		for($y = $this->getHighestSubChunkIndex(); $y >= 0; --$y){
			$this->getSubChunk($y, true)->setBlockLightArray($data);
		}
	}

	/**
	 * Returns the Y coordinate of the highest non-air block at the specified X/Z chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return int 0-255, or -1 if there are no blocks in the column
	 */
	public function getHighestBlockAt(int $x, int $z) : int{
		$index = $this->getHighestSubChunkIndex();
		if($index === -1){
			return -1;
		}

		for($y = $index; $y >= 0; --$y){
			$height = $this->getSubChunk($y)->getHighestBlockAt($x, $z) | ($y << 4);
			if($height !== -1){
				return $height;
			}
		}

		return -1;
	}

	public function getMaxY() : int{
		return ($this->getHighestSubChunkIndex() << 4) | 0x0f;
	}

	/**
	 * Returns the heightmap value at the specified X/Z chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return int
	 */
	public function getHeightMap(int $x, int $z) : int{
		return $this->heightMap[($z << 4) | $x];
	}

	/**
	 * Returns the heightmap value at the specified X/Z chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 * @param int $value
	 */
	public function setHeightMap(int $x, int $z, int $value){
		$this->heightMap[($z << 4) | $x] = $value;
	}

	/**
	 * Recalculates the heightmap for the whole chunk.
	 */
	public function recalculateHeightMap(){
		for($z = 0; $z < 16; ++$z){
			for($x = 0; $x < 16; ++$x){
				$this->recalculateHeightMapColumn($x, $z);
			}
		}
	}

	/**
	 * Recalculates the heightmap for the block column at the specified X/Z chunk coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return int New calculated heightmap value (0-256 inclusive)
	 */
	public function recalculateHeightMapColumn(int $x, int $z) : int{
		$max = $this->getHighestBlockAt($x, $z);
		for($y = $max; $y >= 0; --$y){
			if(BlockFactory::$lightFilter[$id = $this->getBlockId($x, $y, $z)] > 1 or BlockFactory::$diffusesSkyLight[$id]){
				break;
			}
		}

		$this->setHeightMap($x, $z, $y + 1);
		return $y + 1;
	}

	/**
	 * Performs basic sky light population on the chunk.
	 * This does not cater for adjacent sky light, this performs direct sky light population only. This may cause some strange visual artifacts
	 * if the chunk is light-populated after being terrain-populated.
	 *
	 * TODO: fast adjacent light spread
	 */
	public function populateSkyLight(){
		$maxY = $this->getMaxY();

		$this->setAllBlockSkyLight(0);

		for($x = 0; $x < 16; ++$x){
			for($z = 0; $z < 16; ++$z){
				$heightMap = $this->getHeightMap($x, $z);

				for($y = $maxY; $y >= $heightMap; --$y){
					$this->setBlockSkyLight($x, $y, $z, 15);
				}

				$light = 15;
				for(; $y >= 0; --$y){
					if($light > 0){
						$light -= BlockFactory::$lightFilter[$this->getBlockId($x, $y, $z)];
						if($light <= 0){
							break;
						}
					}
					$this->setBlockSkyLight($x, $y, $z, $light);
				}
			}
		}
	}

	/**
	 * Returns the biome ID at the specified X/Z chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return int 0-255
	 */
	public function getBiomeId(int $x, int $z) : int{
		return ord($this->biomeIds[($z << 4) | $x]);
	}

	/**
	 * Sets the biome ID at the specified X/Z chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 * @param int $biomeId 0-255
	 */
	public function setBiomeId(int $x, int $z, int $biomeId){
		$this->hasChanged = true;
		$this->biomeIds[($z << 4) | $x] = chr($biomeId & 0xff);
	}

	/**
	 * Returns a column of sky light values from bottom to top at the specified X/Z chunk block coordinates.
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return string
	 */
	public function getBlockSkyLightColumn(int $x, int $z) : string{
		$result = "";
		foreach($this->subChunks as $subChunk){
			$result .= $subChunk->getBlockSkyLightColumn($x, $z);
		}
		return $result;
	}

	/**
	 * Returns a column of block light values from bottom to top at the specified X/Z chunk block coordinates.
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return string
	 */
	public function getBlockLightColumn(int $x, int $z) : string{
		$result = "";
		foreach($this->subChunks as $subChunk){
			$result .= $subChunk->getBlockLightColumn($x, $z);
		}
		return $result;
	}

	/**
	 * @return bool
	 */
	public function isLightPopulated() : bool{
		return $this->lightPopulated;
	}

	/**
	 * @param bool $value
	 */
	public function setLightPopulated(bool $value = true){
		$this->lightPopulated = $value;
	}

	/**
	 * @return bool
	 */
	public function isPopulated() : bool{
		return $this->terrainPopulated;
	}

	/**
	 * @param bool $value
	 */
	public function setPopulated(bool $value = true){
		$this->terrainPopulated = $value;
	}

	/**
	 * @return bool
	 */
	public function isGenerated() : bool{
		return $this->terrainGenerated;
	}

	/**
	 * @param bool $value
	 */
	public function setGenerated(bool $value = true){
		$this->terrainGenerated = $value;
	}

	/**
	 * @param Entity $entity
	 */
	public function addEntity(Entity $entity){
		if($entity->isClosed()){
			throw new InvalidArgumentException("Attempted to add a garbage closed Entity to a chunk");
		}
		$this->entities[$entity->getId()] = $entity;
		if(!($entity instanceof Player) and $this->isInit){
			$this->hasChanged = true;
		}
	}

	/**
	 * @param Entity $entity
	 */
	public function removeEntity(Entity $entity){
		unset($this->entities[$entity->getId()]);
		if(!($entity instanceof Player) and $this->isInit){
			$this->hasChanged = true;
		}
	}

	/**
	 * @param Tile $tile
	 */
	public function addTile(Tile $tile){
		if($tile->isClosed()){
			throw new InvalidArgumentException("Attempted to add a garbage closed Tile to a chunk");
		}
		$this->tiles[$tile->getId()] = $tile;
		if(isset($this->tileList[$index = (($tile->x & 0x0f) << 12) | (($tile->z & 0x0f) << 8) | ($tile->y & 0xff)]) and $this->tileList[$index] !== $tile){
			$this->tileList[$index]->close();
		}
		$this->tileList[$index] = $tile;
		if($this->isInit){
			$this->hasChanged = true;
		}
	}

	/**
	 * @param Tile $tile
	 */
	public function removeTile(Tile $tile){
		unset($this->tiles[$tile->getId()]);
		unset($this->tileList[(($tile->x & 0x0f) << 12) | (($tile->z & 0x0f) << 8) | ($tile->y & 0xff)]);
		if($this->isInit){
			$this->hasChanged = true;
		}
	}

	/**
	 * Returns an array of entities currently using this chunk.
	 *
	 * @return Entity[]
	 */
	public function getEntities() : array{
		return $this->entities;
	}

	/**
	 * @return Entity[]
	 */
	public function getSavableEntities() : array{
		return array_filter($this->entities, function(Entity $entity) : bool{ return $entity->canSaveWithChunk() and !$entity->isClosed(); });
	}

	/**
	 * @return Tile[]
	 */
	public function getTiles() : array{
		return $this->tiles;
	}

	/**
	 * Returns the tile at the specified chunk block coordinates, or null if no tile exists.
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 *
	 * @return Tile|null
	 */
	public function getTile(int $x, int $y, int $z){
		$index = ($x << 12) | ($z << 8) | $y;
		return $this->tileList[$index] ?? null;
	}

	/**
	 * Called when the chunk is unloaded, closing entities and tiles.
	 */
	public function onUnload() : void{
		foreach($this->getEntities() as $entity){
			if($entity instanceof Player){
				continue;
			}
			$entity->close();
		}

		foreach($this->getTiles() as $tile){
			$tile->close();
		}
	}

	/**
	 * Deserializes tiles and entities from NBT
	 *
	 * @param Level $level
	 */
	public function initChunk(Level $level){
		if(!$this->isInit){
			$changed = false;

			$level->timings->syncChunkLoadEntitiesTimer->startTiming();
			foreach($this->NBTentities as $nbt){
				if($nbt instanceof CompoundTag){
					if(!$nbt->hasTag("id")){ //allow mixed types (because of leveldb)
						$changed = true;
						continue;
					}

					try{
						$entity = Entity::createEntity($nbt->getTag("id")->getValue(), $level, $nbt);
						if(!($entity instanceof Entity)){
							$changed = true;
							continue;
						}
					}catch(Throwable $t){
						$level->getServer()->getLogger()->logException($t);
						$changed = true;
						continue;
					}
				}
			}
			$this->NBTentities = [];
			$level->timings->syncChunkLoadEntitiesTimer->stopTiming();

			$level->timings->syncChunkLoadTileEntitiesTimer->startTiming();
			foreach($this->NBTtiles as $nbt){
				if($nbt instanceof CompoundTag){
					if(!$nbt->hasTag(Tile::TAG_ID, StringTag::class)){
						$changed = true;
						continue;
					}

					if(Tile::createTile($nbt->getString(Tile::TAG_ID), $level, $nbt) === null){
						$changed = true;
						continue;
					}
				}
			}

			$this->NBTtiles = [];
			$level->timings->syncChunkLoadTileEntitiesTimer->stopTiming();

			$this->hasChanged = $changed;

			$this->isInit = true;
		}
	}

	/**
	 * @return string
	 */
	public function getBiomeIdArray() : string{
		return $this->biomeIds;
	}

	/**
	 * @return int[]
	 */
	public function getHeightMapArray() : array{
		return $this->heightMap->toArray();
	}

	/**
	 * @return bool
	 */
	public function hasChanged() : bool{
		return $this->hasChanged;
	}

	/**
	 * @param bool $value
	 */
	public function setChanged(bool $value = true){
		$this->hasChanged = $value;
	}

	/**
	 * Returns the subchunk at the specified subchunk Y coordinate, or an empty, unmodifiable stub if it does not exist or the coordinate is out of range.
	 *
	 * @param int  $y
	 * @param bool $generateNew Whether to create a new, modifiable subchunk if there is not one in place
	 *
	 * @return SubChunkInterface
	 */
	public function getSubChunk(int $y, bool $generateNew = false) : SubChunkInterface{
		if($y < 0 or $y >= $this->height){
			return $this->emptySubChunk;
		}elseif($generateNew and $this->subChunks[$y] instanceof EmptySubChunk){
			$this->subChunks[$y] = new SubChunk(Block::AIR << Block::INTERNAL_METADATA_BITS, []);
		}

		return $this->subChunks[$y];
	}

	/**
	 * Sets a subchunk in the chunk index
	 *
	 * @param int                    $y
	 * @param SubChunkInterface|null $subChunk
	 * @param bool                   $allowEmpty Whether to check if the chunk is empty, and if so replace it with an empty stub
	 *
	 * @return bool
	 */
	public function setSubChunk(int $y, SubChunkInterface $subChunk = null, bool $allowEmpty = false) : bool{
		if($y < 0 or $y >= $this->height){
			return false;
		}
		if($subChunk === null or ($subChunk->isEmpty() and !$allowEmpty)){
			$this->subChunks[$y] = $this->emptySubChunk;
		}else{
			$this->subChunks[$y] = $subChunk;
		}
		$this->hasChanged = true;
		return true;
	}

	/**
	 * @return SplFixedArray|SubChunkInterface[]
	 */
	public function getSubChunks() : SplFixedArray{
		return $this->subChunks;
	}

	/**
	 * Returns the Y coordinate of the highest non-empty subchunk in this chunk.
	 *
	 * @return int
	 */
	public function getHighestSubChunkIndex() : int{
		for($y = $this->subChunks->count() - 1; $y >= 0; --$y){
			if($this->subChunks[$y]->isEmpty(false)){
				//No need to thoroughly prune empties at runtime, this will just reduce performance.
				continue;
			}
			break;
		}

		return $y;
	}

	/**
	 * Disposes of empty subchunks and frees data where possible
	 */
	public function collectGarbage() : void{
		foreach($this->subChunks as $y => $subChunk){
			if($subChunk instanceof SubChunk){
				if($subChunk->isEmpty()){
					$this->subChunks[$y] = $this->emptySubChunk;
				}else{
					$subChunk->collectGarbage();
				}
			}
		}
	}

    /**
     * Returns the min/max subchunk index expected in the protocol.
     * This has no relation to the world height supported by PM.
     *
     * @phpstan-param DimensionIds::* $dimensionId
     * @phpstan-param int $playerProtocol
     * @return int[]
     * @phpstan-return array{int, int}
     */
    public static function getDimensionChunkBounds(int $dimensionId, int $playerProtocol) : array{
        if($playerProtocol >= ProtocolInfo::PROTOCOL_475){
            return match($dimensionId){
                DimensionIds::OVERWORLD => [-4, 19],
                DimensionIds::NETHER => [0, 7],
                DimensionIds::THE_END => [0, 15],
                default => throw new InvalidArgumentException("Unknown dimension ID $dimensionId"),
            };
        }elseif($playerProtocol >= ProtocolInfo::PROTOCOL_100){
            return match($dimensionId){
                DimensionIds::OVERWORLD, DimensionIds::THE_END => [0, 15],
                DimensionIds::NETHER => [0, 7],
                default => throw new InvalidArgumentException("Unknown dimension ID $dimensionId"),
            };
        }else{
            return match($dimensionId){
                DimensionIds::OVERWORLD, DimensionIds::THE_END, DimensionIds::NETHER => [0, 7],
                default => throw new InvalidArgumentException("Unknown dimension ID $dimensionId"),
            };
        }
    }

    /**
     * Returns the number of subchunks that will be sent from the given chunk.
     * Chunks are sent in a stack, so every chunk below the top non-empty one must be sent.
     *
     * @phpstan-param DimensionIds::* $dimensionId
     * @phpstan-param int $playerProtocol
     */
    public function getSubChunkSendCount(int $dimensionId, int $playerProtocol) : int{
        //if the protocol world bounds ever exceed the PM supported bounds again in the future, we might need to
        //polyfill some stuff here
        [$minSubChunkIndex, $maxSubChunkIndex] = self::getDimensionChunkBounds($dimensionId, $playerProtocol);
        for($y = $maxSubChunkIndex, $count = $maxSubChunkIndex - $minSubChunkIndex + 1; $y >= $minSubChunkIndex; --$y, --$count){
            if($this->getSubChunk($y)->isEmpty(false)){
                continue;
            }
            return $count;
        }

        return 0;
    }

	/**
	 * Serializes the chunk for sending to players
	 * 
	 * @param int $playerProtocol
	 * @param int $dimensionId
	 * @param ?Closure $legacyToRuntime
	 * 
	 * @return string
	 */
	public function networkSerialize(int $playerProtocol, int $dimensionId, ?Closure $legacyToRuntime) : string{
		$stream = new BinaryStream();
		$subChunkCount = $this->getSubChunkSendCount($dimensionId, $playerProtocol);
		if($playerProtocol >= ProtocolInfo::PROTOCOL_92 && $playerProtocol < ProtocolInfo::PROTOCOL_360){
	    	$stream->putByte($subChunkCount);
		}

        [$minSubChunkIndex, $maxSubChunkIndex] = self::getDimensionChunkBounds($dimensionId, $playerProtocol);
        if($playerProtocol >= ProtocolInfo::PROTOCOL_92){
            for($writtenCount = 0, $y = $minSubChunkIndex; $writtenCount < $subChunkCount; ++$y, ++$writtenCount){
		        $this->networkSerializeSubChunk($this->getSubChunk($y), $playerProtocol, $legacyToRuntime, $stream);
	        }
        }else{
            $palettedBlocks = [];
            for($writtenCount = 0, $y = $minSubChunkIndex; $writtenCount < $subChunkCount; ++$y, ++$writtenCount){
                $palettedBlocks[] = $this->getSubChunk($y)->getBlockLayers()[0] ?? new PalettedBlockArray(Block::AIR);
            }

            [$blockIdArray, $blockDataArray] = ChunkConverter::convertSubChunkFromPaletteColumn($palettedBlocks, $playerProtocol);
            $stream->put($blockIdArray);
            $stream->put($blockDataArray);

            for($y = 0; $y < 8; $y++){
                $stream->put(ChunkConverter::unreorderNibbleArray($this->getSubChunk($y)->getBlockSkyLightArray(), "\xff"));
            }

            for($y = 0; $y < 8; $y++){
                $stream->put(ChunkConverter::unreorderNibbleArray($this->getSubChunk($y)->getBlockLightArray()));
            }
        }

		if($playerProtocol >= ProtocolInfo::PROTOCOL_475){
	        //TODO: right now we don't support 3D natively, so we just 3Dify our 2D biomes so they fill the column
	        $encodedBiomePalette = $this->networkSerializeBiomesAsPalette();
	        for($y = $minSubChunkIndex; $y <= $maxSubChunkIndex; ++$y){
	            $stream->put($encodedBiomePalette);
	        }
		}else{
		    if($playerProtocol < ProtocolInfo::PROTOCOL_360){
		        $stream->put(pack(($playerProtocol < ProtocolInfo::PROTOCOL_92 ? "C*" : "v*"), ...$this->heightMap));
		    }

		    $stream->put(($playerProtocol < ProtocolInfo::PROTOCOL_92 ?
		        $this->convertChunkBiomeColorsFromBiomeIds() : 
		        $this->biomeIds
		    ));
		}
		if($playerProtocol >= ProtocolInfo::PROTOCOL_92){
	    	$stream->putByte(0); //border block array count
	        //Border block entry format: 1 byte (4 bits X, 4 bits Z). These are however useless since they crash the regular client.
	    	if($playerProtocol < ProtocolInfo::PROTOCOL_270){
		        $stream->putVarInt(0); // extraData (WTF)
	    	}
		}else{
		    $stream->putLInt(0); // extraData
		}

		return $stream->getBuffer();
	}

	public function networkSerializeSubChunk(SubChunkInterface $subChunk, int $playerProtocol, ?Closure $legacyToRuntime, BinaryStream $stream) : void{
		if($legacyToRuntime === null){
		    $stream->putByte(0); // storage version

			[$blockIdArray, $blockDataArray] = ChunkConverter::convertSubChunkFromPaletteXZY($subChunk->getBlockLayers()[0] ?? new PalettedBlockArray(Block::AIR), $playerProtocol);

		    $stream->put($blockIdArray);
		    $stream->put($blockDataArray);
		    if($playerProtocol < ProtocolInfo::PROTOCOL_130){
			    $stream->put($subChunk->getBlockSkyLightArray());
			    $stream->put($subChunk->getBlockLightArray());
		    }
	    }else{
			Utils::validateCallableSignature(function(int $blockId, int $meta) : int{}, $legacyToRuntime);

			$blockLayers = $subChunk->getBlockLayers();

			if($playerProtocol < ProtocolInfo::PROTOCOL_290){
				$stream->putByte(1); // storage version (paletted v1, single blockstorage)

				$blocks = $blockLayers[0] ?? new PalettedBlockArray(Block::AIR);
               	// 1 is network format (palette out of runtimeIDs), 0 is storage format (palette out of NBT tags)
				if($blocks->getBitsPerBlock() === 0){
                	//TODO: we use these in memory, but the game doesn't support them yet
                    //polyfill them with 1-bpb instead
                	$bitsPerBlock = 1;
                	$words = str_repeat("\x00", PalettedBlockArray::getExpectedWordArraySize(1));
            	}else{
                	$bitsPerBlock = $blocks->getBitsPerBlock();
                	$words = $blocks->getWordArray();
                }
            	$stream->putByte(($bitsPerBlock << 1) | 1);
            	$stream->put($words);
            	$palette = $blocks->getPalette();

                //these LSHIFT by 1 uvarints are optimizations: the client expects zigzag varints here
            	//but since we know they are always unsigned, we can avoid the extra fcall overhead of
            	//zigzag and just shift directly.
            	$stream->putUnsignedVarInt(count($palette) << 1);
			    foreach($palette as $fullBlock){
					$block = BlockFactory::get($fullBlock >> 4, $fullBlock & 0x0f);
					$block = $block->getBlockProtocol($playerProtocol) ?? $block;

			    	$runtimeId = $legacyToRuntime($block->getId(), $block->getDamage());
			    	$stream->putUnsignedVarInt($runtimeId << 1);
				}
			}else{
				$stream->putByte(8); // storage version
				$stream->putByte(count($blockLayers)); // layer count

				foreach($blockLayers as $blocks){
               		// 1 is network format (palette out of runtimeIDs), 0 is storage format (palette out of NBT tags)
                	if($blocks->getBitsPerBlock() === 0){
                    	//TODO: we use these in memory, but the game doesn't support them yet
                    	//polyfill them with 1-bpb instead
                    	$bitsPerBlock = 1;
                    	$words = str_repeat("\x00", PalettedBlockArray::getExpectedWordArraySize(1));
                	}else{
                    	$bitsPerBlock = $blocks->getBitsPerBlock();
                    	$words = $blocks->getWordArray();
                	}
                	$stream->putByte(($bitsPerBlock << 1) | 1);
                	$stream->put($words);
                	$palette = $blocks->getPalette();

                	//these LSHIFT by 1 uvarints are optimizations: the client expects zigzag varints here
                	//but since we know they are always unsigned, we can avoid the extra fcall overhead of
                	//zigzag and just shift directly.
                	$stream->putUnsignedVarInt(count($palette) << 1);
			    	foreach($palette as $fullBlock){
						$block = BlockFactory::get($fullBlock >> 4, $fullBlock & 0x0f);
						$block = $block->getBlockProtocol($playerProtocol) ?? $block;

			    		$runtimeId = $legacyToRuntime($block->getId(), $block->getDamage());
			    		$stream->putUnsignedVarInt($runtimeId << 1);
					}
				}
			}
		}
	}

	private function networkSerializeBiomesAsPalette() : string{
		/** @var string[]|null $biomeIdMap */
		static $biomeIdMap = null;
		if($biomeIdMap === null){
			$biomeIdMapRaw = file_get_contents(\pocketmine\RESOURCE_PATH . '/vanilla/biome_id_map.json');
			if($biomeIdMapRaw === false) throw new AssumptionFailedError();
			$biomeIdMapDecoded = json_decode($biomeIdMapRaw, true);
			if(!is_array($biomeIdMapDecoded)) throw new AssumptionFailedError();
			$biomeIdMap = array_flip($biomeIdMapDecoded);
		}
		$biomePalette = new PalettedBlockArray($this->getBiomeId(0, 0));
		for($x = 0; $x < 16; ++$x){
			for($z = 0; $z < 16; ++$z){
				$biomeId = $this->getBiomeId($x, $z);
				if(!isset($biomeIdMap[$biomeId])){
					//make sure we aren't sending bogus biomes - the 1.18.0 client crashes if we do this
					$biomeId = Biome::OCEAN;
				}
				for($y = 0; $y < 16; ++$y){
					$biomePalette->set($x, $y, $z, $biomeId);
				}
			}
		}

		$biomePaletteBitsPerBlock = $biomePalette->getBitsPerBlock();
		$encodedBiomePalette =
			chr(($biomePaletteBitsPerBlock << 1) | 1) . //the last bit is non-persistence (like for blocks), though it has no effect on biomes since they always use integer IDs
			$biomePalette->getWordArray();

		//these LSHIFT by 1 uvarints are optimizations: the client expects zigzag varints here
		//but since we know they are always unsigned, we can avoid the extra fcall overhead of
		//zigzag and just shift directly.
		$biomePaletteArray = $biomePalette->getPalette();
		if($biomePaletteBitsPerBlock !== 0){
			$encodedBiomePalette .= Binary::writeUnsignedVarInt(count($biomePaletteArray) << 1);
		}
		foreach($biomePaletteArray as $p){
			$encodedBiomePalette .= Binary::writeUnsignedVarInt($p << 1);
		}

		return $encodedBiomePalette;
	}

    /**
    * Converts a biome ID string (256 bytes) back to a pre-MCPE-1.0 biome color array.
    * 
    * @param string $biomeIds
    * 
    * @return string
    */
    public function convertChunkBiomeColorsFromBiomeIds() : string{
        /** @var string[]|null $biomeColorMap */
		static $biomeColorMap = null;
		if($biomeColorMap === null){
			$biomeColorMapRaw = file_get_contents(\pocketmine\RESOURCE_PATH . '/vanilla/biome_color_map.json');
			if($biomeColorMapRaw === false) throw new AssumptionFailedError();
			$biomeColorMapDecoded = json_decode($biomeColorMapRaw, true);
			if(!is_array($biomeColorMapDecoded)) throw new AssumptionFailedError();
			$biomeColorMap = $biomeColorMapDecoded;
		}

        $result = [];
        for($i = 0; $i < 256; $i++){
            $result[$i] = $biomeColorMap[$this->biomeIds[$i]] ?? 0x8DB371; // 0x8DB371 - ocean biome color
        }

        return pack('N*', ...$result);
    }

	/**
	 * Fast-serializes the chunk for passing between threads
	 * TODO: tiles and entities
	 *
	 * @return string
	 */
	public function fastSerialize() : string{
		$stream = new BinaryStream();
		$stream->putInt($this->x);
		$stream->putInt($this->z);
		$stream->putByte(($this->lightPopulated ? 4 : 0) | ($this->terrainPopulated ? 2 : 0) | ($this->terrainGenerated ? 1 : 0));
		if($this->terrainGenerated){
			//subchunks
			$count = 0;
			$subChunks = "";
			foreach($this->subChunks as $y => $subChunk){
				++$count;
				$subChunks .= chr($y);
				$newStream = new BinaryStream();
				$newStream->putInt($subChunk->getEmptyBlockId());
				$layers = $subChunk->getBlockLayers();
				$newStream->putByte(count($layers));
				foreach($layers as $blocks){
					$wordArray = $blocks->getWordArray();
					$palette = $blocks->getPalette();
		
					$newStream->putByte($blocks->getBitsPerBlock());
					$newStream->put($wordArray);
					$serialPalette = pack("L*", ...$palette);
					$newStream->putInt(strlen($serialPalette));
					$newStream->put($serialPalette);
				}
				$subChunks .= $newStream->getBuffer();
				if($this->lightPopulated){
					$subChunks .= $subChunk->getBlockSkyLightArray() . $subChunk->getBlockLightArray();
				}
			}
			$stream->putByte($count);
			$stream->put($subChunks);

			//biomes
			$stream->put($this->biomeIds);
			if($this->lightPopulated){
				$stream->put(pack("v*", ...$this->heightMap));
			}
		}

		return $stream->getBuffer();
	}

	/**
	 * Deserializes a fast-serialized chunk
	 *
	 * @param string $data
	 *
	 * @return Chunk
	 */
	public static function fastDeserialize(string $data) : Chunk{
		$stream = new BinaryStream($data);

		$x = $stream->getInt();
		$z = $stream->getInt();
		$flags = $stream->getByte();
		$lightPopulated = (bool) ($flags & 4);
		$terrainPopulated = (bool) ($flags & 2);
		$terrainGenerated = (bool) ($flags & 1);

		$subChunks = [];
		$biomeIds = "";
		$heightMap = [];
		if($terrainGenerated){
			$count = $stream->getByte();
			for($y = 0; $y < $count; ++$y){
				$y = $stream->getByte();

				$airBlockId = $stream->getInt();

				/** @var PalettedBlockArray[] $layers */
				$layers = [];
				for($i = 0, $layerCount = $stream->getByte(); $i < $layerCount; ++$i){
					$bitsPerBlock = $stream->getByte();
					$words = $stream->get(PalettedBlockArray::getExpectedWordArraySize($bitsPerBlock));
					/** @var int[] $unpackedPalette */
					$unpackedPalette = unpack("L*", $stream->get($stream->getInt())); //unpack() will never fail here
					$palette = array_values($unpackedPalette);

					$layers[] = PalettedBlockArray::fromData($bitsPerBlock, $words, $palette);
				}

				$subChunks[$y] = new SubChunk(
					$airBlockId,
					$layers,
					$lightPopulated ? $stream->get(2048) : "", //skylight
					$lightPopulated ? $stream->get(2048) : "" //blocklight
				);
			}

			$biomeIds = $stream->get(256);
			if($lightPopulated){
				$heightMap = array_values(unpack("v*", $stream->get(512)));
			}
		}

		$chunk = new Chunk($x, $z, $subChunks, [], [], $biomeIds, $heightMap);
		$chunk->setGenerated($terrainGenerated);
		$chunk->setPopulated($terrainPopulated);
		$chunk->setLightPopulated($lightPopulated);

		return $chunk;
	}
}
