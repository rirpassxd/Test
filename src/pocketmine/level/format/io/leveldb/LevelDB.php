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

namespace pocketmine\level\format\io\leveldb;

use pocketmine\level\format\Chunk;
use pocketmine\level\format\io\BaseLevelProvider;
use pocketmine\level\format\io\ChunkUtils;
use pocketmine\level\format\io\exception\UnsupportedChunkFormatException;
use pocketmine\level\format\io\exception\CorruptedChunkException;
use pocketmine\level\format\SubChunk;
use pocketmine\level\generator\Flat;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\level\Level;
use pocketmine\level\LevelException;
use pocketmine\nbt\LittleEndianNBTStream;
use pocketmine\nbt\tag\{ByteTag, CompoundTag, FloatTag, IntTag, LongTag, StringTag, ShortTag};
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\utils\BinaryStream;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\io\SubChunkConverter;
use pocketmine\block\Block;
use function array_values;
use function array_flip;
use function json_decode;
use function chr;
use function defined;
use function explode;
use function extension_loaded;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_dir;
use function mkdir;
use function ord;
use function pack;
use function rtrim;
use function strlen;
use function str_repeat;
use function substr;
use function time;
use function trim;
use function unpack;
use const INT32_MAX;
use const LEVELDB_ZLIB_RAW_COMPRESSION;
use const pocketmine\RESOURCE_PATH;
use InvalidArgumentException;
use Exception;

class LevelDB extends BaseLevelProvider{

	//According to Tomasso, these aren't supposed to be readable anymore. Thankfully he didn't change the readable ones...
	public const TAG_DATA_2D = "\x2d";
	public const TAG_DATA_2D_LEGACY = "\x2e";
	public const TAG_SUBCHUNK_PREFIX = "\x2f";
	public const TAG_LEGACY_TERRAIN = "0";
	public const TAG_BLOCK_ENTITY = "1";
	public const TAG_ENTITY = "2";
	public const TAG_PENDING_TICK = "3";
	public const TAG_BLOCK_EXTRA_DATA = "4";
	public const TAG_BIOME_STATE = "5";
	public const TAG_STATE_FINALISATION = "6";

	public const TAG_BORDER_BLOCKS = "8";
	public const TAG_HARDCODED_SPAWNERS = "9";

	public const FINALISATION_NEEDS_INSTATICKING = 0;
	public const FINALISATION_NEEDS_POPULATION = 1;
	public const FINALISATION_DONE = 2;

	public const TAG_VERSION = "v";

	public const ENTRY_FLAT_WORLD_LAYERS = "game_flatworldlayers";

	public const GENERATOR_LIMITED = 0;
	public const GENERATOR_INFINITE = 1;
	public const GENERATOR_FLAT = 2;

	public const CURRENT_STORAGE_VERSION = 6; //Current MCPE level format version
	public const CURRENT_LEVEL_CHUNK_VERSION = 7;
	public const CURRENT_LEVEL_SUBCHUNK_VERSION = 8;

	/** @var \LevelDB */
	protected $db;

	private static $cacheBlockIdMap = null;
	private static $cacheBlockIdMapFlip = null;

	private static function checkForLevelDBExtension(){
		if(!extension_loaded('leveldb')){
			throw new LevelException("The leveldb PHP extension is required to use this world format");
		}

		if(!defined('LEVELDB_ZLIB_RAW_COMPRESSION')){
			throw new LevelException("Given version of php-leveldb doesn't support zlib raw compression");
		}
	}

	private static function createDB(string $path) : \LevelDB{
		return new \LevelDB($path . "/db", [
			"compression" => LEVELDB_ZLIB_RAW_COMPRESSION
		]);
	}

	public function __construct(string $path){
		self::checkForLevelDBExtension();
		parent::__construct($path);

		$this->db = self::createDB($path);
	}

	protected function loadLevelData() : void{
		$nbt = new LittleEndianNBTStream();
		$levelData = $nbt->read(substr(file_get_contents($this->getPath() . "level.dat"), 8));
		if($levelData instanceof CompoundTag){
			$this->levelData = $levelData;
		}else{
			throw new LevelException("Invalid level.dat");
		}

		$version = $this->levelData->getInt("StorageVersion", INT32_MAX, true);
		if($version > self::CURRENT_STORAGE_VERSION){
			throw new LevelException("Specified LevelDB world format version ($version) is not supported");
		}
	}

	protected function fixLevelData() : void{
		$db = self::createDB($this->path);

		if(!$this->levelData->hasTag("generatorName", StringTag::class)){
			if($this->levelData->hasTag("Generator", IntTag::class)){
				switch($this->levelData->getInt("Generator")){ //Detect correct generator from MCPE data
					case self::GENERATOR_FLAT:
						$this->levelData->setString("generatorName", "flat");
						if(($layers = $db->get(self::ENTRY_FLAT_WORLD_LAYERS)) !== false){ //Detect existing custom flat layers
							$layers = trim($layers, "[]");
						}else{
							$layers = "7,3,3,2";
						}
						$this->levelData->setString("generatorOptions", "2;" . $layers . ";1");
						break;
					case self::GENERATOR_INFINITE:
						//TODO: add a null generator which does not generate missing chunks (to allow importing back to MCPE and generating more normal terrain without PocketMine messing things up)
						$this->levelData->setString("generatorName", "default");
						$this->levelData->setString("generatorOptions", "");
						break;
					case self::GENERATOR_LIMITED:
						throw new LevelException("Limited worlds are not currently supported");
					default:
						throw new LevelException("Unknown LevelDB world format type, this level cannot be loaded");
				}
			}else{
				$this->levelData->setString("generatorName", "default");
			}
		}elseif(($generatorName = self::hackyFixForGeneratorClasspathInLevelDat($this->levelData->getString("generatorName"))) !== null){
			$this->levelData->setString("generatorName", $generatorName);
		}

		if(!$this->levelData->hasTag("generatorOptions", StringTag::class)){
			$this->levelData->setString("generatorOptions", "");
		}
	}

	public static function getProviderName() : string{
		return "leveldb";
	}

	public function getWorldHeight() : int{
		return 256;
	}

	public static function isValid(string $path) : bool{
		return file_exists($path . "/level.dat") and is_dir($path . "/db/");
	}

	public static function generate(string $path, string $name, int $seed, string $generator, array $options = []){
		self::checkForLevelDBExtension();

		if(!file_exists($path . "/db")){
			mkdir($path . "/db", 0777, true);
		}

		switch($generator){
			case Flat::class:
				$generatorType = self::GENERATOR_FLAT;
				break;
			default:
				$generatorType = self::GENERATOR_INFINITE;
			//TODO: add support for limited worlds
		}

		$levelData = new CompoundTag("", [
			//Vanilla fields
			new IntTag("DayCycleStopTime", -1),
			new IntTag("Difficulty", Level::getDifficultyFromString((string) ($options["difficulty"] ?? "normal"))),
			new ByteTag("ForceGameType", 0),
			new IntTag("GameType", 0),
			new IntTag("Generator", $generatorType),
			new LongTag("LastPlayed", time()),
			new StringTag("LevelName", $name),
			new IntTag("NetworkVersion", ProtocolInfo::CURRENT_PROTOCOL),
			//new IntTag("Platform", 2), //TODO: find out what the possible values are for
			new LongTag("RandomSeed", $seed),
			new IntTag("SpawnX", 0),
			new IntTag("SpawnY", 32767),
			new IntTag("SpawnZ", 0),
			new IntTag("StorageVersion", self::CURRENT_STORAGE_VERSION),
			new LongTag("Time", 0),
			new ByteTag("eduLevel", 0),
			new ByteTag("falldamage", 1),
			new ByteTag("firedamage", 1),
			new ByteTag("hasBeenLoadedInCreative", 1), //badly named, this actually determines whether achievements can be earned in this world...
			new ByteTag("immutableWorld", 0),
			new FloatTag("lightningLevel", 0.0),
			new IntTag("lightningTime", 0),
			new ByteTag("pvp", 1),
			new FloatTag("rainLevel", 0.0),
			new IntTag("rainTime", 0),
			new ByteTag("spawnMobs", 1),
			new ByteTag("texturePacksRequired", 0), //TODO

			//Additional PocketMine-MP fields
			new CompoundTag("GameRules", []),
			new ByteTag("hardcore", ($options["hardcore"] ?? false) === true ? 1 : 0),
			new StringTag("generatorName", GeneratorManager::getGeneratorName($generator)),
			new StringTag("generatorOptions", $options["preset"] ?? "")
		]);

		$nbt = new LittleEndianNBTStream();
		$buffer = $nbt->write($levelData);
		file_put_contents($path . "level.dat", (pack("V", self::CURRENT_STORAGE_VERSION)) . (pack("V", strlen($buffer))) . $buffer);


		$db = self::createDB($path);

		if($generatorType === self::GENERATOR_FLAT and isset($options["preset"])){
			$layers = explode(";", $options["preset"])[1] ?? "";
			if($layers !== ""){
				$out = "[";
				foreach(Flat::parseLayers($layers) as $result){
					$out .= $result[0] . ","; //only id, meta will unfortunately not survive :(
				}
				$out = rtrim($out, ",") . "]"; //remove trailing comma
				$db->put(self::ENTRY_FLAT_WORLD_LAYERS, $out); //Add vanilla flatworld layers to allow terrain generation by MCPE to continue seamlessly
			}
		}
	}

	public function saveLevelData(){
		$this->levelData->setInt("NetworkVersion", ProtocolInfo::CURRENT_PROTOCOL);
		$this->levelData->setInt("StorageVersion", self::CURRENT_STORAGE_VERSION);

		$nbt = new LittleEndianNBTStream();
		$buffer = $nbt->write($this->levelData);
		file_put_contents($this->getPath() . "level.dat", (pack("V", self::CURRENT_STORAGE_VERSION)) . (pack("V", strlen($buffer))) . $buffer);
	}

	public function getGenerator() : string{
		return $this->levelData->getString("generatorName", "");
	}

	public function getGeneratorOptions() : array{
		return ["preset" => $this->levelData->getString("generatorOptions", "")];
	}

	public function getDifficulty() : int{
		return $this->levelData->getInt("Difficulty", Level::DIFFICULTY_NORMAL);
	}

	public function setDifficulty(int $difficulty){
		$this->levelData->setInt("Difficulty", $difficulty); //yes, this is intended! (in PE: int, PC: byte)
	}

    /**
     * @throws CorruptedChunkException
     */
    protected function deserializePaletted(BinaryStream $stream) : PalettedBlockArray{
        $bitsPerBlock = $stream->getByte() >> 1;

        try{
            $words = $stream->get(PalettedBlockArray::getExpectedWordArraySize($bitsPerBlock));
        }catch(InvalidArgumentException $e){
            throw new CorruptedChunkException("Failed to deserialize paletted storage: " . $e->getMessage(), 0, $e);
        }
        $nbt = new LittleEndianNBTStream();
        $palette = [];

        if(self::$cacheBlockIdMap === null){
            self::$cacheBlockIdMap = json_decode(file_get_contents(RESOURCE_PATH . "/vanilla/palette/block_id_map407.json"), true);
        }
        for($i = 0, $paletteSize = $stream->getLInt(); $i < $paletteSize; ++$i){
            try{
                $offset = $stream->getOffset();

                $tag = $nbt->read($stream->getBuffer(), false, $offset);
                /** @var CompoundTag $tag */
                $stream->setOffset($offset);

                $id = self::$cacheBlockIdMap[$tag->getString("name")] ?? Block::INFO_UPDATE;
                $data = $tag->getShort("val");
                $palette[] = ($id << Block::INTERNAL_METADATA_BITS) | $data;
            }catch(Exception $e){
                throw new CorruptedChunkException("Invalid blockstate NBT at offset $i in paletted storage: " . $e->getMessage(), 0, $e);
            }
        }

        //TODO: exceptions
        return PalettedBlockArray::fromData($bitsPerBlock, $words, $palette);
    }

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @return Chunk|null
	 * @throws UnsupportedChunkFormatException
	 */
	protected function readChunk(int $chunkX, int $chunkZ) : ?Chunk{
		$index = LevelDB::chunkIndex($chunkX, $chunkZ);

		if(!$this->chunkExists($chunkX, $chunkZ)){
			return null;
		}

		/** @var SubChunk[] $subChunks */
		$subChunks = [];

		/** @var int[] $heightMap */
		$heightMap = [];
		/** @var string $biomeIds */
		$biomeIds = "";

		/** @var bool $lightPopulated */
		$lightPopulated = true;

		$chunkVersion = ord($this->db->get($index . self::TAG_VERSION));
		$hasBeenUpgraded = $chunkVersion < self::CURRENT_LEVEL_CHUNK_VERSION;

		$binaryStream = new BinaryStream();

		switch($chunkVersion){
			case 15: //MCPE 1.12.0.4 beta (???)
			case 14: //MCPE 1.11.1.2 (???)
			case 13: //MCPE 1.11.0.4 beta (???)
			case 12: //MCPE 1.11.0.3 beta (???)
			case 11: //MCPE 1.11.0.1 beta (???)
			case 10: //MCPE 1.9 (???)
			case 9: //MCPE 1.8 (???)
			case 8: //MCPE 1.2.13 (paletted subchunks)
			case 7: //MCPE 1.2 (???)
			case 6: //MCPE 1.2.0.2 beta (???)
			case 5: //MCPE 1.1
			case 4: //MCPE 1.1
			case 3: //MCPE 1.0
				for($y = 0; $y < Chunk::MAX_SUBCHUNKS; ++$y){
					if(($data = $this->db->get($index . self::TAG_SUBCHUNK_PREFIX . chr($y))) === false){
						continue;
					}

					$binaryStream->setBuffer($data, 0);
					$subChunkVersion = $binaryStream->getByte();
					if($subChunkVersion < self::CURRENT_LEVEL_SUBCHUNK_VERSION){
						$hasBeenUpgraded = true;
					}

					switch($subChunkVersion){
						case 0:
						case 2: //these are all identical to version 0, but vanilla respects these so we should also
						case 3:
						case 4:
						case 5:
						case 6:
						case 7:
							$blocks = $binaryStream->get(4096);
							$blockData = $binaryStream->get(2048);
							if($chunkVersion < 4){
								$blockSkyLight = $binaryStream->get(2048);
								$blockLight = $binaryStream->get(2048);
								$hasBeenUpgraded = true; //drop saved light
							}else{
								//Mojang didn't bother changing the subchunk version when they stopped saving sky light -_-
								$blockSkyLight = "";
								$blockLight = "";
								$lightPopulated = false;
							}

							$subChunks[$y] = new SubChunk(Block::AIR << Block::INTERNAL_METADATA_BITS, [SubChunkConverter::convertSubChunkXZY($blocks, $blockData)], $blockSkyLight, $blockLight);
							break;
						case 1: //paletted v1, has a single blockstorage
							$storages = [$this->deserializePaletted($binaryStream)];
							$subChunks[$y] = new SubChunk(Block::AIR << Block::INTERNAL_METADATA_BITS, $storages);
							break;
						case 8:
							//legacy extradata layers intentionally ignored because they aren't supposed to exist in v8
							$storageCount = $binaryStream->getByte();
							if($storageCount > 0){
								$storages = [];

								for($k = 0; $k < $storageCount; ++$k){
									$storages[] = $this->deserializePaletted($binaryStream);
								}
								$subChunks[$y] = new SubChunk(Block::AIR << Block::INTERNAL_METADATA_BITS, $storages);
							}
							break;
						default:
							//TODO: set chunks read-only so the version on disk doesn't get overwritten
							throw new UnsupportedChunkFormatException("don't know how to decode LevelDB subchunk format version $subChunkVersion");
					}
				}

				if(($maps2d = $this->db->get($index . self::TAG_DATA_2D)) !== false){
					$binaryStream->setBuffer($maps2d, 0);

					$heightMap = array_values(unpack("v*", $binaryStream->get(512)));
					$biomeIds = $binaryStream->get(256);
				}
				break;
			case 2: // < MCPE 1.0
			case 1:
			case 0: //MCPE 0.9.0.1 beta (first version)
				$binaryStream->setBuffer($this->db->get($index . self::TAG_LEGACY_TERRAIN));
				$fullIds = $binaryStream->get(32768);
				$fullData = $binaryStream->get(16384);
				$fullSkyLight = $binaryStream->get(16384);
				$fullBlockLight = $binaryStream->get(16384);

				for($yy = 0; $yy < 8; ++$yy){
					$subOffset = ($yy << 4);
					$ids = "";
					for($i = 0; $i < 256; ++$i){
						$ids .= substr($fullIds, $subOffset, 16);
						$subOffset += 128;
					}
					$data = "";
					$subOffset = ($yy << 3);
					for($i = 0; $i < 256; ++$i){
						$data .= substr($fullData, $subOffset, 8);
						$subOffset += 64;
					}
					$skyLight = "";
					$subOffset = ($yy << 3);
					for($i = 0; $i < 256; ++$i){
						$skyLight .= substr($fullSkyLight, $subOffset, 8);
						$subOffset += 64;
					}
					$blockLight = "";
					$subOffset = ($yy << 3);
					for($i = 0; $i < 256; ++$i){
						$blockLight .= substr($fullBlockLight, $subOffset, 8);
						$subOffset += 64;
					}
					$subChunks[$yy] = new SubChunk(Block::AIR << Block::INTERNAL_METADATA_BITS, [SubChunkConverter::convertSubChunkXZY($ids, $data)], $skyLight, $blockLight);
				}

				$heightMap = array_values(unpack("C*", $binaryStream->get(256)));
				$biomeIds = ChunkUtils::convertBiomeColors(array_values(unpack("N*", $binaryStream->get(1024))));
				break;
			default:
				//TODO: set chunks read-only so the version on disk doesn't get overwritten
				throw new UnsupportedChunkFormatException("don't know how to decode chunk format version $chunkVersion");
		}

		$nbt = new LittleEndianNBTStream();

		/** @var CompoundTag[] $entities */
		$entities = [];
		if(($entityData = $this->db->get($index . self::TAG_ENTITY)) !== false and $entityData !== ""){
			$entities = $nbt->read($entityData, true);
			if(!is_array($entities)){
				$entities = [$entities];
			}
		}

		/** @var CompoundTag $entityNBT */
		foreach($entities as $entityNBT){
			if($entityNBT->hasTag("id", IntTag::class)){
				$entityNBT->setInt("id", $entityNBT->getInt("id") & 0xff); //remove type flags - TODO: use these instead of removing them)
			}
		}

		$tiles = [];
		if(($tileData = $this->db->get($index . self::TAG_BLOCK_ENTITY)) !== false and $tileData !== ""){
			$tiles = $nbt->read($tileData, true);
			if(!is_array($tiles)){
				$tiles = [$tiles];
			}
		}

		//TODO: extra data should be converted into blockstorage layers (first they need to be implemented!)
		/*
		$extraData = [];
		if(($extraRawData = $this->db->get($index . self::TAG_BLOCK_EXTRA_DATA)) !== false and $extraRawData !== ""){
			$binaryStream->setBuffer($extraRawData, 0);
			$count = $binaryStream->getLInt();
			for($i = 0; $i < $count; ++$i){
				$key = $binaryStream->getLInt();
				$value = $binaryStream->getLShort();
				$extraData[$key] = $value;
			}
		}*/

		$chunk = new Chunk(
			$chunkX,
			$chunkZ,
			$subChunks,
			$entities,
			$tiles,
			$biomeIds,
			$heightMap
		);

		//TODO: tile ticks, biome states (?)

		$chunk->setGenerated(true);
		$chunk->setPopulated(true);
		$chunk->setLightPopulated($lightPopulated);
		$chunk->setChanged($hasBeenUpgraded); //trigger rewriting chunk to disk if it was converted from an older format

		return $chunk;
	}

	protected function writeChunk(Chunk $chunk) : void{
        if(self::$cacheBlockIdMapFlip === null){
            self::$cacheBlockIdMapFlip = array_flip(json_decode(file_get_contents(RESOURCE_PATH . "/vanilla/palette/block_id_map407.json"), true));
        }

		$index = LevelDB::chunkIndex($chunk->getX(), $chunk->getZ());
		$this->db->put($index . self::TAG_VERSION, chr(self::CURRENT_LEVEL_CHUNK_VERSION));

		$subChunks = $chunk->getSubChunks();
		foreach($subChunks as $y => $subChunk){
			$key = $index . self::TAG_SUBCHUNK_PREFIX . chr($y);
			if($subChunk->isEmpty(false)){ //MCPE doesn't save light anymore as of 1.1
				$this->db->delete($key);
			}else{
				$subStream = new BinaryStream();
				$subStream->putByte(self::CURRENT_LEVEL_SUBCHUNK_VERSION);

				$layers = $subChunk->getBlockLayers();
				$subStream->putByte(count($layers));
				foreach($layers as $blocks){
					if($blocks->getBitsPerBlock() !== 0){
						$subStream->putByte($blocks->getBitsPerBlock() << 1);
						$subStream->put($blocks->getWordArray());
					}else{
						//TODO: we use these in-memory, but they aren't supported on disk by the game yet
						//polyfill them with a zero'd 1-bpb instead
						$subStream->putByte(1 << 1);
						$subStream->put(str_repeat("\x00", PalettedBlockArray::getExpectedWordArraySize(1)));
					}

					$palette = $blocks->getPalette();
					$subStream->putLInt(count($palette));
					$tags = [];
					foreach($palette as $p){
						$tags[] = new CompoundTag("", [
							new StringTag("name", self::$cacheBlockIdMapFlip[$p >> Block::INTERNAL_METADATA_BITS] ?? "minecraft:info_update"),
							new IntTag("oldid", $p >> Block::INTERNAL_METADATA_BITS), //PM only (debugging), vanilla doesn't have this
							new ShortTag("val", $p & Block::INTERNAL_METADATA_MASK),
						]);
					}

					$subStream->put((new LittleEndianNBTStream())->write($tags));
				}

				$this->db->put($key, $subStream->getBuffer());
			}
		}

		$this->db->put($index . self::TAG_DATA_2D, pack("v*", ...$chunk->getHeightMapArray()) . $chunk->getBiomeIdArray());

		//TODO: use this properly
		$this->db->put($index . self::TAG_STATE_FINALISATION, chr(self::FINALISATION_DONE));

		/** @var CompoundTag[] $tiles */
		$tiles = [];
		foreach($chunk->getTiles() as $tile){
			$tiles[] = $tile->saveNBT();
		}
		$this->writeTags($tiles, $index . self::TAG_BLOCK_ENTITY);

		/** @var CompoundTag[] $entities */
		$entities = [];
		foreach($chunk->getSavableEntities() as $entity){
			$entity->saveNBT();
			$entities[] = $entity->namedtag;
		}
		$this->writeTags($entities, $index . self::TAG_ENTITY);

		$this->db->delete($index . self::TAG_DATA_2D_LEGACY);
		$this->db->delete($index . self::TAG_LEGACY_TERRAIN);
	}

	/**
	 * @param CompoundTag[] $targets
	 * @param string        $index
	 */
	private function writeTags(array $targets, string $index){
		if(!empty($targets)){
			$nbt = new LittleEndianNBTStream();
			$this->db->put($index, $nbt->write($targets));
		}else{
			$this->db->delete($index);
		}
	}

	/**
	 * @return \LevelDB
	 */
	public function getDatabase() : \LevelDB{
		return $this->db;
	}

	public static function chunkIndex(int $chunkX, int $chunkZ) : string{
		return (pack("V", $chunkX)) . (pack("V", $chunkZ));
	}

	private function chunkExists(int $chunkX, int $chunkZ) : bool{
		return $this->db->get(LevelDB::chunkIndex($chunkX, $chunkZ) . self::TAG_VERSION) !== false;
	}

	public function close(){
		unset($this->db);
	}
}
