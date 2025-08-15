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

namespace pocketmine;

use BadMethodCallException;
use InvalidArgumentException;
use InvalidStateException;
use pocketmine\block\Bed;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\UnknownBlock;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\passive\AbstractHorse;
use pocketmine\entity\passive\Cow;
use pocketmine\entity\passive\Sheep;
use pocketmine\entity\hostile\Creeper;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\FishingHook;
use pocketmine\entity\Skin;
use pocketmine\entity\vehicle\Boat;
use pocketmine\event\block\ItemFrameDropItemEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\player\cheat\PlayerIllegalMoveEvent;
use pocketmine\event\player\PlayerAchievementAwardedEvent;
use pocketmine\event\player\PlayerAnimationEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerBedLeaveEvent;
use pocketmine\event\player\PlayerBlockPickEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerEditBookEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerInteractEntityEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerMissSwingEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleFlightEvent;
use pocketmine\event\player\PlayerToggleGlideEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerToggleSprintEvent;
use pocketmine\event\player\PlayerToggleSwimEvent;
use pocketmine\event\player\PlayerTransferEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\form\Form;
use pocketmine\form\FormValidationException;
use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\CraftingGrid;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\PETransaction\DropItemTransaction;
use pocketmine\inventory\PETransaction\Transaction;
use pocketmine\inventory\PETransaction\TransactionQueue;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\AnvilInventory;
use pocketmine\inventory\EnchantInventory;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\CraftingTransaction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\item\Consumable;
use pocketmine\item\Durable;
use pocketmine\item\Elytra;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\MeleeWeaponEnchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\WritableBook;
use pocketmine\item\WrittenBook;
use pocketmine\lang\TextContainer;
use pocketmine\lang\TranslationContainer;
use pocketmine\level\ChunkLoader;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\metadata\MetadataValue;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\LittleEndianNBTStream;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\encryption\EncryptionContext;
use pocketmine\network\mcpe\encryption\PrepareEncryptionTask;
use pocketmine\network\mcpe\multiversion\biomes\BiomeDefinitionPalette;
use pocketmine\network\mcpe\multiversion\block\BlockPalette;
use pocketmine\network\mcpe\multiversion\inventory\ItemPalette;
use pocketmine\network\mcpe\multiversion\ProtocolConvertor;
use pocketmine\network\mcpe\PacketRateLimiter;
use pocketmine\network\mcpe\PlayerNetworkSessionAdapter;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\BlockPickRequestPacket;
use pocketmine\network\mcpe\protocol\BookEditPacket;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\ChunkRadiusUpdatedPacket;
use pocketmine\network\mcpe\protocol\CommandStepPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\network\mcpe\protocol\CraftingEventPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\DropItemPacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemFrameDropItemPacket;
use pocketmine\network\mcpe\protocol\ItemRegistryPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\OpenSignPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RequestAbilityPacket;
use pocketmine\network\mcpe\protocol\RequestNetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkDataPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\ResourcePackDataInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\ServerToClientHandshakePacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer;
use pocketmine\network\mcpe\protocol\types\CommandData;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandOverload;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use pocketmine\network\mcpe\protocol\types\CompressionAlgorithm;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\NetworkPermissions;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\PlayerMovementSettings;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\types\ServerAuthMovementMode;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionStopBreak;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionWithBlockInfo;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\types\SerializedSkin;
use pocketmine\network\mcpe\protocol\types\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\SkinImage;
use pocketmine\network\mcpe\protocol\types\SpawnSettings;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\UpdateAdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\protocol\UseItemPacket;
use pocketmine\network\mcpe\VerifyLoginTask;
use pocketmine\network\SourceInterface;
use pocketmine\permission\PermissibleBase;
use pocketmine\permission\PermissionAttachment;
use pocketmine\permission\PermissionAttachmentInfo;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\Plugin;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\tile\ItemFrame;
use pocketmine\tile\Sign as TileSign;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;
use pocketmine\timings\Timings;
use pocketmine\utils\Color;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;
use SplFixedArray;
use UnexpectedValueException;
use function abs;
use function array_merge;
use function assert;
use function base64_decode;
use function ceil;
use function count;
use function explode;
use function floor;
use function fmod;
use function get_class;
use function gettype;
use function implode;
use function in_array;
use function is_bool;
use function is_countable;
use function is_int;
use function is_object;
use function is_string;
use function json_encode;
use function json_last_error_msg;
use function lcg_value;
use function max;
use function mb_strlen;
use function microtime;
use function min;
use function preg_match;
use function round;
use function spl_object_hash;
use function sprintf;
use function sqrt;
use function strlen;
use function strpos;
use function strtolower;
use function str_replace;
use function substr;
use function trim;
use function ucfirst;
use const M_PI;
use const M_SQRT3;
use const PHP_INT_MAX;


/**
 * Main class that handles networking, recovery, and packet sending to the server part
 */
class Player extends Human implements CommandSender, ChunkLoader, IPlayer{

	public const SURVIVAL = 0;
	public const CREATIVE = 1;
	public const ADVENTURE = 2;
	public const SPECTATOR = 3;
	public const VIEW = Player::SPECTATOR;

	private const MOVES_PER_TICK = 2;
	private const MOVE_BACKLOG_SIZE = 100 * self::MOVES_PER_TICK; //100 ticks backlog (5 seconds)

	//TODO: HACK!
	//these IDs are used for 1.16 to restore 1.14ish crafting & inventory behaviour; since they don't seem to have any
	//effect on the behaviour of inventory transactions I don't currently plan to integrate these into the main system.
	private const RESERVED_WINDOW_ID_RANGE_START = ContainerIds::LAST - 10;
	private const RESERVED_WINDOW_ID_RANGE_END = ContainerIds::LAST;
	public const HARDCODED_CRAFTING_GRID_WINDOW_ID = self::RESERVED_WINDOW_ID_RANGE_START + 1;
	public const HARDCODED_INVENTORY_WINDOW_ID = self::RESERVED_WINDOW_ID_RANGE_START + 2;

	public const BEACON_WINDOW_ID = 4;

	/** Max length of a chat message (UTF-8 codepoints, not bytes) */
	private const MAX_CHAT_CHAR_LENGTH = 512;
	/**
	 * Max length of a chat message in bytes. This is a theoretical maximum (if every character was 4 bytes).
	 * Since mb_strlen() is O(n), it gets very slow with large messages. Checking byte length with strlen() is O(1) and
	 * is a useful heuristic to filter out oversized messages.
	 */
	private const MAX_CHAT_BYTE_LENGTH = self::MAX_CHAT_CHAR_LENGTH * 4;

	private const INCOMING_PACKET_BATCH_PER_TICK = 20; //usually max 1 per tick, but transactions arrive separately
	private const INCOMING_PACKET_BATCH_BUFFER_TICKS = 100; //enough to account for a 5-second lag spike

	private const INCOMING_GAME_PACKETS_PER_TICK = 40;
	private const INCOMING_GAME_PACKETS_BUFFER_TICKS = 100;

    private const MAX_INPUT_JSON = 15;

	private const DEFAULT_FX_INTERVAL_TICKS = 5;

	/**
	 * Validates the given username.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public static function isValidUserName(?string $name) : bool{
		if($name === null){
			return false;
		}

		$lname = strtolower($name);
		$len = strlen($name);
		return $lname !== "rcon" and $lname !== "console" and $len >= 1 and $len <= 16 and preg_match("/[^A-Za-z0-9_ ]/", $name) === 0 and trim($lname) !== "" and trim($lname) === $lname;
	}


	/** @var SourceInterface */
	protected $interface;

	/**
	 * @var PlayerNetworkSessionAdapter
	 * TODO: remove this once player and network are divorced properly
	 */
	protected $sessionAdapter;

    /** @var bool */
    protected $enableCompression = true;

    /** @var ?EncryptionContext */
	protected $cipher = null;

	/** @var string */
	protected $ip;
	/** @var int */
	protected $port;
    /** @var int */
    protected $raknetProtocol;

	/** @var bool[] */
	private $needACK = [];

	/** @var DataPacket[] */
	private $batchedPackets = [];

	/**
	 * @var int
	 * Last measurement of player's latency in milliseconds.
	 */
	protected $lastPingMeasure = 1;


	/** @var float */
	public $creationTime = 0;

	/** @var bool */
	public $loggedIn = false;

	/** @var bool */
	private $resourcePacksDone = false;

	/** @var bool */
	private $haveAllPacks = false;

	/** @var bool */
	public $spawned = false;

    /** @var bool */
	protected $xboxAuthenticated = false;

	/** @var string */
	protected $username = "";
	/** @var string */
	protected $iusername = "";
	/** @var string */
	protected $displayName = "";
	/** @var int */
	protected $originalProtocol = ProtocolInfo::CURRENT_PROTOCOL;
	/** @var int */
	protected $protocol = ProtocolInfo::CURRENT_PROTOCOL;
	/** @var int */
	protected $chunkProtocol = ProtocolInfo::CURRENT_PROTOCOL;
	/** @var int */
	protected $craftingProtocol = ProtocolInfo::CURRENT_PROTOCOL;
	/** @var string */
	protected $vanillaVersion = "";
	/** @var int */
	protected $randomClientId;
	/** @var string */
	protected $xuid = "";

	protected $windowCnt = 2;
	/** @var int[] */
	protected $windows = [];
	/** @var Inventory[] */
	protected $windowIndex = [];
	/** @var bool[] */
	protected $permanentWindows = [];
	/** @var PlayerCursorInventory */
	protected $cursorInventory;
	/** @var CraftingGrid */
	protected $craftingGrid = null;
	/** @var CraftingTransaction|null */
	protected $craftingTransaction = null;

	/**
	 * TODO: HACK! This tracks GUIs for inventories that the server considers "always open" so that the client can't
	 * open them twice. (1.16 hack)
	 * @var true[]
	 * @phpstan-var array<int, true>
	 * @internal
	 */
	public $openHardcodedWindows = [];

	/** @var int */
	protected $messageCounter = 2;
	/** @var bool */
	protected $removeFormat = true;

	/** @var bool[] name of achievement => bool */
	protected $achievements = [];
	/** @var bool */
	protected $playedBefore;
	/** @var int */
	protected $gamemode;

	/** @var int */
	private $loaderId = 0;
	/** @var bool[] chunkHash => bool (true = sent, false = needs sending) */
	public $usedChunks = [];
	/** @var bool[] chunkHash => dummy */
	protected $loadQueue = [];
	/** @var int */
	protected $nextChunkOrderRun = 5;

	/** @var int */
	protected $viewDistance = -1;
	/** @var int */
	protected $spawnThreshold;
	/** @var int */
	protected $spawnChunkLoadCount = 0;
	/** @var int */
	protected $chunksPerTick;

	/** @var bool[] map: raw UUID (string) => bool */
	protected $hiddenPlayers = [];

    /** @var float */
    protected $moveRateLimit = 10 * self::MOVES_PER_TICK;
	/** @var float|null */
	protected $lastMovementProcess = null;
	/** @var Vector3|null */
	protected $forceMoveSync = null;

	/** @var int */
	protected $inAirTicks = 0;
	/** @var float */
	protected $stepHeight = 0.6;
	/** @var bool */
	protected $allowMovementCheats = false;

	/** @var Vector3|null */
	protected $sleeping = null;
	/** @var Position|null */
	private $spawnPosition = null;

	//TODO: Abilities
	/** @var bool */
	protected $autoJump = true;
	/** @var bool */
	protected $allowFlight = false;
	/** @var bool */
	protected $flying = false;

	/** @var PermissibleBase */
	private $perm = null;

	
	/** @var Vector3 */
	public $fromPos = null;
	/** @var int */
	private $portalTime = 0;
	/** @var  Position */
	private $shouldResPos;

	/** @var int|null */
	protected $lineHeight = null;
	/** @var string */
	protected $locale = "en_US";
    /** @var int|null */
    protected $deviceOS = null;
    /** @var string|null */
    protected $deviceModel = null;

	/** @var int */
	protected $startAction = -1;
	/** @var int[] ID => ticks map */
	protected $usedItemsCooldown = [];

	/** @var int */
	protected $formIdCounter = 0;
	/** @var Form[] */
	protected $forms = [];

	/** @var float */
	protected $lastRightClickTime = 0.0;
	/** @var UseItemTransactionData|null */
	protected $lastRightClickData = null;

    /** @var ?int */
    private $lastPlayerAuthInputFlags = null;
    /** @var ?float */
    private $lastPlayerAuthInputPitch = null;
    /** @var ?float */
    private $lastPlayerAuthInputYaw = null;
    /** @var ?Position */
    private $lastPlayerAuthInputPosition = null;

    /** @var string */
    protected $lastRequestedFullSkinId = null;

	/** @var ?int */
	private $currentItemStackRequestId = null;

	/** @var FishingHook|null */
	protected $fishingHook = null;

	/** @var bool */
	protected $awaitingEncryptionHandshake = false;

	/** @var PacketRateLimiter */
	protected $packetBatchLimiter;
	/** @var PacketRateLimiter */
	protected $gamePacketLimiter;

	/** @var int */
	private $fxTicker = 0;
	/** @var int */
	private $fxTickInterval = self::DEFAULT_FX_INTERVAL_TICKS;

	/** @var int */
	protected $chunkHack = 0;

	/**
	 * @return TranslationContainer|string
	 */
	public function getLeaveMessage(){
		if($this->spawned){
			return new TranslationContainer(TextFormat::YELLOW . "%multiplayer.player.left", [
				$this->getDisplayName()
			]);
		}

		return "";
	}

	/**
	 * This might disappear in the future. Please use getUniqueId() instead.
	 * @deprecated
	 *
	 * @return int
	 */
	public function getClientId(){
		return $this->randomClientId;
	}

	public function isBanned() : bool{
		return $this->server->getNameBans()->isBanned($this->username);
	}

	public function setBanned(bool $value){
		if($value){
			$this->server->getNameBans()->addBan($this->getName(), null, null, null);
			$this->kick("You have been banned");
		}else{
			$this->server->getNameBans()->remove($this->getName());
		}
	}

	public function isWhitelisted() : bool{
		return $this->server->isWhitelisted($this->username);
	}

	public function setWhitelisted(bool $value){
		if($value){
			$this->server->addWhitelist($this->username);
		}else{
			$this->server->removeWhitelist($this->username);
		}
	}

	public function isAuthenticated() : bool{
		return $this->xboxAuthenticated;
	}

	/**
	 * If the player is logged into Xbox Live, returns their Xbox user ID (XUID) as a string. Returns an empty string if
	 * the player is not logged into Xbox Live.
	 *
	 * @return string
	 */
	public function getXuid() : string{
		return $this->xuid;
	}

	/**
	 * Returns the player's UUID. This should be preferred over their Xbox user ID (XUID) because UUID is a standard
	 * format which will never change, and all players will have one regardless of whether they are logged into Xbox
	 * Live.
	 *
	 * The UUID is comprised of:
	 * - when logged into XBL: a hash of their XUID (and as such will not change for the lifetime of the XBL account)
	 * - when NOT logged into XBL: a hash of their name + clientID + secret device ID.
	 *
	 * WARNING: UUIDs of players **not logged into Xbox Live** CAN BE FAKED and SHOULD NOT be trusted!
	 *
	 * (In the olden days this method used to return a fake UUID computed by the server, which was used by plugins such
	 * as SimpleAuth for authentication. This is NOT SAFE anymore as this UUID is now what was given by the client, NOT
	 * a server-computed UUID.)
	 *
	 * @return UUID|null
	 */
	public function getUniqueId() : ?UUID{
		return parent::getUniqueId();
	}

	public function getPlayer(){
		return $this;
	}

	public function getFirstPlayed(){
		return $this->namedtag instanceof CompoundTag ? $this->namedtag->getLong("firstPlayed", 0, true) : null;
	}

	public function getLastPlayed(){
		return $this->namedtag instanceof CompoundTag ? $this->namedtag->getLong("lastPlayed", 0, true) : null;
	}

	public function hasPlayedBefore() : bool{
		return $this->playedBefore;
	}

	public function setAllowFlight(bool $value){
		$this->allowFlight = $value;
		$this->syncAbilities();
	}

	public function getAllowFlight() : bool{
		return $this->allowFlight;
	}

	public function setFlying(bool $value){
		if($this->flying !== $value){
			$this->flying = $value;
			$this->resetFallDistance();
			$this->syncAbilities();
		}
	}

	public function isFlying() : bool{
		return $this->flying;
	}

	public function setAutoJump(bool $value){
		$this->autoJump = $value;
		$this->syncAdventureSettings();
	}

	public function hasAutoJump() : bool{
		return $this->autoJump;
	}

	/**
	 * @return null|FishingHook
	 */
	public function getFishingHook() : ?FishingHook{
		return $this->fishingHook;
	}

	/**
	 * @param null|FishingHook $fishingHook
	 */
	public function setFishingHook(?FishingHook $fishingHook) : void{
		$this->fishingHook = $fishingHook;
	}

	public function allowMovementCheats() : bool{
		return $this->allowMovementCheats;
	}

	public function setAllowMovementCheats(bool $value = true){
		$this->allowMovementCheats = $value;
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player) : void{
		if($this->spawned and $player->spawned and $this->isAlive() and $player->isAlive() and $player->getLevel() === $this->level and $player->canSee($this) and !$this->isSpectator()){
			parent::spawnTo($player);
		}
	}

	/**
	 * @return Server
	 */
	public function getServer(){
		return $this->server;
	}

	/**
	 * @return bool
	 */
	public function getRemoveFormat() : bool{
		return $this->removeFormat;
	}

	/**
	 * @param bool $remove
	 */
	public function setRemoveFormat(bool $remove = true){
		$this->removeFormat = $remove;
	}

	public function getScreenLineHeight() : int{
		return $this->lineHeight ?? 7;
	}

	public function setScreenLineHeight(int $height = null){
		if($height !== null and $height < 1){
			throw new InvalidArgumentException("Line height must be at least 1");
		}
		$this->lineHeight = $height;
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function canSee(Player $player) : bool{
		return !isset($this->hiddenPlayers[$player->getRawUniqueId()]);
	}

	/**
	 * @param Player $player
	 */
	public function hidePlayer(Player $player){
		if($player === $this){
			return;
		}
		$this->hiddenPlayers[$player->getRawUniqueId()] = true;
		$player->despawnFrom($this);
	}

	/**
	 * @param Player $player
	 */
	public function showPlayer(Player $player){
		if($player === $this){
			return;
		}
		unset($this->hiddenPlayers[$player->getRawUniqueId()]);
		if($player->isOnline()){
			$player->spawnTo($this);
		}
	}

	public function canCollideWith(Entity $entity) : bool{
		return false;
	}

	public function canBeCollidedWith() : bool{
		return !$this->isSpectator() and parent::canBeCollidedWith();
	}

	public function resetFallDistance() : void{
		parent::resetFallDistance();
		$this->inAirTicks = 0;
	}

	public function getViewDistance() : int{
		return $this->viewDistance;
	}

	public function setViewDistance(int $distance){
		if(!$this->constructed){
			return;
		}

		$this->viewDistance = $this->server->getAllowedViewDistance($distance);

		$this->spawnThreshold = (int) (min($this->viewDistance, $this->server->getProperty("chunk-sending.spawn-radius", 4)) ** 2 * M_PI);

		$this->nextChunkOrderRun = 0;

		$pk = new ChunkRadiusUpdatedPacket();
		$pk->radius = $this->viewDistance;
		$this->dataPacket($pk);

		$this->server->getLogger()->debug("Setting view distance for " . $this->getName() . " to " . $this->viewDistance . " (requested " . $distance . ")");
	}

	/**
	 * @return bool
	 */
	public function isOnline() : bool{
		return $this->isConnected() and $this->loggedIn;
	}

	/**
	 * @return bool
	 */
	public function isOp() : bool{
		return $this->server->isOp($this->getName());
	}

	/**
	 * @param bool $value
	 */
	public function setOp(bool $value){
		if($value === $this->isOp()){
			return;
		}

		if($value){
			$this->server->addOp($this->getName());
		}else{
			$this->server->removeOp($this->getName());
		}

        $this->syncAbilities();
	}

	/**
	 * @param permission\Permission|string $name
	 *
	 * @return bool
	 */
	public function isPermissionSet($name) : bool{
		return $this->perm->isPermissionSet($name);
	}

	/**
	 * @param permission\Permission|string $name
	 *
	 * @return bool
	 *
	 * @throws InvalidStateException if the player is closed
	 */
	public function hasPermission($name) : bool{
		if($this->closed){
			throw new InvalidStateException("Trying to get permissions of closed player");
		}
		return $this->perm->hasPermission($name);
	}

	/**
	 * @param Plugin $plugin
	 * @param string $name
	 * @param bool   $value
	 *
	 * @return PermissionAttachment
	 */
	public function addAttachment(Plugin $plugin, string $name = null, bool $value = null) : PermissionAttachment{
		return $this->perm->addAttachment($plugin, $name, $value);
	}

	/**
	 * @param PermissionAttachment $attachment
	 */
	public function removeAttachment(PermissionAttachment $attachment){
		$this->perm->removeAttachment($attachment);
	}

	public function recalculatePermissions(){
		$permManager = PermissionManager::getInstance();
		$permManager->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		$permManager->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);

		if($this->perm === null){
			return;
		}

		$this->perm->recalculatePermissions();

		if($this->spawned){
			if($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)){
				$permManager->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
			}
			if($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)){
				$permManager->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
			}

			$this->sendCommandData();
		}
	}

	/**
	 * @return PermissionAttachmentInfo[]
	 */
	public function getEffectivePermissions() : array{
		return $this->perm->getEffectivePermissions();
	}

	public function sendCommandData(){
		$pk = new AvailableCommandsPacket();
		foreach($this->server->getCommandMap()->getCommands() as $name => $command){
			if(isset($pk->commandData[$command->getName()]) or $command->getName() === "help" or !$command->testPermissionSilent($this)){
				continue;
			}

			$pk->commandData[$command->getName()] = $command->getCommandData();
		}

		$this->dataPacket($pk);
	}

	/**
	 * @param SourceInterface $interface
	 * @param string          $ip
	 * @param int             $port
	 * @param int             $raknetProtocol
	 */
	public function __construct(SourceInterface $interface, string $ip, int $port, int $raknetProtocol){
		$this->interface = $interface;
		$this->perm = new PermissibleBase($this);
		$this->namedtag = new CompoundTag();
		$this->server = Server::getInstance();
		$this->ip = $ip;
		$this->port = $port;
		$this->raknetProtocol = $raknetProtocol;
		if($this->raknetProtocol >= 11){ // MCBE_RAKNET_PROTOCOL_VERSION_WITHOUT_ZLIB_COMPRESSION
		    $this->enableCompression = false;
		    $this->protocol = ProtocolInfo::PROTOCOL_567;
		}elseif($this->raknetProtocol >= 8){ // MCPE_RAKNET_PROTOCOL_VERSION
		    $this->protocol = ProtocolInfo::PROTOCOL_90;
		}
		$this->loaderId = Level::generateChunkLoaderId($this);
		$this->chunksPerTick = (int) $this->server->getProperty("chunk-sending.per-tick", 4);
		$this->spawnThreshold = (int) (($this->server->getProperty("chunk-sending.spawn-radius", 4) ** 2) * M_PI);
		$this->gamemode = $this->server->getGamemode();
		$this->setLevel($this->server->getDefaultLevel());
		$this->boundingBox = new AxisAlignedBB(0, 0, 0, 0, 0, 0);

		$this->creationTime = microtime(true);

		$this->allowMovementCheats = (bool) $this->server->getProperty("player.anti-cheat.allow-movement-cheats", false);

		$this->sessionAdapter = new PlayerNetworkSessionAdapter($this->server, $this);

		$this->packetBatchLimiter = new PacketRateLimiter("Packet Batches", self::INCOMING_PACKET_BATCH_PER_TICK, self::INCOMING_PACKET_BATCH_BUFFER_TICKS);
		$this->gamePacketLimiter = new PacketRateLimiter("Game Packets", self::INCOMING_GAME_PACKETS_PER_TICK, self::INCOMING_GAME_PACKETS_BUFFER_TICKS);
	}

    /**
     * @return PacketRateLimiter
     */
    public function getPacketBatchLimiter() : PacketRateLimiter{
		return $this->packetBatchLimiter;
	}

    /**
     * @return PacketRateLimiter
     */
    public function getGamePacketLimiter() : PacketRateLimiter{
		return $this->gamePacketLimiter;
	}

    /**
     * @return bool
     */
    public function isCompressionEnabled() : bool{
        return $this->enableCompression;
    }

	/**
	 * @return bool
	 */
	public function isConnected() : bool{
		return $this->sessionAdapter !== null;
	}

	/**
	 * Gets the username
	 * @return string
	 */
	public function getName() : string{
		return $this->username;
	}

	/**
	 * @return string
	 */
	public function getLowerCaseName() : string{
		return $this->iusername;
	}

	/**
	 * Returns the "friendly" display name of this player to use in the chat.
	 *
	 * @return string
	 */
	public function getDisplayName() : string{
		return $this->displayName;
	}

	/**
	 * @param string $name
	 */
	public function setDisplayName(string $name){
		$this->displayName = $name;
		if($this->constructed){
			$this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getDisplayName(), $this->getSkin(), $this->getXuid());
		}
	}

	/**
	 * Returns the player's locale, e.g. en_US.
	 * @return string
	 */
	public function getLocale() : string{
		return $this->locale;
	}

	/**
	 * Returns the player's device OS.
	 * @return ?int
	 */
    public function getDeviceOS() : ?int{
        return $this->deviceOS;
    }

	/**
	 * Returns the player's device model name.
	 * @return ?string
	 */
    public function getDeviceModel() : ?string{
        return $this->deviceModel;
    }

	/**
	 * Called when a player changes their skin.
	 * Plugin developers should not use this, use setSkin() and sendSkin() instead.
	 *
	 * @param Skin   $skin
	 * @param string $newSkinName
	 * @param string $oldSkinName
	 *
	 * @return bool
	 */
	public function changeSkin(Skin $skin, string $newSkinName, string $oldSkinName) : bool{
		if($skin->getSerializedSkin()->getFullSkinId() === $this->lastRequestedFullSkinId){
			//TODO: HACK! In 1.19.60, the client sends its skin back to us if we sent it a skin different from the one
			//it's using. We need to prevent this from causing a feedback loop.
			$this->server->getLogger()->debug("Refused duplicate skin change request");
			return true;
		}
		$this->lastRequestedFullSkinId = $skin->getSerializedSkin()->getFullSkinId();

		if(!$skin->isValid()){
			return false;
		}

		$ev = new PlayerChangeSkinEvent($this, $this->getSkin(), $skin);
		$ev->call();

		if($ev->isCancelled()){
			$this->sendSkin([$this]);
			return true;
		}

		$this->setSkin($ev->getNewSkin());
		$this->sendSkin($this->server->getOnlinePlayers());
		return true;
	}

	/**
	 * {@inheritdoc}
	 *
	 * If null is given, will additionally send the skin to the player itself as well as its viewers.
	 */
	public function sendSkin(?array $targets = null) : void{
		parent::sendSkin($targets ?? $this->server->getOnlinePlayers());
	}

	/**
	 * Gets the player IP address
	 *
	 * @return string
	 */
	public function getAddress() : string{
		return $this->ip;
	}

	/**
	 * @return int
	 */
	public function getPort() : int{
		return $this->port;
	}

    /**
     * @return int
     */
    public function getRaknetProtocol() : int{
        return $this->raknetProtocol;
    }

	/**
	 * Returns the last measured latency for this player, in milliseconds. This is measured automatically and reported
	 * back by the network interface.
	 *
	 * @return int
	 */
	public function getPing() : int{
		return $this->lastPingMeasure;
	}

	/**
	 * Updates the player's last ping measurement.
	 *
	 * @internal Plugins should not use this method.
	 *
	 * @param int $pingMS
	 */
	public function updatePing(int $pingMS){
		$this->lastPingMeasure = $pingMS;
	}

    /**
	 * @deprecated
	 */
	public function getNextPosition() : Position{
		return $this->getPosition();
	}

	public function getInAirTicks() : int{
		return $this->inAirTicks;
	}

	/**
	 * Returns whether the player is currently using an item (right-click and hold).
	 * @return bool
	 */
	public function isUsingItem() : bool{
		return $this->getGenericFlag(self::DATA_FLAG_ACTION) and $this->startAction > -1;
	}

	public function setUsingItem(bool $value){
		$this->startAction = $value ? $this->server->getTick() : -1;
		$this->setGenericFlag(self::DATA_FLAG_ACTION, $value);
	}

	/**
	 * Returns how long the player has been using their currently-held item for. Used for determining arrow shoot force
	 * for bows.
	 *
	 * @return int
	 */
	public function getItemUseDuration() : int{
		return $this->startAction === -1 ? -1 : ($this->server->getTick() - $this->startAction);
	}

	/**
	 * Returns whether the player has a cooldown period left before it can use the given item again.
	 *
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function hasItemCooldown(Item $item) : bool{
		$this->checkItemCooldowns();
		return isset($this->usedItemsCooldown[$item->getId()]);
	}

	/**
	 * Resets the player's cooldown time for the given item back to the maximum.
	 *
	 * @param Item $item
	 */
	public function resetItemCooldown(Item $item) : void{
		$ticks = $item->getCooldownTicks();
		if($ticks > 0){
			$this->usedItemsCooldown[$item->getId()] = $this->server->getTick() + $ticks;
		}
	}

	protected function checkItemCooldowns() : void{
		$serverTick = $this->server->getTick();
		foreach($this->usedItemsCooldown as $itemId => $cooldownUntil){
			if($cooldownUntil <= $serverTick){
				unset($this->usedItemsCooldown[$itemId]);
			}
		}
	}

	protected function switchLevel(Level $targetLevel) : bool{
		$oldLevel = $this->level;
		if(parent::switchLevel($targetLevel)){
			if($oldLevel !== null) {
                foreach ($this->usedChunks as $index => $d) {
                    Level::getXZ($index, $X, $Z);
                    $this->unloadChunk($X, $Z, $oldLevel);
                }
            }

			$this->usedChunks = [];
			$this->loadQueue = [];
			$this->level->sendTime($this);
			$this->level->sendDifficulty($this);

			if($targetLevel->getDimensionId() !== $oldLevel->getDimensionId()){
				$pk = new ChangeDimensionPacket();
				$pk->dimension = $this->getClientFriendlyDimension($targetLevel->getDimensionId());
				$pk->position = $this->asVector3();
				$this->dataPacket($pk);

				// fast world loading
				$pk1 = new PlayerActionPacket();
				$pk1->entityRuntimeId = $this->getId();
				$pk1->action = PlayerActionPacket::ACTION_DIMENSION_CHANGE_ACK;
				[$pk1->x, $pk1->y, $pk1->z] = [(int) $this->x, (int) $this->y, (int) $this->z];
				[$pk1->rx, $pk1->ry, $pk1->rz] = [(int) $this->x, (int) $this->y, (int) $this->z];
				$pk1->face = 0;
				$this->dataPacket($pk1);
			}

			$targetLevel->getWeather()->sendWeather($this);

			return true;
		}

		return false;
	}

	protected function unloadChunk(int $x, int $z, Level $level = null){
		$level = $level ?? $this->level;
        $index = Level::chunkHash($x, $z);
		if(isset($this->usedChunks[$index])){
			foreach($level->getChunkEntities($x, $z) as $entity){
				if($entity !== $this){
					$entity->despawnFrom($this);
				}
			}

			unset($this->usedChunks[$index]);
		}
		$level->unregisterChunkLoader($this, $x, $z);
		unset($this->loadQueue[$index]);
	}

	public function sendChunk(int $x, int $z, BatchPacket $payload){
		if(!$this->isConnected()){
			return;
		}

		if($this->getProtocol() < ProtocolInfo::PROTOCOL_130){
			$this->chunkHack = 2;
		}

		$this->usedChunks[Level::chunkHash($x, $z)] = true;
		$this->dataPacket($payload);

		if($this->spawned){
			foreach($this->level->getChunkEntities($x, $z) as $entity){
				if($entity !== $this and !$entity->isClosed() and $entity->isAlive()){
					$entity->spawnTo($this);
				}
			}
		}

		if($this->spawnChunkLoadCount !== -1 and ++$this->spawnChunkLoadCount >= $this->spawnThreshold){
			$this->sendPlayStatus(PlayStatusPacket::PLAYER_SPAWN);
			if($this->getProtocol() < ProtocolInfo::PROTOCOL_274){
			    $this->doFirstSpawn();
			}

			$this->spawnChunkLoadCount = -1;
		}
	}

	protected function sendNextChunk(){
        if (!$this->isConnected()) {
            return;
        }

        Timings::$playerChunkSendTimer->startTiming();

        $count = 0;
        foreach ($this->loadQueue as $index => $distance) {
            if ($count >= $this->chunksPerTick) {
                break;
            }

            Level::getXZ($index, $X, $Z);
            assert(is_int($X) and is_int($Z));

            ++$count;

            $this->usedChunks[$index] = false;
            $this->level->registerChunkLoader($this, $X, $Z, false);

            if (!$this->level->populateChunk($X, $Z)) {
                continue;
            }

            unset($this->loadQueue[$index]);
            $this->level->requestChunk($X, $Z, $this);
        }

        Timings::$playerChunkSendTimer->stopTiming();
    }

	public function doFirstSpawn(){
		if($this->spawned || !$this->constructed){
			return; //avoid player spawning twice (this can only happen on 3.x with a custom malicious client)
		}
		$this->spawned = true;
		$this->setImmobile(false);

		$this->inventory->sendContents($this);
		$this->armorInventory->sendContents($this);
		$this->inventory->sendHeldItem($this);

		// for 0.15
		$this->sendAttributes(true);
		$this->level->sendTime($this);
		$this->level->sendDifficulty($this);

		if($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)){
			PermissionManager::getInstance()->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		}
		if($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)){
			PermissionManager::getInstance()->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
		}

		$ev = new PlayerJoinEvent($this,
			new TranslationContainer(TextFormat::YELLOW . "%multiplayer.player.joined", [
				$this->getDisplayName()
			])
		);
		$ev->call();
		if(strlen(trim((string) $ev->getJoinMessage())) > 0){
			$this->server->broadcastMessage($ev->getJoinMessage());
		}

		$this->noDamageTicks = 60;

		foreach($this->usedChunks as $index => $hasSent) {
            if (!$hasSent) {
                continue; //this will happen when the chunk is ready to send
            }

            Level::getXZ($index, $chunkX, $chunkZ);
            foreach ($this->level->getChunkEntities($chunkX, $chunkZ) as $entity) {
                if ($entity !== $this and !$entity->isClosed() and $entity->isAlive() and !$entity->isFlaggedForDespawn()) {
                    $entity->spawnTo($this);
                }
            }
        }

		$this->spawnToAll();

		if($this->level instanceof Level){
		    $this->level->getWeather()->sendWeather($this);
		}

		if($this->server->getUpdater()->hasUpdate() and $this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE) and $this->server->getProperty("auto-updater.on-update.warn-ops", true)){
			$this->server->getUpdater()->showPlayerUpdate($this);
		}

		if($this->getHealth() <= 0){
			$this->respawn();
		}
	}

	protected function sendRespawnPacket(Vector3 $pos, int $respawnState = RespawnPacket::SEARCHING_FOR_SPAWN){
		$pk = new RespawnPacket();
		$pk->position = $pos->add(0, $this->baseOffset, 0);
		$pk->respawnState = $respawnState;
		$pk->entityRuntimeId = $this->getId();

		$this->dataPacket($pk);
	}

    protected function selectChunks(): \Generator
    {
        $radius = $this->server->getAllowedViewDistance($this->viewDistance);
        $radiusSquared = $radius ** 2;

        $centerX = $this->getFloorX() >> 4;
        $centerZ = $this->getFloorZ() >> 4;

        for ($x = 0; $x < $radius; ++$x) {
            for ($z = 0; $z <= $x; ++$z) {
                if (($x ** 2 + $z ** 2) > $radiusSquared) {
                    break; //skip to next band
                }

                //If the chunk is in the radius, others at the same offsets in different quadrants are also guaranteed to be.

                /* Top right quadrant */
                yield Level::chunkHash($centerX + $x, $centerZ + $z);
                /* Top left quadrant */
                yield Level::chunkHash($centerX - $x - 1, $centerZ + $z);
                /* Bottom right quadrant */
                yield Level::chunkHash($centerX + $x, $centerZ - $z - 1);
                /* Bottom left quadrant */
                yield Level::chunkHash($centerX - $x - 1, $centerZ - $z - 1);

                if ($x !== $z) {
                    /* Top right quadrant mirror */
                    yield Level::chunkHash($centerX + $z, $centerZ + $x);
                    /* Top left quadrant mirror */
                    yield Level::chunkHash($centerX - $z - 1, $centerZ + $x);
                    /* Bottom right quadrant mirror */
                    yield Level::chunkHash($centerX + $z, $centerZ - $x - 1);
                    /* Bottom left quadrant mirror */
                    yield Level::chunkHash($centerX - $z - 1, $centerZ - $x - 1);
                }
            }
        }
    }

    protected function orderChunks() : void{
        if(!$this->isConnected() or $this->viewDistance === -1){
            return;
        }

        Timings::$playerChunkOrderTimer->startTiming();

        $newOrder = [];
        $unloadChunks = $this->usedChunks;

        foreach ($this->selectChunks() as $hash) {
            if (!isset($this->usedChunks[$hash]) or $this->usedChunks[$hash] === false) {
                $newOrder[$hash] = true;
            }
            unset($unloadChunks[$hash]);
        }

        foreach ($unloadChunks as $index => $bool) {
            Level::getXZ($index, $X, $Z);
            $this->unloadChunk($X, $Z);
        }

        $this->loadQueue = $newOrder;
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_310){
            if(!empty($this->loadQueue) or !empty($unloadChunks)){
                $pk = new NetworkChunkPublisherUpdatePacket();
                $pk->x = $this->getFloorX();
                $pk->y = $this->getFloorY();
                $pk->z = $this->getFloorZ();
                $pk->radius = $this->viewDistance * 16; //blocks, not chunks >.>
                $this->sendDataPacket($pk);
            }
        }

        Timings::$playerChunkOrderTimer->stopTiming();
    }

	/**
	 * @return Position
	 */
	public function getSpawn(){
		if($this->hasValidSpawnPosition()){
			return $this->spawnPosition;
		}else{
			$level = $this->server->getDefaultLevel();

			return $level->getSafeSpawn();
		}
	}

	/**
	 * @return bool
	 */
	public function hasValidSpawnPosition() : bool{
		return $this->spawnPosition !== null and $this->spawnPosition->isValid();
	}

	/**
	 * Sets the spawnpoint of the player (and the compass direction) to a Vector3, or set it on another world with a
	 * Position object
	 *
	 * @param Vector3|Position $pos
	 */
	public function setSpawn(Vector3 $pos){
		if(!($pos instanceof Position)){
			$level = $this->level;
		}else{
			$level = $pos->getLevelNonNull();
		}
		$this->spawnPosition = new Position($pos->x, $pos->y, $pos->z, $level);
		$pk = new SetSpawnPositionPacket();
		$pk->x = $pk->x2 = $this->spawnPosition->getFloorX();
		$pk->y = $pk->y2 = $this->spawnPosition->getFloorY();
		$pk->z = $pk->z2 = $this->spawnPosition->getFloorZ();
		$pk->dimension = $this->spawnPosition->getLevelNonNull()->getDimensionId();
		$pk->spawnType = SetSpawnPositionPacket::TYPE_PLAYER_SPAWN;
		$pk->spawnForced = false;
		$this->dataPacket($pk);
	}

	/**
	 * @return bool
	 */
	public function isSleeping() : bool{
		return $this->sleeping !== null;
	}

	/**
	 * @param Vector3 $pos
	 *
	 * @return bool
	 */
	public function sleepOn(Vector3 $pos) : bool{
		if(!$this->isOnline()){
			return false;
		}

		$pos = $pos->floor();
		$b = $this->level->getBlock($pos);

		$ev = new PlayerBedEnterEvent($this, $b);
		$ev->call();
		if($ev->isCancelled()){
			return false;
		}

		if($b instanceof Bed){
			$b->setOccupied();
		}

		$this->sleeping = clone $pos;

		$this->propertyManager->setBlockPos(self::DATA_PLAYER_BED_POSITION, $pos);
		$this->setPlayerFlag(self::DATA_PLAYER_FLAG_SLEEP, true);

		$this->setSpawn($pos);

		$this->level->setSleepTicks(60);
		$this->updateBoundingBox(0.2, 0.2);

		return true;
	}

	public function stopSleep(){
		if($this->sleeping instanceof Vector3){
			$b = $this->level->getBlock($this->sleeping);
			if($b instanceof Bed){
				$b->setOccupied(false);
			}
			(new PlayerBedLeaveEvent($this, $b))->call();

			$this->sleeping = null;
			$this->updateBoundingBox(1.8, 0.6);
			$this->propertyManager->setBlockPos(self::DATA_PLAYER_BED_POSITION, null);
			$this->setPlayerFlag(self::DATA_PLAYER_FLAG_SLEEP, false);

			$this->level->setSleepTicks(0);

			$pk = new AnimatePacket();
			$pk->entityRuntimeId = $this->id;
			$pk->action = AnimatePacket::ACTION_STOP_SLEEP;
			$this->dataPacket($pk);
		}
	}

	public function setSneaking(bool $value = true) : void{
		parent::setSneaking($value);

		if($value){
			$this->updateBoundingBox(1.65, 0.6);
		}else{
			$this->updateBoundingBox(1.8, 0.6);
		}
	}

	public function setGliding(bool $value = true) : void{
		parent::setGliding($value);

		if($value){
			$this->updateBoundingBox(0.6, 0.6);
		}else{
			$this->updateBoundingBox(1.8, 0.6);
		}
	}

	public function setSwimming(bool $value = true) : void{
		parent::setSwimming($value);

		if($value){
			$this->updateBoundingBox(0.6, 0.6);
		}else{
			$this->updateBoundingBox(1.8, 0.6);
		}
	}

	/**
	 * @param string $achievementId
	 *
	 * @return bool
	 */
	public function hasAchievement(string $achievementId) : bool{
		if(!isset(Achievement::$list[$achievementId])){
			return false;
		}

		return $this->achievements[$achievementId] ?? false;
	}

	/**
	 * @param string $achievementId
	 *
	 * @return bool
	 */
	public function awardAchievement(string $achievementId) : bool{
		if(isset(Achievement::$list[$achievementId]) and !$this->hasAchievement($achievementId)){
			foreach(Achievement::$list[$achievementId]["requires"] as $requirementId){
				if(!$this->hasAchievement($requirementId)){
					return false;
				}
			}
			$ev = new PlayerAchievementAwardedEvent($this, $achievementId);
			$ev->call();
			if(!$ev->isCancelled()){
				$this->achievements[$achievementId] = true;
				Achievement::broadcast($this, $achievementId);

				return true;
			}else{
				return false;
			}
		}

		return false;
	}

	/**
	 * @param string $achievementId
	 */
	public function removeAchievement(string $achievementId){
		if($this->hasAchievement($achievementId)){
			$this->achievements[$achievementId] = false;
		}
	}

	/**
	 * @return int
	 */
	public function getGamemode() : int{
		return $this->gamemode;
	}

	/**
	 * @internal
	 *
	 * Returns a client-friendly gamemode of the specified real gamemode
	 * This function takes care of handling gamemodes known to MCPE (as of 1.1.0.3, that includes Survival, Creative and Adventure)
	 *
	 * TODO: remove this when Spectator Mode gets added properly to MCPE
	 *
	 * @param int $gamemode
	 *
	 * @return int
	 */
	public static function getClientFriendlyGamemode(int $gamemode) : int{
	  static $map = [
			self::SURVIVAL => GameMode::SURVIVAL,
			self::CREATIVE => GameMode::CREATIVE,
			self::ADVENTURE => GameMode::ADVENTURE,
			self::SPECTATOR => GameMode::CREATIVE
		];
		return $map[$gamemode & 0x3];
	}

    public static function gamemodeToNetworkId(int $gamemode, int $playerProtocol) : int{
		return (($playerProtocol >= ProtocolInfo::PROTOCOL_560 && ($gamemode & 0x3) === self::SPECTATOR) ? GameMode::SPECTATOR : self::getClientFriendlyGamemode($gamemode));
	}

	public static function protocolGameModeToCore(int $gameMode) : ?int{
		static $map = [
			GameMode::SURVIVAL => self::SURVIVAL,
			GameMode::CREATIVE => self::CREATIVE,
			GameMode::ADVENTURE => self::ADVENTURE,
			GameMode::SURVIVAL_VIEWER => self::SPECTATOR,
			GameMode::CREATIVE_VIEWER => self::SPECTATOR,
			GameMode::SPECTATOR => self::SPECTATOR,
		];
		return $map[$gameMode] ?? null;
	}

	/**
	 * @param int $dimension
	 *
	 * @return int
	 */
	public function getClientFriendlyDimension(int $dimension) : int{
		return ($this->getProtocol() >= ProtocolInfo::PROTOCOL_100 ? $dimension : ($dimension & 0x1));
	}

	/**
	 * Sets the gamemode, and if needed, kicks the Player.
	 *
	 * @param int  $gm
	 * @param bool $client if the client made this change in their GUI
	 *
	 * @return bool
	 */
	public function setGamemode(int $gm, bool $client = false) : bool{
		if($gm < 0 or $gm > 3 or $this->gamemode === $gm){
			return false;
		}

		$ev = new PlayerGameModeChangeEvent($this, $gm);
		$ev->call();
		if($ev->isCancelled()){
			if($client){ //gamemode change by client in the GUI
				$this->sendGamemode();
			}
			return false;
		}

		$this->gamemode = $gm;

		$this->allowFlight = $this->isCreative();
		if($this->isSpectator()){
			$this->setGenericFlag(self::DATA_FLAG_HAS_COLLISION, false);
			$this->setFlying(true);
			$this->keepMovement = true;
			$this->onGround = false;

			//TODO: HACK! this syncs the onground flag with the client so that flying works properly
			//this is a yucky hack but we don't have any other options :(
			$this->sendPosition($this, null, null, MovePlayerPacket::MODE_TELEPORT);

			$this->despawnFromAll();
		}else{
			$this->setGenericFlag(self::DATA_FLAG_HAS_COLLISION, true);
			$this->keepMovement = $this->allowMovementCheats;
			$this->checkGroundState(0, 0, 0, 0, 0, 0);
			if($this->isSurvival()){
				$this->setFlying(false);
			}
			$this->spawnToAll();
		}

		$this->namedtag->setInt("playerGameType", $this->gamemode);
		if(!$client){ //Gamemode changed by server, do not send for client changes
			$this->sendGamemode();
		}else{
			Command::broadcastCommandMessage($this, new TranslationContainer("commands.gamemode.success.self", [Server::getGamemodeString($gm)]));
		}

		$this->inventory->sendContents($this);
		$this->inventory->sendHeldItem($this->hasSpawned);

		$this->syncAbilitiesAndAdventureSettings(); //TODO: we might be able to do this with the abilities packet alone
		$this->inventory->sendCreativeContents();

		return true;
	}

	/**
	 * @internal
	 * Sends the player's gamemode to the client.
	 */
	public function sendGamemode(){
		$pk = new SetPlayerGameTypePacket();
		$pk->gamemode = Player::gamemodeToNetworkId($this->gamemode, $this->getProtocol());
		$this->dataPacket($pk);
	}

    public function syncAbilities(){
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_534){
	    	//ALL of these need to be set for the base layer, otherwise the client will cry
	    	$boolAbilities = [
		    	AbilitiesLayer::ABILITY_ALLOW_FLIGHT => $this->getAllowFlight(),
		    	AbilitiesLayer::ABILITY_FLYING => $this->isFlying(),
		    	AbilitiesLayer::ABILITY_NO_CLIP => $this->isSpectator(),
		    	AbilitiesLayer::ABILITY_OPERATOR => ($this->isOp() && !$this->isSpectator()),
		    	AbilitiesLayer::ABILITY_TELEPORT => $this->hasPermission("pocketmine.command.teleport"),
		    	AbilitiesLayer::ABILITY_INVULNERABLE => $this->isCreative(),
		    	AbilitiesLayer::ABILITY_MUTED => false,
		    	AbilitiesLayer::ABILITY_WORLD_BUILDER => false,
		    	AbilitiesLayer::ABILITY_INFINITE_RESOURCES => !$this->hasFiniteResources(),
		    	AbilitiesLayer::ABILITY_LIGHTNING => false,
		    	AbilitiesLayer::ABILITY_BUILD => !$this->isSpectator(),
		    	AbilitiesLayer::ABILITY_MINE => !$this->isSpectator(),
		    	AbilitiesLayer::ABILITY_DOORS_AND_SWITCHES => !$this->isSpectator(),
		    	AbilitiesLayer::ABILITY_OPEN_CONTAINERS => !$this->isSpectator(),
		    	AbilitiesLayer::ABILITY_ATTACK_PLAYERS => !$this->isSpectator(),
		    	AbilitiesLayer::ABILITY_ATTACK_MOBS => !$this->isSpectator(),
	    	];

            $layers = [
				//TODO: dynamic flying speed! FINALLY!!!!!!!!!!!!!!!!!
				new AbilitiesLayer(AbilitiesLayer::LAYER_BASE, $boolAbilities, 0.05, 1.0, 0.1),
			];

			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_582){
				if($this->isSpectator()){
					//TODO: HACK! In 1.19.80, the client starts falling in our faux spectator mode when it clips into a
					//block. We can't seem to prevent this short of forcing the player to always fly when block collision is
					//disabled. Also, for some reason the client always reads flight state from this layer if present, even
					//though the player isn't in spectator mode.
		
					$layers[] = new AbilitiesLayer(AbilitiesLayer::LAYER_SPECTATOR, [
						AbilitiesLayer::ABILITY_FLYING => true,
					], null, null, null);
				}
			}

	    	$this->sendDataPacket(UpdateAbilitiesPacket::create(new AbilitiesData(
		    	(($this->isOp() && !$this->isSpectator()) ? AdventureSettingsPacket::PERMISSION_OPERATOR : AdventureSettingsPacket::PERMISSION_NORMAL),
		    	(($this->isOp() && !$this->isSpectator()) ? PlayerPermissions::OPERATOR : PlayerPermissions::MEMBER),
		    	$this->getId(),
				$layers
	    	)));
        }else{
	    	$pk = new AdventureSettingsPacket();

			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
		    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
		        	$pk->setFlag(AdventureSettingsPacket::BUILD, !$this->isSpectator());
		        	$pk->setFlag(AdventureSettingsPacket::MINE, !$this->isSpectator());
		        	$pk->setFlag(AdventureSettingsPacket::DOORS_AND_SWITCHES, !$this->isSpectator());
			    	$pk->setFlag(AdventureSettingsPacket::OPEN_CONTAINERS, !$this->isSpectator());
			    	$pk->setFlag(AdventureSettingsPacket::ATTACK_PLAYERS, !$this->isSpectator());
			    	$pk->setFlag(AdventureSettingsPacket::ATTACK_MOBS, !$this->isSpectator());
			    	$pk->setFlag(AdventureSettingsPacket::OPERATOR, ($this->isOp() && !$this->isSpectator()));
			    	$pk->setFlag(AdventureSettingsPacket::TELEPORT, $this->hasPermission("pocketmine.command.teleport"));
		    	}
	        	$pk->setFlag(AdventureSettingsPacket::AUTO_JUMP, $this->autoJump);
	        	$pk->setFlag(AdventureSettingsPacket::ALLOW_FLIGHT, $this->allowFlight);
	        	$pk->setFlag(AdventureSettingsPacket::NO_CLIP, $this->isSpectator());
	        	$pk->setFlag(AdventureSettingsPacket::FLYING, $this->flying);
	        	$pk->setFlag(AdventureSettingsPacket::WORLD_IMMUTABLE, $this->isSpectator());
	        	$pk->setFlag(AdventureSettingsPacket::NO_PVP, $this->isSpectator());

	        	$pk->commandPermission = (($this->isOp() && !$this->isSpectator()) ? AdventureSettingsPacket::PERMISSION_OPERATOR : AdventureSettingsPacket::PERMISSION_NORMAL);
	        	$pk->playerPermission = (($this->isOp() && !$this->isSpectator()) ? PlayerPermissions::OPERATOR : PlayerPermissions::MEMBER);
	    	$pk->entityUniqueId = $this->getId();
			}else{
			    if($this->autoJump){
			        $pk->flags |= AdventureSettingsPacket::PRE_V_0_15_0_AUTO_JUMP;
			    }
			    if($this->allowFlight){
			        $pk->flags |= AdventureSettingsPacket::PRE_V_0_15_0_ALLOW_FLIGHT;
			    }
			    if($this->isSpectator()){
	            	$pk->flags |= AdventureSettingsPacket::PRE_V_0_15_0_NO_CLIP;
	            	$pk->flags |= AdventureSettingsPacket::WORLD_IMMUTABLE;
	            	$pk->flags |= AdventureSettingsPacket::NO_PVP;
			    }

			    $pk->commandPermission = AdventureSettingsPacket::PERMISSION_HOST; // wtf, idk how to send this correctly
			    $pk->playerPermission = AdventureSettingsPacket::PERMISSION_HOST; // wtf, idk how to send this correctly
			}

	    	$this->dataPacket($pk);
        }
    }

	public function syncAdventureSettings(){
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_534){
	    	//everything except auto jump is handled via UpdateAbilitiesPacket
	    	$this->sendDataPacket(UpdateAdventureSettingsPacket::create(
		    	false, // noAttackingMobs
		    	false, // noAttackingPlayers
		    	false, // worldImmutable
		    	true, // showNameTags
		    	$this->hasAutoJump() // autoJump
	    	));
        }else{
            $this->syncAbilities();
        }
	}

    public function syncAbilitiesAndAdventureSettings(){
        $this->syncAbilities();
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_534){
            $this->syncAdventureSettings();
        }
    }

	/**
	 * NOTE: Because Survival and Adventure Mode share some similar behaviour, this method will also return true if the player is
	 * in Adventure Mode. Supply the $literal parameter as true to force a literal Survival Mode check.
	 *
	 * @param bool $literal whether a literal check should be performed
	 *
	 * @return bool
	 */
	public function isSurvival(bool $literal = false) : bool{
		if($literal){
			return $this->gamemode === Player::SURVIVAL;
		}else{
			return ($this->gamemode & 0x01) === 0;
		}
	}

	/**
	 * NOTE: Because Creative and Spectator Mode share some similar behaviour, this method will also return true if the player is
	 * in Spectator Mode. Supply the $literal parameter as true to force a literal Creative Mode check.
	 *
	 * @param bool $literal whether a literal check should be performed
	 *
	 * @return bool
	 */
	public function isCreative(bool $literal = false) : bool{
		if($literal){
			return $this->gamemode === Player::CREATIVE;
		}else{
			return ($this->gamemode & 0x01) === 1;
		}
	}

	/**
	 * NOTE: Because Adventure and Spectator Mode share some similar behaviour, this method will also return true if the player is
	 * in Spectator Mode. Supply the $literal parameter as true to force a literal Adventure Mode check.
	 *
	 * @param bool $literal whether a literal check should be performed
	 *
	 * @return bool
	 */
	public function isAdventure(bool $literal = false) : bool{
		if($literal){
			return $this->gamemode === Player::ADVENTURE;
		}else{
			return ($this->gamemode & 0x02) > 0;
		}
	}

	/**
	 * @return bool
	 */
	public function isSpectator() : bool{
		return $this->gamemode === Player::SPECTATOR;
	}

	/**
	 * TODO: make this a dynamic ability instead of being hardcoded
	 */
	public function hasFiniteResources() : bool{
		return $this->gamemode === Player::SURVIVAL || $this->gamemode === Player::ADVENTURE;
	}

	public function isFireProof() : bool{
		return $this->isCreative();
	}

	public function getDrops() : array{
		if(!$this->isCreative()){
			return parent::getDrops();
		}

		return [];
	}

	public function getXpDropAmount() : int{
		if(!$this->isCreative()){
			return parent::getXpDropAmount();
		}

		return 0;
	}

	protected function checkGroundState(float $movX, float $movY, float $movZ, float $dx, float $dy, float $dz) : void{
		if($this->isSpectator()){
			$this->onGround = false;
		}else{
			$bb = clone $this->boundingBox;
			$bb->minY = $this->y - 0.2;
			$bb->maxY = $this->y + 0.2;

			$this->onGround = $this->isCollided = count($this->level->getCollisionBlocks($bb, true)) > 0;
		}
	}

	public function canBeMovedByCurrents() : bool{
		return false; //currently has no server-side movement
	}

	protected function checkNearEntities(){
		foreach($this->level->getNearbyEntities($this->boundingBox->expandedCopy(1, 0.5, 1), $this) as $entity){
			$entity->scheduleUpdate();

			if(!$entity->isAlive() or $entity->isFlaggedForDespawn()){
				continue;
			}

			$entity->onCollideWithPlayer($this);
		}
	}

	protected function handleMovement(Vector3 $newPos) : void{
		$this->moveRateLimit--;
		if($this->moveRateLimit < 0){
			return;
		}

		$oldPos = $this->asLocation();
		$distanceSquared = $newPos->distanceSquared($oldPos);

		$revert = false;

		if($distanceSquared > 100){
			//TODO: this is probably too big if we process every movement
			/* !!! BEWARE YE WHO ENTER HERE !!!
			 *
			 * This is NOT an anti-cheat check. It is a safety check.
			 * Without it hackers can teleport with freedom on their own and cause lots of undesirable behaviour, like
			 * freezes, lag spikes and memory exhaustion due to sync chunk loading and collision checks across large distances.
			 * Not only that, but high-latency players can trigger such behaviour innocently.
			 *
			 * If you must tamper with this code, be aware that this can cause very nasty results. Do not waste our time
			 * asking for help if you suffer the consequences of messing with this.
			 */
			$this->server->getLogger()->debug($this->getName() . " moved too fast, reverting movement");
			$this->server->getLogger()->debug("Old position: " . $this->asVector3() . ", new position: " . $newPos);
			$revert = true;
		}elseif(!$this->level->isInLoadedTerrain($newPos) or !$this->level->isChunkGenerated($newPos->getFloorX() >> 4, $newPos->getFloorZ() >> 4)){
			$revert = true;
			$this->nextChunkOrderRun = 0;
		}

		if(!$revert and $distanceSquared != 0){
			$dx = $newPos->x - $this->x;
			$dy = $newPos->y - $this->y;
			$dz = $newPos->z - $this->z;

			//the client likes to clip into blocks like stairs, but we do full server-side prediction of that without
			//help from the client's position changes, so we deduct the expected clip height from the moved distance.
			$expectedClipDistance = $this->ySize * (1 - self::STEP_CLIP_MULTIPLIER);
			$dy -= $expectedClipDistance;
			$this->move($dx, $dy, $dz);

			$diff = $this->distanceSquared($newPos);

			//TODO: Explore lowering this threshold now that stairs work properly.
			if($this->isSurvival() and $diff > 0.0625){
				$ev = new PlayerIllegalMoveEvent($this, $newPos, new Vector3($this->lastX, $this->lastY, $this->lastZ));
				$ev->setCancelled($this->allowMovementCheats);

				$ev->call();

				if(!$ev->isCancelled()){
					$revert = true;
					$this->server->getLogger()->debug($this->getServer()->getLanguage()->translateString("pocketmine.player.invalidMove", [$this->getName()]));
					$this->server->getLogger()->debug("Old position: " . $this->asVector3() . ", new position: " . $newPos . ", expected clip distance: $expectedClipDistance");
				}
			}

			if($diff > 0 and !$revert){
				$this->setPosition($newPos);
			}
		}

		if($revert){
			$this->revertMovement($oldPos);
		}
	}

	/**
	 * Fires movement events and synchronizes player movement, every tick.
	 */
	protected function processMostRecentMovements() : void{
		$now = microtime(true);
		$multiplier = $this->lastMovementProcess !== null ? ($now - $this->lastMovementProcess) * 20 : 1;
		$exceededRateLimit = $this->moveRateLimit < 0;
		$this->moveRateLimit = min(self::MOVE_BACKLOG_SIZE, max(0, $this->moveRateLimit) + self::MOVES_PER_TICK * $multiplier);
		$this->lastMovementProcess = $now;

		$from = new Location($this->lastX, $this->lastY, $this->lastZ, $this->lastYaw, $this->lastPitch, $this->level);
		$to = $this->getLocation();

		$delta = (($this->lastX - $to->x) ** 2) + (($this->lastY - $to->y) ** 2) + (($this->lastZ - $to->z) ** 2);
		$deltaAngle = abs($this->lastYaw - $to->yaw) + abs($this->lastPitch - $to->pitch);

		if($delta > 0.0001 or $deltaAngle > 1.0){
			$ev = new PlayerMoveEvent($this, $from, $to);

			$ev->call();

			if($ev->isCancelled()){
				$this->revertMovement($from);
				return;
			}

			if($to->distanceSquared($ev->getTo()) > 0.01){ //If plugins modify the destination
				$this->teleport($ev->getTo());
				return;
			}

			$this->lastX = $to->x;
			$this->lastY = $to->y;
			$this->lastZ = $to->z;

			$this->lastYaw = $to->yaw;
			$this->lastPitch = $to->pitch;
			$this->broadcastMovement();

			$distance = sqrt((($from->x - $to->x) ** 2) + (($from->z - $to->z) ** 2));
			if($this->isSprinting()){
				$this->exhaust(0.1 * $distance, PlayerExhaustEvent::CAUSE_SPRINTING);
			}elseif($this->isSwimming()){
				$this->exhaust(0.015 * $distance, PlayerExhaustEvent::CAUSE_SWIMMING);
			}else{
				$this->exhaust(0.01 * $distance, PlayerExhaustEvent::CAUSE_WALKING);
			}

			if($this->server->netherEnabled){
				if($this->isInsideOfPortal()){
					if($this->portalTime === 0){
						$this->portalTime = $this->server->getTick();
					}
				}else{
					$this->portalTime = 0;
				}
			}

			if($this->isOnGround() && $this->isGliding()){
				$this->toggleGlide(false);
			}

			if($this->nextChunkOrderRun > 20){
				$this->nextChunkOrderRun = 20;
			}
		}

		if($exceededRateLimit){ //client and server positions will be out of sync if this happens
			$this->server->getLogger()->debug("Player " . $this->getName() . " exceeded movement rate limit, forcing to last accepted position");
			$this->sendPosition($this, $this->yaw, $this->pitch, MovePlayerPacket::MODE_RESET);
		}
	}

	protected function revertMovement(Location $from) : void{
		$this->setPosition($from);
		$this->sendPosition($from, $from->yaw, $from->pitch, MovePlayerPacket::MODE_RESET);
	}

	public function fall(float $fallDistance) : void{
		if(!$this->flying){
			parent::fall($fallDistance);
		}
	}

    public function setSkin(Skin $skin): void
    {
        parent::setSkin($skin);
        if ($this->spawned) {
            $this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getDisplayName(), $this->getSkin(), $this->getXuid());
        }
    }

	public function jump() : void{
		(new PlayerJumpEvent($this))->call();
		parent::jump();
	}

	public function setMotion(Vector3 $motion) : bool{
		if(parent::setMotion($motion)){
			$this->broadcastMotion();

			return true;
		}
		return false;
	}

	protected function updateMovement(bool $teleport = false) : void{

	}

	protected function tryChangeMovement() : void{

	}

	public function sendAttributes(bool $sendAll = false){
		$entries = $sendAll ? $this->attributeMap->getAll() : $this->attributeMap->needSend();
		if(count($entries) > 0){
			$pk = new UpdateAttributesPacket();
			$pk->entityRuntimeId = $this->id;
			$pk->entries = $entries;
			$this->dataPacket($pk);
			foreach($entries as $entry){
				$entry->markSynchronized();
			}
		}
	}

	public function onUpdate(int $currentTick) : bool{
		if(!$this->loggedIn){
			return false;
		}

		$tickDiff = $currentTick - $this->lastUpdate;

		if($tickDiff <= 0){
			return true;
		}

		$this->messageCounter = 2;

		$this->lastUpdate = $currentTick;

		$this->sendAttributes();

		if(!$this->isAlive() and $this->spawned){
			$this->onDeathUpdate($tickDiff);
			return true;
		}

		$this->timings->startTiming();

		if($this->spawned){
			if($this->server->netherEnabled){
				if(($this->isCreative() or $this->isSurvival() and $this->server->getTick() - $this->portalTime >= 80) and $this->portalTime > 0){
					$netherLevel = null;
					if($this->server->isLevelLoaded($this->server->netherName) or $this->server->loadLevel($this->server->netherName)){
						$netherLevel = $this->server->getLevelByName($this->server->netherName);
					}

					if($netherLevel instanceof Level){
						if($this->getLevelNonNull() !== $netherLevel){
							$this->fromPos = $this->getPosition();
							$this->fromPos->x = ((int) $this->fromPos->x) + 0.5;
							$this->fromPos->z = ((int) $this->fromPos->z) + 0.5;
							$this->teleport($this->shouldResPos = $netherLevel->getSafeSpawn());
						}elseif($this->fromPos instanceof Position){
							if(!($this->getLevelNonNull()->isChunkLoaded((int) $this->fromPos->x, (int) $this->fromPos->z))){
								$this->getLevelNonNull()->loadChunk((int) $this->fromPos->x, (int) $this->fromPos->z);
							}
							$add = [1, 0, -1, 0, 0, 1, 0, -1];
							$tempos = null;
							for($j = 2; $j < 5; $j++){
								for($i = 0; $i < 4; $i++){
									if($this->fromPos->getLevelNonNull()->getBlock($this->temporalVector->fromObjectAdd($this->fromPos, $add[$i] * $j, 0, $add[$i + 4] * $j))->getId() === Block::AIR){
										if($this->fromPos->getLevelNonNull()->getBlock($this->temporalVector->fromObjectAdd($this->fromPos, $add[$i] * $j, 1, $add[$i + 4] * $j))->getId() === Block::AIR){
											$tempos = Position::fromObject($this->fromPos->add($add[$i] * $j, 0, $add[$i + 4] * $j), $this->fromPos->getLevelNonNull());
										    break;
										}
									}
								}
								if($tempos != null){
									break;
								}
							}
							if($tempos === null){
								$tempos = Position::fromObject($this->fromPos->add(mt_rand(-2, 2), 0, mt_rand(-2, 2)), $this->fromPos->getLevelNonNull());
							}
							$this->teleport($this->shouldResPos = $tempos);
							$add = null;
							$tempos = null;
							$this->fromPos = null;
						}else{
							$this->teleport($this->shouldResPos = $this->server->getDefaultLevel()->getSafeSpawn());
						}
						$this->portalTime = 0;
					}
				}
			}
			if($this->getInventory() !== null){
				$this->inventory->getItemInHand()->onUpdate($this);
			}
			if($this->getOffHandInventory() !== null){
				$this->offHandInventory->getItemInHand()->onUpdate($this);
			}
			$this->processMostRecentMovements();
			$this->motion->x = $this->motion->y = $this->motion->z = 0; //TODO: HACK! (Fixes player knockback being messed up)
			if($this->onGround){
				$this->inAirTicks = 0;
			}else{
				$this->inAirTicks += $tickDiff;
			}

			Timings::$timerEntityBaseTick->startTiming();
			$this->entityBaseTick($tickDiff);
			Timings::$timerEntityBaseTick->stopTiming();

			if(!$this->isSpectator() and $this->isAlive()){
				Timings::$playerCheckNearEntitiesTimer->startTiming();
				$this->checkNearEntities();
				Timings::$playerCheckNearEntitiesTimer->stopTiming();
			}

			if($this->isOnFire() or $this->lastUpdate % 10 == 0){
				if($this->isCreative() and !$this->isInsideOfFire()){
					$this->extinguish();
				}
			}

            if($this->getTransactionQueue() !== null){
                $this->getTransactionQueue()->execute();
            }

			if(!$this->getProtocol() < ProtocolInfo::PROTOCOL_130 and $this->chunkHack > 0){
				--$this->chunkHack;
				if($this->chunkHack === 0){
					$pk = new ChunkRadiusUpdatedPacket();
					$pk->radius = $this->viewDistance;
					$this->sendDataPacket($pk);
				}elseif($this->chunkHack === 1){
					$pk = new ChunkRadiusUpdatedPacket();
					$pk->radius = $this->viewDistance + 1;
					$this->sendDataPacket($pk);
				}
			}
		}

		$this->timings->stopTiming();

		return true;
	}

	protected function doFoodTick(int $tickDiff = 1) : void{
		if($this->isSurvival()){
			parent::doFoodTick($tickDiff);
		}
	}

	public function exhaust(float $amount, int $cause = PlayerExhaustEvent::CAUSE_CUSTOM) : float{
		if($this->isSurvival()){
			return parent::exhaust($amount, $cause);
		}

		return 0.0;
	}

	public function isHungry() : bool{
		return $this->isSurvival() and parent::isHungry();
	}

	public function canBreathe() : bool{
		return $this->isCreative() or parent::canBreathe();
	}

	protected function sendEffectAdd(EffectInstance $effect, bool $replacesOldEffect) : void{
		$pk = new MobEffectPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->eventId = $replacesOldEffect ? MobEffectPacket::EVENT_MODIFY : MobEffectPacket::EVENT_ADD;
		$pk->effectId = $effect->getId();
		$pk->amplifier = $effect->getAmplifier();
		$pk->particles = $effect->isVisible();
		$pk->duration = $effect->getDuration();

		$this->dataPacket($pk);
	}

	protected function sendEffectRemove(EffectInstance $effect) : void{
		$pk = new MobEffectPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->eventId = MobEffectPacket::EVENT_REMOVE;
		$pk->effectId = $effect->getId();

		$this->dataPacket($pk);
	}

	public function checkNetwork(){
		if(!$this->isOnline()){
			return;
		}

		if($this->nextChunkOrderRun !== PHP_INT_MAX and $this->nextChunkOrderRun-- <= 0){
			$this->nextChunkOrderRun = PHP_INT_MAX;
			$this->orderChunks();
		}

		if(count($this->loadQueue) > 0){
			$this->sendNextChunk();
		}

		if(count($this->batchedPackets) > 0){
			$this->server->batchPackets([$this], $this->batchedPackets, false);
			$this->batchedPackets = [];
		}
	}

	/**
	 * Returns whether the player can interact with the specified position. This checks distance and direction.
	 *
	 * @param Vector3 $pos
	 * @param float   $maxDistance
	 * @param float   $maxDiff defaults to half of the 3D diagonal width of a block
	 *
	 * @return bool
	 */
	public function canInteract(Vector3 $pos, float $maxDistance, float $maxDiff = M_SQRT3 / 2) : bool{
		$eyePos = $this->getPosition()->add(0, $this->getEyeHeight(), 0);
		if($eyePos->distanceSquared($pos) > $maxDistance ** 2){
			return false;
		}

		$dV = $this->getDirectionVector();
		$eyeDot = $dV->dot($eyePos);
		$targetDot = $dV->dot($pos);
		return ($targetDot - $eyeDot) >= -$maxDiff;
	}

	protected function initHumanData() : void{
		$this->setNameTag($this->username);
	}

	protected function initEntity() : void{
		parent::initEntity();
		$this->addDefaultWindows();
	}

	public function handleRequestNetworkSettings(RequestNetworkSettingsPacket $packet) : bool{
	    if($this->enableCompression) {
	        return false;
	    }

		$this->protocol = $packet->getProtocolVersion();
		if(!in_array($this->protocol, ProtocolInfo::ACCEPTED_PROTOCOLS)){
	    	$packet = new PlayStatusPacket();
	    	$packet->status = ($this->protocol < ProtocolInfo::CURRENT_PROTOCOL ? PlayStatusPacket::LOGIN_FAILED_CLIENT : PlayStatusPacket::LOGIN_FAILED_SERVER);
	    	$packet->setProtocol($this->protocol);

	    	$pk = new BatchPacket();
	    	$pk->setProtocol($this->protocol);
	    	$pk->addPacket($packet);
	    	$pk->compressionEnabled = false;
	    	$this->sendDataPacket($pk, false, true);

			//This pocketmine disconnect message will only be seen by the console (PlayStatusPacket causes the messages to be shown for the client)
			$this->close("", $this->server->getLanguage()->translateString("pocketmine.disconnect.incompatibleProtocol", [$this->protocol ?? "unknown"]), false);

			return true;
		}

		//TODO: we're filling in the defaults to get pre-1.19.30 behaviour back for now, but we should explore the new options in the future
		$packet = NetworkSettingsPacket::create(
			NetworkSettingsPacket::COMPRESS_EVERYTHING,
			CompressionAlgorithm::ZLIB,
			false,
			0,
			0
		);
		$packet->setProtocol($this->protocol);

		$pk = new BatchPacket();
		$pk->setProtocol($this->protocol);
		$pk->addPacket($packet);
		$pk->compressionEnabled = false;
		$this->sendDataPacket($pk);

        $this->enableCompression = true;

		return true;
	}

	public function handleLogin(LoginPacket $packet) : bool{
		if($this->loggedIn){
			return false;
		}

        $this->protocol = $packet->protocol;
		if(!$packet->isValidProtocol){
			if($packet->protocol < ProtocolInfo::CURRENT_PROTOCOL){
				$this->sendPlayStatus(PlayStatusPacket::LOGIN_FAILED_CLIENT, true);
			}else{
				$this->sendPlayStatus(PlayStatusPacket::LOGIN_FAILED_SERVER, true);
			}

			//This pocketmine disconnect message will only be seen by the console (PlayStatusPacket causes the messages to be shown for the client)
			$this->close("", $this->server->getLanguage()->translateString("pocketmine.disconnect.incompatibleProtocol", [$packet->protocol ?? "unknown"]), false);

			return true;
		}

		if(!self::isValidUserName($packet->username)){
			$this->close("", "disconnectionScreen.invalidName");

			return true;
		}

        if((isset($packet->clientData["DeviceModel"]) ? $packet->clientData["DeviceModel"] : "") === "Asus xyefon 4"){
			$this->close("", $this->server->getLanguage()->translateString("pocketmine.disconnect.invalidSession", ["%pocketmine.disconnect.invalidSession.badSignature"]));
			
            return true;
        }
        
        if((isset($packet->clientData["ServerAddress"]) ? $packet->clientData["ServerAddress"] : "") === "okda.net:19132"){
			$this->close("", $this->server->getLanguage()->translateString("pocketmine.disconnect.invalidSession", ["%pocketmine.disconnect.invalidSession.badSignature"]));

            return true;
        }
        
        if((UUID::fromString($packet->clientUUID)->getVersion()) !== 3){
			$this->close("", $this->server->getLanguage()->translateString("pocketmine.disconnect.invalidSession", ["%pocketmine.disconnect.invalidSession.badSignature"]));

            return true;
        }

        $this->protocol = ProtocolConvertor::convertProtocol($packet->protocol);
        $this->originalProtocol = $packet->protocol;
        $this->chunkProtocol = ProtocolConvertor::convertChunkProtocol($this->protocol);
        $this->craftingProtocol = ProtocolConvertor::convertCraftingProtocol($this->protocol);
		BlockPalette::initPalette($this->protocol);

		$this->username = str_replace(" ", "_", TextFormat::clean($packet->username));
		$this->displayName = $this->username;
		$this->iusername = strtolower($this->username);

		if($packet->locale !== null){
			$this->locale = $packet->locale;
		}

        $this->deviceOS = $packet->clientData["DeviceOS"] ?? null;
        $this->deviceModel = $packet->clientData["DeviceModel"] ?? "";

        $this->vanillaVersion = $packet->clientData["GameVersion"] ?? "";

		if(count($this->server->getOnlinePlayers()) >= $this->server->getMaxPlayers() and $this->kick("disconnectionScreen.serverFull", false)){
			return true;
		}

		$this->randomClientId = $packet->clientId;

		$this->uuid = UUID::fromString($packet->clientUUID);
		$this->rawUUID = $this->uuid->toBinary();

        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_370){
	    	$animations = [];
	    	foreach($packet->clientData["AnimatedImageData"] as $animation){
		    	$animations[] = new SkinAnimation(
		    	    new SkinImage(
		    	        $animation["ImageHeight"],
		    	        $animation["ImageWidth"],
		    	        base64_decode($animation["Image"], true
		    	        )),
		    	    $animation["Type"],
		    	    $animation["Frames"],
		    	    ($this->getProtocol() >= ProtocolInfo::PROTOCOL_419) ? $animation["AnimationExpression"] : 0
		    	);
	    	}

	    	if($this->getProtocol() === ProtocolInfo::PROTOCOL_390 || $this->getProtocol() >= ProtocolInfo::PROTOCOL_401){
	        	$personaPieces = [];
	        	foreach($packet->clientData["PersonaPieces"] as $piece){
		        	$personaPieces[] = new PersonaSkinPiece(
		        	    $piece["PieceId"],
		        	    $piece["PieceType"],
		        	    $piece["PackId"],
		        	    $piece["IsDefault"],
		        	    $piece["ProductId"]
		        	);
	        	}

	        	$pieceTintColors = [];
	        	foreach($packet->clientData["PieceTintColors"] as $tintColor){
		        	$pieceTintColors[] = new PersonaPieceTintColor($tintColor["PieceType"], $tintColor["Colors"]);
	        	}

		    	$armSize = $packet->clientData["ArmSize"] ?? "wide";
		    	$skinColor = isset($packet->clientData["SkinColor"]) ? Color::fromHexString($packet->clientData["SkinColor"]) : new Color(0, 0, 0);
		    	$personaPieces = SplFixedArray::fromArray($personaPieces);
		    	$pieceTintColors = SplFixedArray::fromArray($pieceTintColors);
	    	}else{
	    	    $armSize = "wide";
	    	    $skinColor = new Color(0, 0, 0);
		    	$personaPieces = SplFixedArray::fromArray([]);
		    	$pieceTintColors = SplFixedArray::fromArray([]);
	    	}

	    	$serializedSkin = new SerializedSkin(
		    	$packet->clientData["SkinId"],
		    	$packet->clientData["PlayFabId"] ?? "",
		    	new SkinImage(
		    	    $packet->clientData["SkinImageHeight"],
		    	    $packet->clientData["SkinImageWidth"],
		    	    base64_decode($packet->clientData["SkinData"], true)
		    	),
		    	$packet->clientData["CapeId"] ?? "",
		    	new SkinImage(
		    	    $packet->clientData["CapeImageHeight"],
		    	    $packet->clientData["CapeImageWidth"],
		    	    base64_decode($packet->clientData["CapeData"] ?? "", true)
		    	),
		    	base64_decode($packet->clientData["SkinResourcePatch"] ?? "", true),
		    	base64_decode($packet->clientData["SkinGeometryData"] ?? "", true),
                base64_decode($packet->clientData["SkinGeometryDataEngineVersion"] ?? "", true),
		    	base64_decode($packet->clientData["AnimationData"] ?? "", true),
		    	$animations,
		    	$packet->clientData["PremiumSkin"] ?? false,
		    	$packet->clientData["PersonaSkin"] ?? false,
		    	$packet->clientData["CapeOnClassicSkin"] ?? false,
		    	null,
		    	$armSize,
		    	$skinColor,
		    	$personaPieces,
		    	$pieceTintColors,
		    	true, //assume this is true? there's no field for it ...
		    	$packet->clientData["OverrideSkin"] ?? true,
	    	);

	    	$skin = $serializedSkin->toSkin();
        }else{
	    	$skin = new Skin(
		    	$packet->clientData["SkinId"],
		    	base64_decode($packet->clientData["SkinData"], true),
		    	base64_decode($packet->clientData["CapeData"] ?? "", true),
		    	$packet->clientData["SkinGeometryName"] ?? "",
		    	base64_decode($packet->clientData["SkinGeometry"] ?? "", true)
	    	);
        }

		if(!$skin->isValid()){
			$this->close("", "disconnectionScreen.invalidSkin");

			return true;
		}

		$this->setSkin($skin);

		$ev = new PlayerPreLoginEvent($this, "Plugin reason");
		$ev->call();
		if($ev->isCancelled()){
			$this->close("", $ev->getKickMessage());

			return true;
		}

		if(!$this->server->isWhitelisted($this->username) and $this->kick("Server is white-listed", false)){
			return true;
		}

		if(
			($this->isBanned() or $this->server->getIPBans()->isBanned($this->getAddress())) and
			$this->kick("You are banned", false)
		){
			return true;
		}

		if(!$packet->skipVerification){
			$this->server->getAsyncPool()->submitTask(new VerifyLoginTask($this, $packet));
		}else{
			$this->onVerifyCompleted($packet, null, true);
		}

		return true;
	}

	public function sendPlayStatus(int $status, bool $immediate = false){
		$pk = new PlayStatusPacket();
		$pk->status = $status;
		$this->sendDataPacket($pk, false, $immediate);
	}

	public function onVerifyCompleted(LoginPacket $packet, ?string $error, bool $signedByMojang) : void{
		if($this->closed){
			return;
		}

		if($error !== null){
			$this->close("", $this->server->getLanguage()->translateString("pocketmine.disconnect.invalidSession", [$error]));
			return;
		}

        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
	    	$xuid = $packet->xuid;

	    	if(!$signedByMojang and $xuid !== ""){
		    	$this->server->getLogger()->warning($this->getName() . " has an XUID, but their login keychain is not signed by Mojang");
		    	$xuid = "";
	    	}

	    	if($xuid === "" or !is_string($xuid)){
		    	if($signedByMojang){
			    	$this->server->getLogger()->error($this->getName() . " should have an XUID, but none found");
		    	}

		    	if($this->server->requiresAuthentication() and $this->kick("disconnectionScreen.notAuthenticated", false)){ //use kick to allow plugins to cancel this
			    	return;
		    	}

                $this->xboxAuthenticated = false;
		    	$this->server->getLogger()->debug($this->getName() . " is NOT logged into Xbox Live");
	    	}else{
		    	$this->server->getLogger()->debug($this->getName() . " is logged into Xbox Live");
		    	$this->xuid = $xuid;
		    	$this->xboxAuthenticated = true;
	    	}
        }else{
            $this->xboxAuthenticated = $signedByMojang;
            if(!$this->xboxAuthenticated){
		    	if($this->server->requiresAuthentication() and $this->kick("disconnectionScreen.notAuthenticated", false)){ //use kick to allow plugins to cancel this
			    	return;
		    	}

		    	$this->server->getLogger()->debug($this->getName() . " is NOT logged into Xbox Live");
	    	}else{
		    	$this->server->getLogger()->debug($this->getName() . " is logged into Xbox Live");
	    	}
        }

		$identityPublicKey = base64_decode($packet->identityPublicKey, true);

		if($identityPublicKey === false){
			//if this is invalid it should have borked VerifyLoginTask
			throw new InvalidArgumentException("We should never have reached here if the key is invalid");
		}

		if(EncryptionContext::$ENABLED){
			$this->server->getAsyncPool()->submitTask(new PrepareEncryptionTask(
				$identityPublicKey,
				function(string $encryptionKey, string $handshakeJwt, string $publicServerKey, string $serverToken) : void{
					if(!$this->isConnected()){
						return;
					}

					$pk = new ServerToClientHandshakePacket();
					$pk->publicKey = $publicServerKey;
					$pk->serverToken = $serverToken;
					$pk->jwt = $handshakeJwt;
					$this->sendDataPacket($pk, false, true); //make sure this gets sent before encryption is enabled

                    $this->awaitingEncryptionHandshake = true;

                    if($this->getProtocol() < ProtocolInfo::PROTOCOL_429){
				    	$this->cipher = EncryptionContext::cfb8($encryptionKey);
                    }else{
                        $this->cipher = EncryptionContext::fakeGCM($encryptionKey);
                    }

				    $this->server->getLogger()->debug("Enabled encryption for " . $this->username);
				}
			));
		}else{
			$this->processLogin();
		}
	}

	/**
	 * @return bool
	 */
	public function onEncryptionHandshake() : bool{
		if(!$this->awaitingEncryptionHandshake){
			return false;
		}
		$this->awaitingEncryptionHandshake = false;

		$this->server->getLogger()->debug("Encryption handshake completed for " . $this->username);

		$this->processLogin();
		return true;
	}

	/**
	 * @return ?EncryptionContext
	 */
	public function getCipher(): ?EncryptionContext{
		return $this->cipher;
	}

	protected function processLogin(){
		foreach($this->server->getLoggedInPlayers() as $p){
			if($p !== $this and ($p->iusername === $this->iusername or $this->getUniqueId()->equals($p->getUniqueId()))){
				if(!$p->kick("logged in from another location")){
					$this->close($this->getLeaveMessage(), "Logged in from another location");

					return;
				}
			}
		}

		$this->namedtag = $this->server->getOfflinePlayerData($this->username);

		$this->playedBefore = ($this->getLastPlayed() - $this->getFirstPlayed()) > 1; // microtime(true) - microtime(true) may have less than one millisecond difference
		$this->namedtag->setString("NameTag", $this->username);

		$this->gamemode = $this->namedtag->getInt("playerGameType", self::SURVIVAL) & 0x03;
		if($this->server->getForceGamemode()){
			$this->gamemode = $this->server->getGamemode();
			$this->namedtag->setInt("playerGameType", $this->gamemode);
		}

		$this->allowFlight = $this->isCreative();
		$this->keepMovement = $this->isSpectator() || $this->allowMovementCheats();

		if(($level = $this->server->getLevelByName($this->namedtag->getString("Level", "", true))) === null){
			$this->setLevel($this->server->getDefaultLevel());
			$this->namedtag->setString("Level", $this->level->getFolderName());
			$spawnLocation = $this->level->getSafeSpawn();
			$this->namedtag->setTag(new ListTag("Pos", [
				new DoubleTag("", $spawnLocation->x),
				new DoubleTag("", $spawnLocation->y),
				new DoubleTag("", $spawnLocation->z)
			]));
		}else{
			$this->setLevel($level);
		}

		$this->achievements = [];

		$achievements = $this->namedtag->getCompoundTag("Achievements") ?? [];
		/** @var ByteTag $achievement */
		foreach($achievements as $achievement){
			$this->achievements[$achievement->getName()] = $achievement->getValue() !== 0;
		}

		$this->sendPlayStatus(PlayStatusPacket::LOGIN_SUCCESS);

		$this->loggedIn = true;
		$this->server->onPlayerLogin($this);

		// 0.15.0 NOT HAVE RESOURCE PACKS, LOL
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$pk = new ResourcePacksInfoPacket();
	    	$manager = $this->server->getResourcePackManager($this->getOriginalProtocol());
	    	$pk->resourcePackEntries = $manager->getResourceStack();
	    	$pk->mustAccept = $manager->resourcePacksRequired();
	    	$pk->worldTemplateId = UUID::fromBinary(str_repeat("\x00", 16), 0);
	    	$pk->worldTemplateVersion = "";
	    	$this->dataPacket($pk);
		}else{
		    $this->completeLoginSequence();
		}
	}

	public function handleResourcePackClientResponse(ResourcePackClientResponsePacket $packet) : bool{
		if($this->resourcePacksDone){
			return false;
		}
		switch($packet->status){
			case ResourcePackClientResponsePacket::STATUS_REFUSED:
				//TODO: add lang strings for this
				$this->close("", "You must accept resource packs to join this server.", true);
				break;
			case ResourcePackClientResponsePacket::STATUS_SEND_PACKS:
				$manager = $this->server->getResourcePackManager($this->getOriginalProtocol());
				foreach($packet->packIds as $uuid){
					//dirty hack for mojang's dirty hack for versions
					$splitPos = strpos($uuid, "_");
					if($splitPos !== false){
						$uuid = substr($uuid, 0, $splitPos);
					}

					$pack = $manager->getPackById($uuid);
					if(!($pack instanceof ResourcePack)){
						//Client requested a resource pack but we don't have it available on the server
						$this->close("", "disconnectionScreen.resourcePack", true);
						$this->server->getLogger()->debug("Got a resource pack request for unknown pack with UUID " . $uuid . ", available packs: " . implode(", ", $manager->getPackIdList()));

						return false;
					}

					$pk = new ResourcePackDataInfoPacket();
					$pk->packId = $pack->getPackId();
					$pk->maxChunkSize = 1048576; //1MB
					$pk->chunkCount = (int) ceil($pack->getPackSize() / $pk->maxChunkSize);
					$pk->compressedPackSize = $pack->getPackSize();
					$pk->sha256 = $pack->getSha256();
					$this->dataPacket($pk);
				}

				break;
			case ResourcePackClientResponsePacket::STATUS_HAVE_ALL_PACKS:
				$this->haveAllPacks = true;
				$pk = new ResourcePackStackPacket();
				$manager = $this->server->getResourcePackManager($this->getOriginalProtocol());
				$pk->resourcePackStack = $manager->getResourceStack();
				//we don't force here, because it doesn't have user-facing effects
				//but it does have an annoying side-effect when true: it makes
				//the client remove its own non-server-supplied resource packs.
				$pk->mustAccept = false;
				$pk->experiments = new Experiments([], false);
				$pk->useVanillaEditorPacks = false;
				$this->dataPacket($pk);
				break;
			case ResourcePackClientResponsePacket::STATUS_COMPLETED:
				if($this->haveAllPacks){
			    	$this->resourcePacksDone = true;
			    	$this->completeLoginSequence();
				}
				break;
			default:
				return false;
		}

		return true;
	}

	protected function completeLoginSequence(){
		/** @var float[] $pos */
		$pos = $this->namedtag->getListTag("Pos")->getAllValues();
		$this->level->registerChunkLoader($this, ((int) floor($pos[0])) >> 4, ((int) floor($pos[2])) >> 4, true);
		$this->usedChunks[Level::chunkHash((((int) floor($pos[0])) >> 4), (((int) floor($pos[2])) >> 4))] = false;

		parent::__construct($this->level, $this->namedtag);
		$ev = new PlayerLoginEvent($this, "Plugin reason");
		$ev->call();
		if($ev->isCancelled()){
			$this->close($this->getLeaveMessage(), $ev->getKickMessage());

			return;
		}

		if(!$this->hasValidSpawnPosition()){
			if(($level = $this->server->getLevelByName($this->namedtag->getString("SpawnLevel", ""))) instanceof Level){
				$this->spawnPosition = new Position($this->namedtag->getInt("SpawnX"), $this->namedtag->getInt("SpawnY"), $this->namedtag->getInt("SpawnZ"), $level);
			}else{
				$this->spawnPosition = $this->level->getSafeSpawn();
			}
		}

		$spawnPosition = $this->getSpawn();

		$pk = new StartGamePacket();
		$pk->entityUniqueId = $this->id;
		$pk->entityRuntimeId = $this->id;
		$pk->playerGamemode = Player::gamemodeToNetworkId($this->gamemode, $this->getProtocol());

		$pk->playerPosition = $this->getOffsetPosition($this);

		$pk->pitch = $this->pitch;
		$pk->yaw = $this->yaw;
		$pk->seed = -1;
		$pk->spawnSettings = new SpawnSettings(SpawnSettings::BIOME_TYPE_DEFAULT, "", $this->getClientFriendlyDimension($this->level->getDimensionId())); //TODO: implement this properly
		$pk->worldGamemode = Player::gamemodeToNetworkId($this->server->getGamemode(), $this->getProtocol());
		$pk->difficulty = $this->level->getDifficulty();
		$pk->spawnX = $spawnPosition->getFloorX();
		$pk->spawnY = $spawnPosition->getFloorY();
		$pk->spawnZ = $spawnPosition->getFloorZ();
		$pk->hasAchievementsDisabled = true;
		$pk->time = $this->level->getTime();
		$pk->eduEditionOffer = 0;
		$pk->eduMode = false;
		$pk->rainLevel = 0; //TODO: implement these properly
		$pk->lightningLevel = 0;
		$pk->commandsEnabled = true;
		$pk->levelId = "";
		$pk->worldName = $this->server->getMotd();
		$pk->experiments = new Experiments([], false);
		$pk->playerMovementSettings = new PlayerMovementSettings(ServerAuthMovementMode::SERVER_AUTHORITATIVE_V3, 0, false);
		$pk->isMovementServerAuthoritative = true;
		$pk->serverSoftwareVersion = sprintf("%s %s", NAME, VERSION);
		$pk->playerActorProperties = new CompoundTag("");
		$pk->blockPaletteChecksum = 0; //we don't bother with this (0 skips verification) - the preimage is some dumb stringified NBT, not even actual NBT
		$pk->worldTemplateId = UUID::fromBinary(str_repeat("\x00", 16), 0);
		$pk->networkPermissions = new NetworkPermissions(disableClientSounds: true);
		$pk->vanillaVersion = $this->vanillaVersion;
		$this->dataPacket($pk);

		if($this->getProtocol() < ProtocolInfo::PROTOCOL_110){
			$this->sendGamemode();
		}

        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_331){
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_776){
				$palette = ItemPalette::getPalette($this->getProtocol());
				$this->sendDataPacket(ItemRegistryPacket::create($palette::getEntries()));
			}
	    	$this->sendDataPacket(new AvailableActorIdentifiersPacket());
	    	$this->sendDataPacket(BiomeDefinitionPalette::getBiomeDefinitionListPacket($this->getProtocol()));
        }

		$this->level->sendTime($this);

		$this->sendAttributes(true);
		$this->setNameTagVisible();
		$this->setNameTagAlwaysVisible();
		$this->setCanClimb();
		$this->setImmobile(); //disable pre-spawn movement

		$this->server->getLogger()->info($this->getServer()->getLanguage()->translateString("pocketmine.player.logIn", [
			TextFormat::GREEN . $this->username . TextFormat::WHITE,
			$this->ip,
			$this->port,
			$this->id,
			$this->level->getName(),
			round($this->x, 4),
			round($this->y, 4),
			round($this->z, 4),
			TextFormat::RED . $this->vanillaVersion . TextFormat::WHITE,
			TextFormat::AQUA . $this->deviceModel . TextFormat::WHITE,
			$this->deviceOS,
			$this->getProtocol(),
			$this->getOriginalProtocol(),
			TextFormat::AQUA . $this->getClientId() . TextFormat::WHITE
		]));

		if($this->isOp()){
			$this->setRemoveFormat(false);
		}

		$this->sendCommandData();
		$this->syncAbilitiesAndAdventureSettings();
		$this->sendPotionEffects($this);
		$this->sendData($this);

		$this->sendAllInventories();
		$this->inventory->sendCreativeContents();
		$this->inventory->sendHeldItem($this);
        $this->server->getCraftingManager()->requestCraftingDataPacket($this);

		$this->level->getWeather()->sendWeather($this);

		$this->server->addOnlinePlayer($this);
		$this->server->sendFullPlayerListData($this);
    }

	/**
	 * Sends a chat message as this player. If the message begins with a / (forward-slash) it will be treated
	 * as a command.
	 *
	 * @param string $message
	 *
	 * @return bool
	 */
	public function chat(string $message) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return false;
		}

		$this->doCloseInventory(false);

		//Fast length check, to make sure we don't get hung trying to explode MBs of string ...
		$maxTotalLength = $this->messageCounter * (self::MAX_CHAT_BYTE_LENGTH + 1);
		if(strlen($message) > $maxTotalLength){
			return false;
		}

		$message = TextFormat::clean($message, $this->removeFormat);
		foreach(explode("\n", $message, $this->messageCounter + 1) as $messagePart){
		    if(trim($messagePart) !== "" && strlen($messagePart) <= self::MAX_CHAT_BYTE_LENGTH && mb_strlen($messagePart, 'UTF-8') <= self::MAX_CHAT_CHAR_LENGTH && $this->messageCounter-- > 0){
				if(strpos($messagePart, './') === 0){
					$messagePart = substr($messagePart, 1);
				}

				$ev = new PlayerCommandPreprocessEvent($this, $messagePart);
				$ev->call();

				if($ev->isCancelled()){
					break;
				}

				if(strpos($ev->getMessage(), "/") === 0){
					Timings::$playerCommandTimer->startTiming();
					$this->server->dispatchCommand($ev->getPlayer(), substr($ev->getMessage(), 1));
					Timings::$playerCommandTimer->stopTiming();
				}else{
					$ev = new PlayerChatEvent($this, $ev->getMessage());
					$ev->call();
					if(!$ev->isCancelled()){
						$this->server->broadcastMessage($this->getServer()->getLanguage()->translateString($ev->getFormat(), [$ev->getPlayer()->getDisplayName(), $ev->getMessage()]), $ev->getRecipients());
					}
				}
			}
		}

		return true;
	}

    /**
     * @param Vector3 $blockVector
     * 
     * @return bool
     */
  public function removeBlock(Vector3 $blockVector) : bool{
        if($this->isSpectator()){
            return true;
        }
		$this->doCloseInventory(false);

		$item = $this->inventory->getItemInHand();
		$oldItem = clone $item;

		if($this->canInteract($blockVector->add(0.5, 0.5, 0.5), $this->isCreative() ? 13 : 7) and $this->level->useBreakOn($blockVector, $item, $this, true)){
			if($this->isSurvival()){
				if(!$item->equalsExact($oldItem)){
					$this->inventory->setItemInHand($item);
					$this->inventory->sendHeldItem($this->hasSpawned);
				}

				$this->exhaust(0.025, PlayerExhaustEvent::CAUSE_MINING);
			}
			return true;
		}

		$this->inventory->sendContents($this);
		$this->inventory->sendHeldItem($this);

		$target = $this->level->getBlock($blockVector);
		/** @var Block[] $blocks */
		$blocks = $target->getAllSides();
		$blocks[] = $target;

		$this->level->sendBlocks([$this], $blocks, UpdateBlockPacket::FLAG_ALL_PRIORITY);

		foreach($blocks as $b){
			$tile = $this->level->getTile($b);
			if($tile instanceof Spawnable){
				$tile->spawnTo($this);
			}
		}

		return true;
    }

	/**
	 * Performs a left-click (attack) action on the block.
	 *
	 * @return bool if an action took place successfully
	 */
	public function attackBlock(Vector3 $pos, int $face) : bool{
		if($pos->distanceSquared($this) > 10000){
			return false;
		}

		$target = $this->level->getBlock($pos);

		$ev = new PlayerInteractEvent($this, $this->inventory->getItemInHand(), $target, null, $face, PlayerInteractEvent::LEFT_CLICK_BLOCK);
		if($this->level->checkSpawnProtection($this, $target)){
			$ev->setCancelled();
		}

		$ev->call();

        if(!$this->isConnected()){
            return true;
        }

		if($ev->isCancelled()){
			$this->inventory->sendHeldItem($this);
			return false;
		}

		if($target->onAttack($this->inventory->getItemInHand(), $face, $this)){
			return true;
		}

		$block = $target->getSide($face);
		if($block->getId() === Block::FIRE){
			$this->level->setBlock($block, BlockFactory::get(Block::AIR));
			return true;
		}

		if(!$this->isCreative()){
			//TODO: improve this to take stuff like swimming, ladders, enchanted tools into account, fix wrong tool break time calculations for bad tools (pmmp/PocketMine-MP#211)
			$breakTime = ceil($target->getBreakTime($this->inventory->getItemInHand()) * 20);
			if($breakTime > 0){
				$this->level->broadcastLevelEvent($pos, LevelEventPacket::EVENT_BLOCK_START_BREAK, (int) (65535 / $breakTime));
			}
		}

		return true;
    }

    /**
     * @param Entity $target
     * 
     * @return bool
     */
    public function attackEntity(Entity $target) : bool{
		if(!$target->isAlive()){
			return true;
		}

		if($target instanceof ItemEntity or $target instanceof Arrow){
			$this->kick("Attempting to attack an invalid entity");
			$this->server->getLogger()->warning($this->getServer()->getLanguage()->translateString("pocketmine.player.invalidEntity", [$this->getName()]));
			return false;
		}

		$cancelled = false;

		$heldItem = $this->inventory->getItemInHand();

		if($this->isSpectator()){
			$cancelled = true;
		}elseif(!$this->canInteract($target, 8)){
			$cancelled = true;
		}elseif($target instanceof Player){
			if(!$this->server->getConfigBool("pvp")){
				$cancelled = true;
			}
		}

		$ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $heldItem->getAttackPoints());

		$meleeEnchantmentDamage = 0;
		/** @var EnchantmentInstance[] $meleeEnchantments */
		$meleeEnchantments = [];
		foreach($heldItem->getEnchantments() as $enchantment){
			$type = $enchantment->getType();
			if($type instanceof MeleeWeaponEnchantment and $type->isApplicableTo($target)){
				$meleeEnchantmentDamage += $type->getDamageBonus($enchantment->getLevel());
				$meleeEnchantments[] = $enchantment;
			}
		}
		$ev->setModifier($meleeEnchantmentDamage, EntityDamageEvent::MODIFIER_WEAPON_ENCHANTMENTS);

		if($cancelled){
			$ev->setCancelled();
		}

		if(!$this->isSprinting() and !$this->isFlying() and $this->fallDistance > 0 and !$this->hasEffect(Effect::BLINDNESS) and !$this->isUnderwater()){
			$ev->setModifier($ev->getFinalDamage() / 2, EntityDamageEvent::MODIFIER_CRITICAL);
		}

		$target->attack($ev);

		if($ev->isCancelled()){
			if($heldItem instanceof Durable and $this->isSurvival()){
				$this->inventory->sendContents($this);
			}
			return true;
		}

		if($ev->getModifier(EntityDamageEvent::MODIFIER_CRITICAL) > 0){
			$pk = new AnimatePacket();
			$pk->action = AnimatePacket::ACTION_CRITICAL_HIT;
			$pk->entityRuntimeId = $target->getId();
			$this->server->broadcastPacket($target->getViewers(), $pk);
			if($target instanceof Player){
				$target->dataPacket($pk);
			}
		}

		foreach($meleeEnchantments as $enchantment){
			$type = $enchantment->getType();
			assert($type instanceof MeleeWeaponEnchantment);
			$type->onPostAttack($this, $target, $enchantment->getLevel());
		}

		if($this->isAlive()){
			//reactive damage like thorns might cause us to be killed by attacking another mob, which
			//would mean we'd already have dropped the inventory by the time we reached here
			if($heldItem->onAttackEntity($target) and $this->isSurvival()){ //always fire the hook, even if we are survival
				$this->inventory->setItemInHand($heldItem);
			}

			$this->exhaust(0.3, PlayerExhaustEvent::CAUSE_ATTACK);
		}

		return true;
    }

	public function handleCommandStep(CommandStepPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

		$commandText = $packet->command;
		if($packet->inputJson !== null){
			if(is_countable($packet->inputJson)){
				if(count($packet->inputJson) > self::MAX_INPUT_JSON){
					throw new InvalidArgumentException("Too many inputJson in CommandStepPacket");
				}
			}
			foreach($packet->inputJson as $arg){ //command ordering will be an issue
				if(!is_object($arg)) //anti bot
			    	$commandText .= " " . $arg;
			}
		}

        $this->chat("/" . $commandText);

		return true;
	}

	public function handleMovePlayer(MovePlayerPacket $packet) : bool{
		return $this->updateNextPosition($packet->position, $packet->yaw, $packet->headYaw, $packet->pitch);
	}

    public function handlePlayerAuthInput(PlayerAuthInputPacket $packet) : bool{
        $rawPos = $packet->getPosition();
        $rawYaw = $packet->getYaw();
        $rawPitch = $packet->getPitch();

        $hasMoved =
            $this->lastPlayerAuthInputPosition === null ||
            !$this->lastPlayerAuthInputPosition->equals($rawPos) ||
            $rawYaw !== $this->lastPlayerAuthInputYaw ||
            $rawPitch !== $this->lastPlayerAuthInputPitch;

        if($hasMoved){
            $this->lastPlayerAuthInputPosition = $rawPos;
            $this->lastPlayerAuthInputYaw = $rawYaw;
            $this->lastPlayerAuthInputPitch = $rawPitch;

            $this->updateNextPosition($rawPos, $rawYaw, $rawYaw, $rawPitch);

            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_649 && $this->isRiding()){
                $entity = $this->getRidingEntity();
                if($entity !== null){
                    $vehicle = $packet->getVehicleInfo();
                    if($vehicle !== null && $vehicle->getPredictedVehicleActorUniqueId() === $entity->getId()){
                        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_662){
                            $yaw = fmod($vehicle->getVehicleRotationZ(), 360);
                        }else{
                            $yaw = fmod($rawYaw, 360);
                        }

                        $entity->setClientPositionAndRotation($rawPos, $yaw, 0, 3, true);
                    }
                }
            }
        }

        $inputFlags = $packet->getInputFlags();
        if($inputFlags !== $this->lastPlayerAuthInputFlags){
            $this->lastPlayerAuthInputFlags = $inputFlags;

            $sneaking = $this->resolveOnOffInputFlags($inputFlags, PlayerAuthInputFlags::START_SNEAKING, PlayerAuthInputFlags::STOP_SNEAKING);
            $sprinting = $this->resolveOnOffInputFlags($inputFlags, PlayerAuthInputFlags::START_SPRINTING, PlayerAuthInputFlags::STOP_SPRINTING);
            $swimming = $this->resolveOnOffInputFlags($inputFlags, PlayerAuthInputFlags::START_SWIMMING, PlayerAuthInputFlags::STOP_SWIMMING);
            $gliding = $this->resolveOnOffInputFlags($inputFlags, PlayerAuthInputFlags::START_GLIDING, PlayerAuthInputFlags::STOP_GLIDING);
            $flying = $this->resolveOnOffInputFlags($inputFlags, PlayerAuthInputFlags::START_FLYING, PlayerAuthInputFlags::STOP_FLYING);
            $mismatch =
                ($sneaking !== null && !$this->toggleSneak($sneaking)) |
                ($sprinting !== null && !$this->toggleSprint($sprinting)) |
                ($swimming !== null && !$this->toggleSwim($swimming)) |
                ($gliding !== null && !$this->toggleGlide($gliding)) |
                ($flying !== null && !$this->toggleFlight($flying));
            if((bool) $mismatch){
                $this->sendData([$this]);
            }

            if($packet->hasFlag(PlayerAuthInputFlags::START_JUMPING)){
                $this->jump();
            }
            if($packet->hasFlag(PlayerAuthInputFlags::MISSED_SWING)){
                $this->missSwing();
            }
        }

        $packetHandled = true;

        $blockActions = $packet->getBlockActions();
        if($blockActions !== null){
            if(count($blockActions) > 50){
                $this->server->getLogger()->debug("Too many block actions in PlayerAuthInputPacket from " . $this->getName());
                return false;
            }
            foreach($blockActions as $k => $blockAction){
                $actionHandled = false;
                if($blockAction instanceof PlayerBlockActionStopBreak){
                    $actionHandled = $this->handlePlayerActionFromData($blockAction->getActionType(), new Vector3(0, 0, 0), Vector3::SIDE_DOWN);
                }elseif($blockAction instanceof PlayerBlockActionWithBlockInfo){
                    $actionHandled = $this->handlePlayerActionFromData($blockAction->getActionType(), new Vector3($blockAction->getX(), $blockAction->getY(), $blockAction->getZ()), $blockAction->getFace());
                }

                if(!$actionHandled){
                    $packetHandled = false;
                    $this->server->getLogger()->debug("Unhandled player block action at offset $k in PlayerAuthInputPacket from " . $this->getName());
                }
            }
        }

        $useItemTransaction = $packet->getItemInteractionData();
        if($useItemTransaction !== null){
            if(count($useItemTransaction->getTransactionData()->getActions()) > 50){
                $this->server->getLogger()->debug("Too many actions in item use transaction from " . $this->getName());
                return false;
            }

            $this->setCurrentItemStackRequestId($useItemTransaction->getRequestId());
            if(!$this->handleUseItemTransaction($useItemTransaction->getTransactionData())){
                $packetHandled = false;
                $this->server->getLogger()->debug("Unhandled transaction in PlayerAuthInputPacket (type " . $useItemTransaction->getTransactionData()->getActionType() . ") from " . $this->getName());
            }

            $this->setCurrentItemStackRequestId(null);
        }

        //TODO: ItemStack`s

        if(!$packetHandled){
            $this->getInventory()->sendContents($this);
        }

        return $packetHandled;
	}

	public function updateNextPosition(Vector3 $position, float $yaw, float $headYaw, float $pitch) : bool{
		foreach([$position->x, $position->y, $position->z, $yaw, $headYaw, $pitch] as $float){
			if(is_infinite($float) || is_nan($float)){
				$this->server->getLogger()->debug("Invalid movement from " . $this->getName() . ", contains NAN/INF components");
				return false;
			}
		}

		$newPos = $position->round(4)->subtract(0, $this->baseOffset, 0);
		if($this->forceMoveSync !== null and $newPos->distanceSquared($this->forceMoveSync) > 1){  //Tolerate up to 1 block to avoid problems with client-sided physics when spawning in blocks
			$this->sendPosition($this, null, null, MovePlayerPacket::MODE_RESET);
			$this->server->getLogger()->debug("Got outdated pre-teleport movement from " . $this->getName() . ", received " . $newPos . ", expected " . $this->asVector3());
			//Still getting movements from before teleport, ignore them
		}elseif((!$this->isAlive() or !$this->spawned) and $newPos->distanceSquared($this) > 0.01){
			$this->sendPosition($this, null, null, MovePlayerPacket::MODE_RESET);
			$this->server->getLogger()->debug("Reverted movement of " . $this->getName() . " due to not alive or not spawned, received " . $newPos . ", locked at " . $this->asVector3());
		}else{
			// Once we get a movement within a reasonable distance, treat it as a teleport ACK and remove position lock
			$this->forceMoveSync = null;

			$yaw = fmod($yaw, 360);
			$pitch = fmod($pitch, 360);

			if($yaw < 0){
				$yaw += 360;
			}

			$this->setRotation($yaw, $pitch);
			$this->handleMovement($newPos);
		}

		return true;
	}

    private function resolveOnOffInputFlags(int $inputFlags, int $startFlag, int $stopFlag) : ?bool{
        $enabled = ($inputFlags & (1 << $startFlag)) !== 0;
        $disabled = ($inputFlags & (1 << $stopFlag)) !== 0;
        if($enabled !== $disabled){
            return $enabled;
        }
        //neither flag was set, or both were set
        return null;
    }

    /**
     * Performs actions associated with the attack action (left-click) without a target entity.
     * Under normal circumstances, this will just play the no-damage attack sound and the arm-swing animation.
     */
    public function missSwing() : void{
        $ev = new PlayerMissSwingEvent($this);
        $ev->call();
        if(!$ev->isCancelled()){
            $pk = new LevelSoundEventPacket();
            $pk->sound = LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE;
            $pk->position = $this;
            $pk->entityType = "minecraft:player";
            $pk->extraData = -1;
            $pk->isBabyMob = false;
            $pk->disableRelativeVolume = false;
            $this->server->broadcastPacket($this->getViewers(), $pk);

            $pk = new ActorEventPacket();
            $pk->entityRuntimeId = $this->id;
            $pk->event = ActorEventPacket::ARM_SWING;
            $pk->data = 0;
            $this->server->broadcastPacket($this->getViewers(), $pk);
        }
    }

	public function handleLevelSoundEvent(LevelSoundEventPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

		if(
			$packet->sound === LevelSoundEventPacket::SOUND_UNDEFINED or
			$packet->sound === LevelSoundEventPacket::SOUND_NOTE
		){
			return false;
		}

		//TODO: add events so plugins can change this
		$this->getLevelNonNull()->broadcastPacketToViewers($this, $packet);
		return true;
	}

	public function handleEntityEvent(ActorEventPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

		$this->doCloseInventory(false);

		switch($packet->event){
			case ActorEventPacket::PLAYER_ADD_XP_LEVELS:
				if($this->getProtocol() < ProtocolInfo::PROTOCOL_130){
					if($this->currentWindowType === WindowTypes::ENCHANTMENT){
						$inventory = $this->findEnchantInventory();
						$inventory?->onResolve($this, abs($packet->data));
					}elseif($this->currentWindowType === WindowTypes::ANVIL){
						$inventory = $this->findAnvilInventory();
						$inventory?->onResolve($this, abs($packet->data));
					}
				}

				break;
			case ActorEventPacket::USE_ITEM: //Eating
			    if($this->getProtocol() < ProtocolInfo::PROTOCOL_130){
			    	$slot = $this->inventory->getItemInHand();

			    	if($slot instanceof Consumable){
				    	$ev = new PlayerItemConsumeEvent($this, $slot);
				    	if($this->hasItemCooldown($slot)){
					    	$ev->setCancelled();
				    	}
				    	$ev->call();

				    	if($ev->isCancelled() or !$this->consumeObject($slot)){
				    		$this->inventory->sendContents($this);
					    	return true;
				    	}

				    	$this->resetItemCooldown($slot);

				    	if($this->isSurvival()){
				    		$slot->pop();
					    	$this->inventory->setItemInHand($slot);
					    	$this->inventory->addItem($slot->getResidue());
					    	$this->inventory->sendHeldItem($this);
					    	$this->inventory->sendHeldItem($this->hasSpawned);
				    	}

                        $this->setUsingItem(false);

				    	return true;
			    	}
				}

				break;
			case ActorEventPacket::EATING_ITEM: // Eating particles
				if($packet->data === 0){
					return false;
				}

				if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
					$id = $packet->data >> 16;
					$palette = ItemPalette::getPalette($this->getProtocol());
					[$id, ] = $palette::getLegacyFromRuntimeId($id, 0);
				}elseif($this->getProtocol() >= ProtocolInfo::PROTOCOL_386){
					$id = $packet->data >> 16;
				}else{
					$id = $packet->data;
				}

				$item = $this->inventory->getItemInHand();
				if($item->getId() !== $id){
					return ($item->getItemProtocol($this->getProtocol()) ?? $item)->getId() === $id;
				}

				$viewers = $this->getViewers();
				foreach($viewers as $viewer){
					$id = ($item->getItemProtocol($viewer->getProtocol()) ?? $item)->getId();

					$pk = new ActorEventPacket();
					$pk->entityRuntimeId = $this->getId();
					$pk->event = ActorEventPacket::EATING_ITEM;

					if($viewer->getProtocol() >= ProtocolInfo::PROTOCOL_419){
						$palette = ItemPalette::getPalette($viewer->getProtocol());
						$idMeta = $palette::getRuntimeFromLegacyIdQuiet($id, 0);
						if($idMeta === null){
							continue;
						}

						[$netId, ] = $idMeta;
						$pk->data = $netId << 16;
					}elseif($viewer->getProtocol() >= ProtocolInfo::PROTOCOL_386){
						$pk->data = $id << 16;
					}else{
						$pk->data = $id;
					}

					$viewer->dataPacket($pk);
				}

				$this->dataPacket($packet);

				break;
			default:
				return false;
		}

		return true;
	}

	/**
	 * Don't expect much from this handler. Most of it is roughly hacked and duct-taped together.
	 *
	 * @param InventoryTransactionPacket $packet
	 *
	 * @return bool
	 */
	public function handleInventoryTransaction(InventoryTransactionPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return false;
		}

		if(count($packet->trData->getActions()) > 50){
		    throw new UnexpectedValueException("Too many actions in inventory transaction");
		}

		$this->setCurrentItemStackRequestId($packet->requestId);

		$result = false;
		if($packet->trData instanceof NormalTransactionData){
			$result = $this->handleNormalTransaction($packet->trData, $packet->requestId);
		}elseif($packet->trData instanceof MismatchTransactionData){
			$result = $this->handleMismatchTransaction($packet->trData);
		}elseif($packet->trData instanceof UseItemTransactionData){
			$result = $this->handleUseItemTransaction($packet->trData);
		}elseif($packet->trData instanceof UseItemOnEntityTransactionData){
			$result = $this->handleUseItemOnEntityTransaction($packet->trData);
		}elseif($packet->trData instanceof ReleaseItemTransactionData){
			$result = $this->handleReleaseItemTransaction($packet->trData);
		}

		$this->setCurrentItemStackRequestId(null);

		if(!$result){
			$this->inventory->sendContents($this);
		}

		return $result;
	}

	public function handleNormalTransaction(NormalTransactionData $data, int $requestId) : bool{
		/** @var InventoryAction[] $actions */
		$actions = [];
		$isCraftingPart = false;
		$isFinalCraftingPart = false;
		foreach($data->getActions() as $networkInventoryAction){
            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_385){
                if($this->getProtocol() < ProtocolInfo::PROTOCOL_401){
		        	if(
			        	$networkInventoryAction->sourceType === NetworkInventoryAction::SOURCE_CONTAINER and
			        	$networkInventoryAction->windowId === ContainerIds::UI and
			        	$networkInventoryAction->inventorySlot === 50 and
			        	!$networkInventoryAction->oldItem->equalsExact($networkInventoryAction->newItem)
		        	){
			        	$isCraftingPart = true;
			        	if(!$networkInventoryAction->oldItem->isNull() and $networkInventoryAction->newItem->isNull()){
				        	$isFinalCraftingPart = true;
			        	}
		        	}elseif(
			        	$networkInventoryAction->sourceType === NetworkInventoryAction::SOURCE_TODO and (
				        	$networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_RESULT or
				        	$networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_USE_INGREDIENT
			        	)
		        	){
			        	$isCraftingPart = true;
		        	}
                }else{
                    if(
			        	$networkInventoryAction->sourceType === NetworkInventoryAction::SOURCE_TODO and (
				        	$networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_RESULT or
				        	$networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_USE_INGREDIENT
			        	)
		        	){
			        	$isCraftingPart = true;
			        	if($networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_RESULT){
				        	$isFinalCraftingPart = true;
			        	}
			    	}
                }
	    	}else{
                if((
                    $networkInventoryAction->sourceType === NetworkInventoryAction::SOURCE_TODO ||
                    $networkInventoryAction->sourceType === NetworkInventoryAction::SOURCE_UNTRACKED_INTERACTION_UI
                ) && (
                    $networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_RESULT ||
                    $networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_USE_INGREDIENT
                )){
                    $isCraftingPart = true;
                    if($networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_RESULT){
                        $isFinalCraftingPart = true;
                    }
                }
            }

			try{
				$action = $networkInventoryAction->createInventoryAction($this);
				if($action !== null){
					$actions[] = $action;
				}
			}catch(UnexpectedValueException $e){
				$this->server->getLogger()->debug("Unhandled inventory action from " . $this->getName() . ": " . $e->getMessage());
				$this->sendAllInventories();
				return false;
			}
		}

		if($isCraftingPart){
			if($this->craftingTransaction === null){
				$this->craftingTransaction = new CraftingTransaction($this, $actions);
			}else{
				foreach($actions as $action){
					$this->craftingTransaction->addAction($action);
				}
			}

			if($isFinalCraftingPart){
				//we get the actions for this in several packets, so we need to wait until we have all the pieces before
				//trying to execute it

				$ret = true;
				try{
					$this->craftingTransaction->execute();
				}catch(TransactionValidationException $e){
					$this->server->getLogger()->debug("Failed to execute crafting transaction for " . $this->getName() . ": " . $e->getMessage());
					$ret = false;
				}

				$this->craftingTransaction = null;
				return $ret;
			}

			return true;
		}elseif($this->craftingTransaction !== null){
			$this->server->getLogger()->debug("Got unexpected normal inventory action with incomplete crafting transaction from " . $this->getName() . ", refusing to execute crafting");
			$this->craftingTransaction = null;
		}

		$this->setUsingItem(false);
		$this->setCurrentItemStackRequestId($requestId);

		$transaction = new InventoryTransaction($this, $actions);

		try{
			$transaction->execute();
		}catch(TransactionValidationException $e){
			$this->server->getLogger()->debug("Failed to execute inventory transaction from " . $this->getName() . ": " . $e->getMessage());
			$this->server->getLogger()->debug("Actions: " . json_encode($data->getActions()));

			return false;
		}finally{
			$this->setCurrentItemStackRequestId(null);
		}

		//TODO: fix achievement for getting iron from furnace

		return true;
	}

	public function handleMismatchTransaction(MismatchTransactionData $data) : bool{
		if(count($data->getActions()) > 0){
			$this->server->getLogger()->debug("Expected 0 actions for mismatch, got " . count($data->getActions()) . ", " . json_encode($data->getActions()));
		}
		$this->setUsingItem(false);
		$this->sendAllInventories();

		return true;
	}

	public function handleUseItemTransaction(UseItemTransactionData $data) : bool{
		$blockVector = $data->getBlockPos();
		$face = $data->getFace();

		switch($data->getActionType()){
            case UseItemTransactionData::ACTION_CLICK_BLOCK:
                //TODO: start hack for client spam bug
                $spamBug = ($this->lastRightClickData !== null and
                    microtime(true) - $this->lastRightClickTime < 0.1 and //100ms
                    $this->lastRightClickData->getPlayerPos()->distanceSquared($data->getPlayerPos()) < 0.00001 and
                    $this->lastRightClickData->getBlockPos()->equals($data->getBlockPos()) and
				    $this->lastRightClickData->getClickPos()->distanceSquared($data->getClickPos()) < 0.00001 //signature spam bug has 0 distance, but allow some error
                );
                //get rid of continued spam if the player clicks and holds right-click
                $this->lastRightClickData = $data;
                $this->lastRightClickTime = microtime(true);
                if ($spamBug) {
                    return true;
                }
                //TODO: end hack for client spam bug

                $this->setUsingItem(false);

                if (!$this->canInteract($blockVector->add(0.5, 0.5, 0.5), 13) or $this->isSpectator()) {
                } elseif ($this->isCreative()) {
                    $item = $this->inventory->getItemInHand();
                    if ($this->level->useItemOn($blockVector, $item, $face, $data->getClickPos(), $this, true)) {
                        return true;
                    }
                } elseif (!$this->inventory->getItemInHand()->equals($data->getItemInHand())) {
                    $this->inventory->sendHeldItem($this);
                } else {
                    $item = $this->inventory->getItemInHand();
                    $oldItem = clone $item;
                    if ($this->level->useItemOn($blockVector, $item, $face, $data->getClickPos(), $this, true)) {
                        if (!$item->equalsExact($oldItem)) {
                            $this->inventory->setItemInHand($item);
                            $this->inventory->sendHeldItem($this->hasSpawned);
                        }

                        return true;
                    }
                }

                if(!$this->isConnected()){
                    return true;
                }

                $this->inventory->sendHeldItem($this);

				if($blockVector->distanceSquared($this) > 10000){
					return true;
				}

				$target = $this->level->getBlock($blockVector);
				$block = $target->getSide($face);

				/** @var Block[] $blocks */
				$blocks = array_merge($target->getAllSides(), $block->getAllSides()); //getAllSides() on each of these will include $target and $block because they are next to each other

				$this->level->sendBlocks([$this], $blocks, UpdateBlockPacket::FLAG_ALL_PRIORITY);

				return true;
			case UseItemTransactionData::ACTION_BREAK_BLOCK:
				return $this->removeBlock($blockVector);
			case UseItemTransactionData::ACTION_CLICK_AIR:
				if($this->getProtocol() >= ProtocolInfo::PROTOCOL_386 && $this->isUsingItem()){
					$slot = $this->inventory->getItemInHand();
				    if($slot instanceof Consumable){
					   	$ev = new PlayerItemConsumeEvent($this, $slot);
						if($this->hasItemCooldown($slot)){
							$ev->setCancelled();
						}
					    $ev->call();
					    if($ev->isCancelled() or !$this->consumeObject($slot)){
						    $this->inventory->sendContents($this);
							return true;
						}
					   	$this->resetItemCooldown($slot);
						if($this->isSurvival()){
						    $slot->pop();
						    $this->inventory->setItemInHand($slot);
							$this->inventory->addItem($slot->getResidue());
					    }
						$this->setUsingItem(false);
					}
				}

				$directionVector = $this->getDirectionVector();

				if($this->isCreative()){
					$item = $this->inventory->getItemInHand();
				}elseif(!$this->inventory->getItemInHand()->equals($data->getItemInHand())){
					$this->inventory->sendHeldItem($this);
					return true;
				}else{
					$item = $this->inventory->getItemInHand();
				}

				$ev = new PlayerInteractEvent($this, $item, null, $directionVector, $face, PlayerInteractEvent::RIGHT_CLICK_AIR);
				if($this->hasItemCooldown($item)){
					$ev->setCancelled();
				}

				$ev->call();
				if($ev->isCancelled()){
					$this->inventory->sendHeldItem($this);
					return true;
				}

				if($item->onClickAir($this, $directionVector)){
					$this->resetItemCooldown($item);
					if($this->isSurvival()){
						$this->inventory->setItemInHand($item);
					}
				}

				$this->setUsingItem(true);

				return true;
			default:
				//unknown
				break;
		}

		return false;
	}

	public function handleUseItemOnEntityTransaction(UseItemOnEntityTransactionData $data) : bool{
		$target = $this->level->getEntity($data->getEntityRuntimeId());
		if($target === null){
			return false;
		}

		switch($data->getActionType()){
			case UseItemOnEntityTransactionData::ACTION_INTERACT:
	            if($this->isSpectator()){
	                return true;
	            }
				if(!$target->isAlive()){
					return true;
				}
				$ev = new PlayerInteractEntityEvent($this, $target, $item = $this->inventory->getItemInHand(), $data->getClickPos());
				$ev->call();

				if(!$ev->isCancelled()){
					$oldItem = clone $item;
					if(!$target->onFirstInteract($this, $ev->getItem(), $ev->getClickPosition())){
						if($target instanceof Living){
							if($this->isCreative()){
								$item = $oldItem;
							}

							if($item->onInteractWithEntity($this, $target)){
								if(!$item->equalsExact($oldItem) and !$this->isCreative()){
									$this->inventory->setItemInHand($item);
								}
							}
						}
					}elseif(!$item->equalsExact($oldItem)){
						$this->inventory->setItemInHand($ev->getItem());
					}
				}
				return true;
			case UseItemOnEntityTransactionData::ACTION_ATTACK:
				return $this->attackEntity($target);
			default:
				break; //unknown
		}

		return false;
	}

	public function handleReleaseItemTransaction(ReleaseItemTransactionData $data) : bool{
	    if($this->isSpectator()){
	        return true;
	    }

		try{
			switch($data->getActionType()){
				case ReleaseItemTransactionData::ACTION_RELEASE:
					if($this->isUsingItem()){
						$item = $this->inventory->getItemInHand();
						if($this->hasItemCooldown($item)){
							$this->inventory->sendContents($this);
							return false;
						}
						if($item->onReleaseUsing($this)){
							$this->resetItemCooldown($item);
							$this->inventory->setItemInHand($item);
						}
					}else{
						break;
					}

					return true;
				case ReleaseItemTransactionData::ACTION_CONSUME:
				    if($this->getProtocol() < ProtocolInfo::PROTOCOL_386){
				    	$slot = $this->inventory->getItemInHand();

					    if($slot instanceof Consumable){
						    $ev = new PlayerItemConsumeEvent($this, $slot);
							if($this->hasItemCooldown($slot)){
						    	$ev->setCancelled();
					    	}
					    	$ev->call();

						    if($ev->isCancelled() or !$this->consumeObject($slot)){
						    	$this->inventory->sendContents($this);
						    	return true;
					    	}

							$this->resetItemCooldown($slot);

							if($this->isSurvival()){
						    	$slot->pop();
						    	$this->inventory->setItemInHand($slot);
						    	$this->inventory->addItem($slot->getResidue());
					    	}

							return true;
						}
					}

					break;
				default:
					break;
			}
		}finally{
			$this->setUsingItem(false);
		}

		return false;
	}

	public function handleUseItem(UseItemPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

		$blockVector = new Vector3($packet->x, $packet->y, $packet->z);

		if($packet->face >= 0 and $packet->face <= 5){ //Use Block, place
			$this->setUsingItem(false);

			if(!$this->canInteract($blockVector->add(0.5, 0.5, 0.5), 13) or $this->isSpectator()){
			}elseif($this->isCreative()){
				$item = $this->inventory->getItemInHand();
				if($this->level->useItemOn($blockVector, $item, $packet->face, $packet->clickPos, $this, true)){
					return true;
				}
			}elseif(!$this->inventory->getItemInHand()->equals($packet->item)){
				$this->inventory->sendHeldItem($this);
			}else{
				$item = $this->inventory->getItemInHand();
				$oldItem = clone $item;
				if($this->level->useItemOn($blockVector, $item, $packet->face, $packet->clickPos, $this, true)){
					if(!$item->equalsExact($oldItem)){
						$this->inventory->setItemInHand($item);
						$this->inventory->sendHeldItem($this->hasSpawned);
					}
					return true;
				}
			}

			if(!$this->isConnected()){
				return true;
			}

			$this->inventory->sendHeldItem($this);

			if($blockVector->distanceSquared($this) > 10000){
				return true;
			}
			$target = $this->level->getBlock($blockVector);
			$block = $target->getSide($packet->face);

			$this->level->sendBlocks([$this], [$target, $block], UpdateBlockPacket::FLAG_ALL_PRIORITY);
			return true;
		}elseif($packet->face === -1 or ($this->getProtocol() < ProtocolInfo::PROTOCOL_90 && $packet->face === 0xff)){
			$directionVector = $this->getDirectionVector();

			if($this->isCreative()){
				$item = $this->inventory->getItemInHand();
			}elseif(!$this->inventory->getItemInHand()->equals($packet->item)){
				$this->inventory->sendHeldItem($this);
				return true;
			}else{
				$item = $this->inventory->getItemInHand();
			}

			$ev = new PlayerInteractEvent($this, $item, null, $directionVector, $packet->face, PlayerInteractEvent::RIGHT_CLICK_AIR);
			if($this->hasItemCooldown($item)){
				$ev->setCancelled();
			}

			$this->server->getPluginManager()->callEvent($ev);
			if($ev->isCancelled()){
				$this->inventory->sendHeldItem($this);
				return true;
			}

			if($item->onClickAir($this, $directionVector)){
			    $this->resetItemCooldown($item);
			    if($this->isSurvival()){
			    	$this->inventory->setItemInHand($item);
			    }
			}

			$this->setUsingItem(true);
		}

		return true;
	}

	public function handleCraftingEvent(CraftingEventPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

        $recipes = $this->server->getCraftingManager()->matchRecipeByOutputs($packet->output, $this->getCraftingProtocol());
        $this->transactionQueue->execute();
        $inventory = $this->transactionQueue->getInventory();
        if(count($packet->input) > 0){
            foreach($packet->input as $item){
                if($item->isNull()){
                    continue;
                }elseif($item->getDamage() === 0x7fff){
                    $item = ItemFactory::get($item->getId(), -1, $item->getCount(), $item->getNamedTag());
                }

                if($this->getInventory()->contains($item)){
                    $inventory->addItem($item);
                    $this->getInventory()->removeItem($item);
                }else{
					$this->inventory->sendContents($this);
                    return false;
                }
            }
        }

        $actions = [];
        $currentRecipe = null;
        foreach($recipes as $recipe){
            $contents = $inventory->getContents();
            $actions  = [];
            $canCraft = true;

            foreach($recipe->getIngredientList() as $ingredient){
                $count = max(1, $ingredient->getCount());
                $damage = !$ingredient->hasAnyDamageValue();
                $namedtag = $ingredient->hasNamedTag();

                foreach($contents as $slot => $item){
                    if($ingredient->equals($item, $damage, $namedtag)){
                        $reduce = min($count, $item->getCount());
                        $count -= $reduce;
                        $newItem = (clone $item)->setCount($item->getCount() - $reduce);

                        if($newItem->getCount() === 0){
                            $newItem = ItemFactory::get(Item::AIR, 0);
                        }

                        $actions[] = new SlotChangeAction($inventory, $slot, $item, $newItem);
                        $contents[$slot] = $newItem;
                        if($count === 0){
                            break;
                        }elseif($count < 0){
                            throw new InvalidArgumentException("Wait... this is illegally $count $reduce");
                        }
                    }
                }

                if($count > 0){
                    $canCraft = false;
                    break;
                }
            }

            if($canCraft === true){
                $currentRecipe = $recipe;
                break;
            }
        }

        if($currentRecipe === null){
            $this->inventory->sendContents($this);
            return true;
        }

        $baseInventory = new class([], 36) extends BaseInventory{
        	public function getName() : string{
	        	return "null";
        	}

        	public function getDefaultSize() : int{
	        	return 36;
        	}
        };
        foreach($packet->output as $slot => $item){
            $actions[] = new SlotChangeAction($baseInventory, $slot, $baseInventory->getItem($slot), $item);
        }

        $this->setCraftingGrid($inventory);
        if($this->craftingTransaction === null){
            $this->craftingTransaction = new CraftingTransaction($this, $actions);
        }

        try{
            $this->craftingTransaction->execute();
        }catch(TransactionValidationException $exception){
            $this->server->getLogger()->debug("Failed to execute crafting transaction: " . $exception->getMessage());
            return false;
        }finally{
            $this->craftingTransaction = null;
        }

        if(count($packet->input) === 0){
            $items = $inventory->addItem(...$baseInventory->getContents());
        }else{
            $items = $this->getInventory()->addItem(...$baseInventory->getContents());
        }

        if(count($items) > 0){
            foreach($items as $item){
                $this->dropItem($item);
            }
        }

        return true;
	}

	public function handleMobEquipment(MobEquipmentPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

        if($packet->windowId === ContainerIds::OFFHAND){
            if($this->getProtocol() < ProtocolInfo::PROTOCOL_130){
                $this->transactionQueue->addTransaction(new Transaction($this->getOffHandInventory(), 0, $packet->item));
            }
            return true;
        }

        if($this->getProtocol() < ProtocolInfo::PROTOCOL_130){
            //Get the index of the slot in the actual inventory
            $packet->inventorySlot -= $this->inventory->getHotbarSize();
            if($packet->inventorySlot < 0 || $packet->inventorySlot >= $this->inventory->getSize()){
				//Mapping was not in range of the inventory, set it to -1
				//This happens if the client selected a blank slot (sends 255)
                $packet->inventorySlot = -1;
            }

            $this->inventory->equipItem($packet->hotbarSlot, $packet->inventorySlot);
        }else{
            $this->inventory->equipItem($packet->hotbarSlot);
        }

        $this->setUsingItem(false);

        return true;
	}

	public function handleInteract(InteractPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}
		if($packet->action === InteractPacket::ACTION_MOUSEOVER and $packet->target === 0){
			//TODO HACK: silence useless spam (MCPE 1.8)
			//this packet is EXPECTED to only be sent when interacting with an entity, but due to some messy Mojang
			//hacks, it also sends it when changing the held item now, which causes us to think the inventory was closed
			//when it wasn't.
			return true;
		}

		$this->doCloseInventory(false);

		$target = $this->level->getEntity($packet->target);
		if($target === null){
			return false;
		}

		switch($packet->action){
			case InteractPacket::ACTION_LEAVE_VEHICLE:
				if($this->ridingEid === $packet->target){
					$this->dismountEntity();
				}
				break;
		    case InteractPacket::ACTION_OPEN_INVENTORY:
				if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392 && $target === $this && !array_key_exists($windowId = self::HARDCODED_INVENTORY_WINDOW_ID, $this->openHardcodedWindows)){
					//TODO: HACK! this restores 1.14ish behaviour, but this should be able to be listened to and
					//controlled by plugins. However, the player is always a subscriber to their own inventory so it
					//doesn't integrate well with the regular container system right now.
					$this->openHardcodedWindows[$windowId] = true;
				    $pk = new ContainerOpenPacket();
				    $pk->windowId = self::HARDCODED_INVENTORY_WINDOW_ID;
				    $pk->type = WindowTypes::INVENTORY;
				    $pk->x = $pk->y = $pk->z = 0;
				    $pk->entityUniqueId = $this->getId();
					$this->setCurrentWindowType($pk->type);
				    $this->sendDataPacket($pk);
				    break;
				}elseif($target instanceof InventoryHolder){
					if(!($target instanceof AbstractHorse and !$target->isTamed())){
						$this->setCurrentWindowType(WindowTypes::HORSE);
						$this->addWindow($target->getInventory());
						return true;
					}
				}
				return false;
			case InteractPacket::ACTION_RIGHT_CLICK:
				if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
					return true;
				}
	            if($this->isSpectator()){
	                return true;
	            }
				if(!$target->isAlive()){
					return true;
				}
				$ev = new PlayerInteractEntityEvent($this, $target, $item = $this->inventory->getItemInHand(), $target->asVector3());
				$ev->call();

				if(!$ev->isCancelled()){
					$oldItem = clone $item;
					if(!$target->onFirstInteract($this, $ev->getItem(), $ev->getClickPosition())){
						if($target instanceof Living){
							if($this->isCreative()){
								$item = $oldItem;
							}

							if($item->onInteractWithEntity($this, $target)){
								if(!$item->equalsExact($oldItem) and !$this->isCreative()){
									$this->inventory->setItemInHand($item);
								}
							}
						}
					}elseif(!$item->equalsExact($oldItem)){
						$this->inventory->setItemInHand($ev->getItem());
					}
				}
				break;
		    case InteractPacket::ACTION_LEFT_CLICK:
		        if($this->getProtocol() < ProtocolInfo::PROTOCOL_130){
		            return $this->attackEntity($target);
		        }

		        break;
			case InteractPacket::ACTION_MOUSEOVER:
	            if($this->isSpectator()){
	                return true;
	            }

				if(!$target->isAlive()){
					return true;
				}

                if($target->distance($this) <= 4){
                    $item = $this->inventory->getItemInHand();
                    $isSneaking = $this->isSneaking();

                    match(true){
                        $item->getId() === Item::NAMETAG && $target instanceof Entity && !$target instanceof Human && $item->hasCustomName() =>
							$this->setInteractiveTag(new TextContainer("action.interact.name")),

                        $target instanceof AbstractHorse => match(true){
                            //$target->isBreedingItem($item) => $this->setInteractiveTag(new TextContainer("action.interact.feed")),
                            $isSneaking && $target->isTamed() => $this->setInteractiveTag(new TextContainer("action.interact.opencontainer")),
                            !$isSneaking => $this->setInteractiveTag(new TextContainer("action.interact.ride.horse")),
                            default => $this->removeInteractiveTag()
                        },

                        $target instanceof Creeper && $item->getId() === Item::FLINT_AND_STEEL && !$isSneaking =>
						    $this->setInteractiveTag(new TextContainer("action.interact.creeper")),

                        $target instanceof Cow && !$target->hasMilkingCooldown() && $item->getId() === Item::BUCKET && $item->getDamage() === 0 =>
                            $this->setInteractiveTag(new TextContainer("action.interact.milk")),

                        $target instanceof Sheep && !$target->isSheared() && $item->getId() === Item::SHEARS =>
						    $this->setInteractiveTag(new TextContainer("action.interact.shear")),

                        default => $this->removeInteractiveTag()
                    };
                }else{
                    $this->removeInteractiveTag();
                }

				break;
			default:
				$this->server->getLogger()->debug("Unhandled/unknown interaction type " . $packet->action . "received from " . $this->getName());

				return false;
		}

		return true;
	}

	public function handleEmote(EmotePacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

		if($packet->entityRuntimeId !== $this->id){
			return false;
		}

		$pk = new EmotePacket();
		$pk->entityRuntimeId = $this->id;
		$pk->emoteId = $packet->emoteId;
		$pk->emoteLengthTicks = $packet->emoteLengthTicks;
		$pk->xboxUserId = "";
		$pk->platformChatId = "";
		$pk->flags = EmotePacket::FLAG_SERVER | EmotePacket::FLAG_MUTE_ANNOUNCEMENT;

        $bedrockPlayers = [];
        foreach($this->hasSpawned as $player){
            if($player->getProtocol() >= ProtocolInfo::PROTOCOL_385){
                $bedrockPlayers[] = $player;
            }
        }

		$this->server->broadcastPacket($bedrockPlayers, $pk);

		return true;
	}

	public function handleBlockPickRequest(BlockPickRequestPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

		$block = $this->level->getBlockAt($packet->blockX, $packet->blockY, $packet->blockZ);
		if($block instanceof UnknownBlock){
			return true;
		}

		$item = $block->getPickedItem();
		if($packet->addUserData){
			$tile = $this->getLevelNonNull()->getTile($block);
			if($tile instanceof Tile){
				$nbt = $tile->getCleanedNBT();
				if($nbt instanceof CompoundTag){
					$item->setCustomBlockData($nbt);
					$item->setLore(["+(DATA)"]);
				}
			}
		}

		$ev = new PlayerBlockPickEvent($this, $block, $item);
		if(!$this->isCreative(true)){
			$this->server->getLogger()->debug("Got block-pick request from " . $this->getName() . " when not in creative mode (gamemode " . $this->getGamemode() . ")");
			$ev->setCancelled();
		}

		$ev->call();
		if(!$ev->isCancelled()){
			$this->inventory->setItemInHand($ev->getResultItem());
		}

		return true;

	}

	public function handlePlayerAction(PlayerActionPacket $packet) : bool{
		return $this->handlePlayerActionFromData($packet->action, new Vector3($packet->x, $packet->y, $packet->z), $packet->face);
	}

	public function handlePlayerActionFromData(int $action, Vector3 $pos, int $face) : bool{
        if(!$this->spawned or (!$this->isAlive() && $action !== PlayerActionPacket::ACTION_DIMENSION_CHANGE_REQUEST && $action !== PlayerActionPacket::ACTION_RESPAWN)){
            return false;
        }

		switch($action){
		    case PlayerActionPacket::ACTION_RELEASE_ITEM:
	            if($this->isSpectator()){
	                return true;
	            }

		        if($this->getProtocol() < ProtocolInfo::PROTOCOL_130){
		            try{
				    	if($this->isUsingItem()){
					    	$item = $this->inventory->getItemInHand();
					    	if($this->hasItemCooldown($item)){
						    	$this->inventory->sendContents($this);
						    	return false;
					    	}
					    	if($item->onReleaseUsing($this)){
						    	$this->resetItemCooldown($item);
						    	$this->inventory->setItemInHand($item);
					    	}
				    	}else{
					    	break;
				    	}
					}finally{
					    $this->setUsingItem(false);
					}
		        }

		        break;
			case PlayerActionPacket::ACTION_START_BREAK:
			    $this->attackBlock($pos, $face);
				break;
			case PlayerActionPacket::ACTION_ABORT_BREAK:
			case PlayerActionPacket::ACTION_STOP_BREAK:
				$this->level->broadcastLevelEvent($pos, LevelEventPacket::EVENT_BLOCK_STOP_BREAK);
				break;
			case PlayerActionPacket::ACTION_START_SLEEPING:
				//unused
				break;
			case PlayerActionPacket::ACTION_STOP_SLEEPING:
				$this->stopSleep();
				break;
			case PlayerActionPacket::ACTION_DIMENSION_CHANGE_REQUEST:
			case PlayerActionPacket::ACTION_RESPAWN:
				if($this->isAlive()){
					break;
				}

				$this->respawn();
				break;
			case PlayerActionPacket::ACTION_JUMP:
				$this->jump();
				return true;
			case PlayerActionPacket::ACTION_START_SPRINT:
				$this->toggleSprint(true);
				return true;
			case PlayerActionPacket::ACTION_STOP_SPRINT:
				$this->toggleSprint(false);
				return true;
			case PlayerActionPacket::ACTION_START_SNEAK:
				$this->toggleSneak(true);
				return true;
			case PlayerActionPacket::ACTION_STOP_SNEAK:
				$this->toggleSneak(false);
				return true;
			case PlayerActionPacket::ACTION_START_GLIDE:
				if(!($this->armorInventory->getChestplate() instanceof Elytra)){
					$this->server->getLogger()->debug("Player " . $this->username . " tried to start glide without elytra");
					$this->sendData($this);
					$this->armorInventory->sendContents($this);
					return false;
				}
				$this->toggleGlide(true);
				break;
			case PlayerActionPacket::ACTION_STOP_GLIDE:
				$this->toggleGlide(false);
				break;
			case PlayerActionPacket::ACTION_CONTINUE_BREAK:
				$block = $this->level->getBlock($pos);
				$data = $block->getId() | ($block->getDamage() << 8);
				if(($this->fxTicker++ % $this->fxTickInterval) === 0){
			    	$this->level->broadcastLevelSoundEvent($pos, LevelSoundEventPacket::SOUND_HIT, $block->getId());
				}

				//TODO: destroy-progress level event
				break;
			case PlayerActionPacket::ACTION_START_SWIMMING:
				if(!$this->isSwimming()){
					$this->toggleSwim(true);
				}
				break;
			case PlayerActionPacket::ACTION_STOP_SWIMMING:
				if($this->isSwimming()){ // for spam issue
					$this->toggleSwim(false);
				}
				break;
			case PlayerActionPacket::ACTION_INTERACT_BLOCK: //TODO: ignored (for now)
				break;
			case PlayerActionPacket::ACTION_CREATIVE_PLAYER_DESTROY_BLOCK:
				//TODO: do we need to handle this?
				break;
			case PlayerActionPacket::ACTION_START_ITEM_USE_ON:
			case PlayerActionPacket::ACTION_STOP_ITEM_USE_ON:
				//TODO: this has no obvious use and seems only used for analytics in vanilla - ignore it
				break;
			case PlayerActionPacket::ACTION_START_USING_ITEM:
				return true;
			case PlayerActionPacket::ACTION_HANDLED_TELEPORT:
        	case PlayerActionPacket::ACTION_MISSED_SWING:
        	case PlayerActionPacket::ACTION_START_CRAWLING:
        	case PlayerActionPacket::ACTION_STOP_CRAWLING:
        	case PlayerActionPacket::ACTION_START_FLYING:
        	case PlayerActionPacket::ACTION_STOP_FLYING:
        	case PlayerActionPacket::ACTION_ACK_ACTOR_DATA:
			    break;
			default:
				$this->server->getLogger()->debug("Unhandled/unknown player action type " . $action . " from " . $this->getName());
				return false;
		}

		$this->setUsingItem(false);

		return true;
	}

	public function toggleSprint(bool $sprint) : void{
		$ev = new PlayerToggleSprintEvent($this, $sprint);
		$ev->call();
		if($ev->isCancelled()){
			$this->sendData($this);
		}else{
			$this->setSprinting($sprint);
		}
	}

	public function toggleSneak(bool $sneak) : void{
		$ev = new PlayerToggleSneakEvent($this, $sneak);
		$ev->call();
		if($ev->isCancelled()){
			$this->sendData($this);
		}else{
			$this->setSneaking($sneak);
		}
	}

	public function toggleGlide(bool $glide) : void{
		$ev = new PlayerToggleGlideEvent($this, $glide);
		$ev->call();
		if($ev->isCancelled()){
			$this->sendData($this);
		}else{
			$this->setGliding($glide);
		}
	}

	public function toggleSwim(bool $swimming) : void{
		$ev = new PlayerToggleSwimEvent($this, $swimming);
		$ev->call();
		if($ev->isCancelled()){
			$this->sendData($this);
		}else{
			$this->setSwimming($swimming);
		}
	}

	public function toggleFlight(bool $flying) : void{
	    $ev = new PlayerToggleFlightEvent($this, $flying);
		$ev->call();
    	if($ev->isCancelled()){
	    	$this->syncAbilities();
		}else{ //don't use setFlying() here, to avoid feedback loops
		 	$this->flying = $ev->isFlying();
		    $this->resetFallDistance();
	    }
	}

	public function handleAnimate(AnimatePacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

		$ev = new PlayerAnimationEvent($this, $packet->action);
		$ev->call();
		if($ev->isCancelled()){
			return true;
		}

		$riding = $this->getRidingEntity();
		if($riding instanceof Boat){
			if($packet->action === AnimatePacket::ACTION_ROW_RIGHT){
				$riding->setPaddleTimeRight($packet->rowingTime);
			}elseif($packet->action === AnimatePacket::ACTION_ROW_LEFT){
				$riding->setPaddleTimeLeft($packet->rowingTime);
			}
		}

		$pk = new AnimatePacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->action = $ev->getAnimationType();
		$this->server->broadcastPacket($this->getViewers(), $pk);

		return true;
	}

	public function handleRespawn(RespawnPacket $packet) : bool{
		if(!$this->isAlive() && $packet->respawnState === RespawnPacket::CLIENT_READY_TO_SPAWN){
			$this->sendRespawnPacket($this, RespawnPacket::READY_TO_SPAWN);
			return true;
		}

		return false;
	}

	public function handleDropItem(DropItemPacket $packet) : bool{
		if($this->spawned === false or !$this->isAlive() or $this->isSpectator()){
			return true;
		}

		if($packet->item->getId() === Item::AIR){
			// Windows 10 Edition drops the contents of the crafting grid on container close - including air.
			return true;
		}

        $this->transactionQueue->addTransaction(new DropItemTransaction($packet->item));

		return true;
	}

	/**
	 * Drops an item on the ground in front of the player. Returns if the item drop was successful.
	 *
	 * @param Item $item
	 *
	 * @return bool if the item was dropped or if the item was null
	 */
	public function dropItem(Item $item) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return false;
		}

		if($item->isNull()){
			$this->server->getLogger()->debug($this->getName() . " attempted to drop a null item (" . $item . ")");
			return true;
		}

		$motion = $this->getDirectionVector()->multiply(0.4);

		$this->level->dropItem($this->add(0, 1.3, 0), $item, $motion, 40);

		return true;
	}

	/** @var int|null */
	private $closingWindowId = null;

	/** @internal */
	public function getClosingWindowId() : ?int{ return $this->closingWindowId; }

	/** @var int */
	private $currentWindowType = WindowTypes::CONTAINER;

	/** @internal */
	public function getCurrentWindowType() : ?int{ return $this->currentWindowType; }

	/** @internal */
	public function setCurrentWindowType(int $windowType) : void{ $this->currentWindowType = $windowType; }

	public function handleContainerClose(ContainerClosePacket $packet) : bool{
		if(!$this->spawned or ($this->getProtocol() < ProtocolInfo::PROTOCOL_392 && $packet->windowId === 0)){
			return true;
		}

        if($this->isCreative(true) && $this->getProtocol() < ProtocolInfo::PROTOCOL_130){
            $this->transactionQueue->getInventory()->clearAll();
        }
		$this->doCloseInventory();

        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392){
			if(array_key_exists($packet->windowId, $this->openHardcodedWindows)){
				unset($this->openHardcodedWindows[$packet->windowId]);
		        $pk = new ContainerClosePacket();
		        $pk->windowId = $packet->windowId;
				$pk->windowType = $this->getCurrentWindowType();
		        $this->sendDataPacket($pk);
		        return true;
	    	}
		}
		if(isset($this->windowIndex[$packet->windowId])){
		    $this->closingWindowId = $packet->windowId;
			$this->removeWindow($this->windowIndex[$packet->windowId]);
			$this->closingWindowId = null;
			return true;
		}else{
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392){
		    	/*
		    	 * TODO: HACK!
		    	 * If we told the client to remove a window on our own (e.g. a plugin called removeWindow()), our
		    	 * first ContainerClose tricks the client into behaving as if it itself asked for the window to be closed.
		    	 * This means that it will send us a ContainerClose of its own, which we must respond to the same way as if
		    	 * the client closed the window by itself.
		    	 * If we don't, the client will not be able to open any new windows.
		    	 */
		    	$pk = new ContainerClosePacket();
		    	$pk->windowId = $packet->windowId;
				$pk->windowType = $this->getCurrentWindowType();
		    	$pk->server = false;
		    	$this->sendDataPacket($pk);
		    	return true;
			}else{
				if($packet->windowId === 255){
		        	//Closed a fake window
		        	return true;
				}
			}
		}

		return false;
	}

	public function handleContainerSetSlot(ContainerSetSlotPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

        if($packet->slot < 0){
            return false;
        }

        switch($packet->windowid){
            case ContainerIds::INVENTORY:
                if($packet->slot >= $this->inventory->getSize()){
                    return false;
                }

                $transaction = new Transaction($this->inventory, $packet->slot, $packet->item);
                break;
            case ContainerIds::ARMOR:
                if($packet->slot >= 4){
                    return false;
                }

                $transaction = new Transaction($this->armorInventory, $packet->slot, $packet->item);
                break;
            case ContainerIds::HOTBAR:
                //Hotbar link update
				//hotbarSlot 0-8, slot 9-44
				$this->inventory->setHotbarSlotIndex($packet->hotbarSlot, $packet->slot - 9);
                return true;
            default:
                $inventory = $this->getWindow($packet->windowid);
                if(!($inventory instanceof Inventory)){
                    return false;
                }

                if($packet->slot >= $inventory->getSize()){
                    return false;
                }

                $transaction = new Transaction($inventory, $packet->slot, $packet->item);
                break;
        }

        $this->transactionQueue->addTransaction($transaction);

		if($this->currentWindowType === WindowTypes::ANVIL){
			$inventory = $this->findAnvilInventory();
			$inventory?->handleChange($this, $this->getInventory()->getItem($packet->slot), $packet->item, $packet->slot);
		}elseif($this->currentWindowType === WindowTypes::ENCHANTMENT){
			$inventory = $this->findEnchantInventory();
			$inventory?->handleChange($this, $this->getInventory()->getItem($packet->slot), $packet->item, $packet->slot);
		}

		return true;
	}

	public function handleAdventureSettings(AdventureSettingsPacket $packet) : bool{
	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_526){
	        return true; //no longer used, but the client still sends it for flight changes
	    }

	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130 && $packet->entityUniqueId !== $this->getId()){
		    return false; //TODO
	    }

	    $handled = false;

		$isFlying = $packet->getFlag(AdventureSettingsPacket::FLYING);
		if($isFlying and !$this->allowFlight){
			$this->kick($this->server->getLanguage()->translateString("kick.reason.cheat", ["%ability.flight"]));
			return true;
		}elseif($isFlying !== $this->isFlying()){
			$this->toggleFlight($isFlying);
			$handled = true;
		}

		if($packet->getFlag(AdventureSettingsPacket::NO_CLIP) and !$this->allowMovementCheats and !$this->isSpectator()){
			$this->kick($this->server->getLanguage()->translateString("kick.reason.cheat", ["%ability.noclip"]));
			return true;
		}

		//TODO: check other changes

		return $handled;
	}

	public function handleRequestAbility(RequestAbilityPacket $packet) : bool{
		if(!$this->spawned){
			return true;
		}

		if($packet->getAbilityId() === RequestAbilityPacket::ABILITY_FLYING){
			$isFlying = $packet->getAbilityValue();
			if(!is_bool($isFlying)){
				throw new InvalidArgumentException("Flying ability should always have a bool value");
			}
			if($isFlying !== $this->isFlying()){
				$this->toggleFlight($isFlying);
			}

			return true;
		}

		return false;
	}

	public function handleBlockEntityData(BlockActorDataPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

		$this->doCloseInventory(false);

		$pos = new Vector3($packet->x, $packet->y, $packet->z);
		if($pos->distanceSquared($this) > 10000 or $this->level->checkSpawnProtection($this, $pos)){
			return true;
		}

		$t = $this->level->getTile($pos);
		if($t instanceof Spawnable){
			$nbt = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? new NetworkLittleEndianNBTStream() : new LittleEndianNBTStream();
			$_ = 0;
			$compound = $nbt->read($packet->namedtag, false, $_, 512);

			if(!($compound instanceof CompoundTag)){
				throw new InvalidArgumentException("Expected " . CompoundTag::class . " in block entity NBT, got " . (is_object($compound) ? get_class($compound) : gettype($compound)));
			}
			if(!$t->updateCompoundTag($compound, $this)){
				$t->spawnTo($this);
			}
		}

		return true;
	}

	public function handleSetPlayerGameType(SetPlayerGameTypePacket $packet) : bool{
		$gamemode = self::protocolGameModeToCore($packet->gamemode);
		if($gamemode !== $this->gamemode){
			//Set this back to default. TODO: handle this properly
			$this->sendGamemode();
			$this->syncAbilitiesAndAdventureSettings(); //TODO: we might be able to do this with the abilities packet alone
		}
		return true;
	}

	public function handleItemFrameDropItem(ItemFrameDropItemPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

		$tile = $this->level->getTileAt($packet->x, $packet->y, $packet->z);
		if($tile instanceof ItemFrame){
			$ev = new PlayerInteractEvent($this, $this->inventory->getItemInHand(), $tile->getBlock(), null, 5 - $tile->getBlock()->getDamage(), PlayerInteractEvent::LEFT_CLICK_BLOCK);
			if($this->isSpectator() or $this->level->checkSpawnProtection($this, $tile)){
				$ev->setCancelled();
			}

			$ev->call();
			if($ev->isCancelled()){
				$tile->spawnTo($this);
				return true;
			}

			$ev = new ItemFrameDropItemEvent($this, $tile->getBlock(), $tile, $tile->getItem());
			$ev->call();
			if($ev->isCancelled()){
				$tile->spawnTo($this);
				return true;
			}

			$item = $tile->getItem();
			$item->setOnItemFrame(false);
			if(lcg_value() <= $tile->getItemDropChance()){
				$this->level->dropItem($tile->getBlock(), $item);
			}
			$tile->setItem(null);
			$tile->setItemRotation(0);
		}

		return true;
	}

	public function handleResourcePackChunkRequest(ResourcePackChunkRequestPacket $packet) : bool{
		if($this->resourcePacksDone){
			return false;
		}
		$manager = $this->server->getResourcePackManager($this->getOriginalProtocol());
		$pack = $manager->getPackById($packet->packId);
		if(!($pack instanceof ResourcePack)){
			$this->close("", "disconnectionScreen.resourcePack", true);
			$this->server->getLogger()->debug("Got a resource pack chunk request for unknown pack with UUID " . $packet->packId . ", available packs: " . implode(", ", $manager->getPackIdList()));

			return false;
		}

		$pk = new ResourcePackChunkDataPacket();
		$pk->packId = $pack->getPackId();
		$pk->chunkIndex = $packet->chunkIndex;
		$pk->data = $pack->getPackChunk(1048576 * $packet->chunkIndex, 1048576);
		$pk->progress = (1048576 * $packet->chunkIndex);
		$this->dataPacket($pk);
		return true;
	}

	public function handleBookEdit(BookEditPacket $packet) : bool{
		if(!$this->spawned or !$this->isAlive()){
			return true;
		}

		/** @var WritableBook $oldBook */
		if($this->getProtocol() === ProtocolInfo::PROTOCOL_201 || $this->getProtocol() < ProtocolInfo::PROTOCOL_200){
		    $packet->inventorySlot -= $this->inventory->getHotbarSize();
		}

		$oldBook = $this->inventory->getItem($packet->inventorySlot);
		if($oldBook->getId() !== Item::WRITABLE_BOOK){
			return false;
		}

		$newBook = clone $oldBook;
		$modifiedPages = [];

		switch($packet->type){
			case BookEditPacket::TYPE_REPLACE_PAGE:
				$newBook->setPageText($packet->pageNumber, $packet->text);
				$modifiedPages[] = $packet->pageNumber;
				break;
			case BookEditPacket::TYPE_ADD_PAGE:
				if(!$newBook->pageExists($packet->pageNumber)){
					//this may only come before a page which already exists
					//TODO: the client can send insert-before actions on trailing client-side pages which cause odd behaviour on the server
					return false;
				}
				$newBook->insertPage($packet->pageNumber, $packet->text);
				$modifiedPages[] = $packet->pageNumber;
				break;
			case BookEditPacket::TYPE_DELETE_PAGE:
				if(!$newBook->pageExists($packet->pageNumber)){
					return false;
				}
				$newBook->deletePage($packet->pageNumber);
				$modifiedPages[] = $packet->pageNumber;
				break;
			case BookEditPacket::TYPE_SWAP_PAGES:
				if(!$newBook->pageExists($packet->pageNumber) or !$newBook->pageExists($packet->secondaryPageNumber)){
					//the client will create pages on its own without telling us until it tries to switch them
					$newBook->addPage(max($packet->pageNumber, $packet->secondaryPageNumber));
				}
				$newBook->swapPages($packet->pageNumber, $packet->secondaryPageNumber);
				$modifiedPages = [$packet->pageNumber, $packet->secondaryPageNumber];
				break;
			case BookEditPacket::TYPE_SIGN_BOOK:
				/** @var WrittenBook $newBook */
				$newBook = Item::get(Item::WRITTEN_BOOK, 0, 1, $newBook->getNamedTag());
				$newBook->setAuthor($packet->author);
				$newBook->setTitle($packet->title);
				$newBook->setGeneration(WrittenBook::GENERATION_ORIGINAL);
				break;
			default:
				return false;
		}

		$event = new PlayerEditBookEvent($this, $oldBook, $newBook, $packet->type, $modifiedPages);
		$event->call();
		if($event->isCancelled()){
			return true;
		}

		$this->getInventory()->setItem($packet->inventorySlot, $event->getNewBook());

		return true;
	}

	/**
	 * Called when a packet is received from the client. This method will call DataPacketReceiveEvent.
	 *
	 * @param DataPacket $packet
	 */
	public function handleDataPacket(DataPacket $packet){
		if($this->sessionAdapter !== null){
			$this->sessionAdapter->handleDataPacket($packet);
		}
	}

	/**
	 * Batch a Data packet into the channel list to send at the end of the tick
	 *
	 * @param DataPacket $packet
	 *
	 * @return bool
	 */
	public function batchDataPacket(DataPacket $packet) : bool{
		if(!$this->isConnected()){
			return false;
		}

		$timings = Timings::getSendDataPacketTimings($packet);
		$timings->startTiming();
		$ev = new DataPacketSendEvent($this, $packet);
		$ev->call();
		if($ev->isCancelled()){
			$timings->stopTiming();
			return false;
		}

		$this->batchedPackets[] = clone $packet;
		$timings->stopTiming();
		return true;
	}

	/**
	 * @param DataPacket $packet
	 * @param bool       $needACK
	 * @param bool       $immediate
	 *
	 * @return bool|int
	 */
	public function sendDataPacket(DataPacket $packet, bool $needACK = false, bool $immediate = false){
		if(!$this->isConnected()){
			return false;
		}

		//Basic safety restriction. TODO: improve this
		if(!$this->loggedIn and !$packet->canBeSentBeforeLogin()){
			throw new InvalidArgumentException("Attempted to send " . get_class($packet) . " to " . $this->getName() . " too early");
		}

		$timings = Timings::getSendDataPacketTimings($packet);
		$timings->startTiming();
		try{
			$packet = clone $packet;
		    $packet->setProtocol($this->protocol);

			$ev = new DataPacketSendEvent($this, $packet);
			$ev->call();
			if($ev->isCancelled()){
				return false;
			}

			$identifier = $this->interface->putPacket($this, $packet, $needACK, $immediate);

			if($needACK and $identifier !== null){
				$this->needACK[$identifier] = false;
				return $identifier;
			}

			return true;
		}finally{
			$timings->stopTiming();
		}
	}

	/**
	 * @param DataPacket $packet
	 * @param bool       $needACK
	 *
	 * @return bool|int
	 */
	public function dataPacket(DataPacket $packet, bool $needACK = false){
		return $this->sendDataPacket($packet, $needACK, false);
	}

	/**
	 * @param DataPacket $packet
	 * @param bool       $needACK
	 *
	 * @return bool|int
	 */
	public function directDataPacket(DataPacket $packet, bool $needACK = false){
		return $this->sendDataPacket($packet, $needACK, true);
	}

	/**
	 * Transfers a player to another server.
	 *
	 * @param string $address The IP address or hostname of the destination server
	 * @param int    $port The destination port, defaults to 19132
	 * @param string $message Message to show in the console when closing the player
	 *
	 * @return bool if transfer was successful.
	 */
	public function transfer(string $address, int $port = 19132, string $message = "transfer") : bool{
		$ev = new PlayerTransferEvent($this, $address, $port, $message);
		$ev->call();
		if(!$ev->isCancelled()){
			$pk = new TransferPacket();
			$pk->address = $ev->getAddress();
			$pk->port = $ev->getPort();
			$this->directDataPacket($pk);
			$this->close("", $ev->getMessage(), false);

			return true;
		}

		return false;
	}

	/**
	 * Kicks a player from the server
	 *
	 * @param string               $reason
	 * @param bool                 $isAdmin
	 * @param TextContainer|string $quitMessage
	 *
	 * @return bool
	 */
	public function kick(string $reason = "", bool $isAdmin = true, $quitMessage = null) : bool{
		$ev = new PlayerKickEvent($this, $reason, $quitMessage ?? $this->getLeaveMessage());
		$ev->call();
		if(!$ev->isCancelled()){
			$reason = $ev->getReason();
			$message = $reason;
			if($isAdmin){
				if(!$this->isBanned()){
					$message = "Kicked by admin." . ($reason !== "" ? " Reason: " . $reason : "");
				}
			}else{
				if($reason === ""){
					$message = "disconnectionScreen.noReason";
				}
			}
			$this->close($ev->getQuitMessage(), $message);

			return true;
		}

		return false;
	}

	/**
	 * Adds a title text to the user's screen, with an optional subtitle.
	 *
	 * @param string $title
	 * @param string $subtitle
	 * @param int    $fadeIn Duration in ticks for fade-in. If -1 is given, client-sided defaults will be used.
	 * @param int    $stay Duration in ticks to stay on screen for
	 * @param int    $fadeOut Duration in ticks for fade-out.
	 */
	public function addTitle(string $title, string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1){
		$this->setTitleDuration($fadeIn, $stay, $fadeOut);
		if($subtitle !== ""){
			$this->addSubTitle($subtitle);
		}
		$this->sendTitleText($title, SetTitlePacket::TYPE_SET_TITLE);
	}

	/**
	 * Sets the subtitle message, without sending a title.
	 *
	 * @param string $subtitle
	 */
	public function addSubTitle(string $subtitle){
		$this->sendTitleText($subtitle, SetTitlePacket::TYPE_SET_SUBTITLE);
	}

	/**
	 * Adds small text to the user's screen.
	 *
	 * @param string $message
	 */
	public function addActionBarMessage(string $message){
		$this->sendTitleText($message, SetTitlePacket::TYPE_SET_ACTIONBAR_MESSAGE);
	}

	/**
	 * Removes the title from the client's screen.
	 */
	public function removeTitles(){
		$pk = new SetTitlePacket();
		$pk->type = SetTitlePacket::TYPE_CLEAR_TITLE;
		$this->dataPacket($pk);
	}

	/**
	 * Resets the title duration settings to defaults and removes any existing titles.
	 */
	public function resetTitles(){
		$pk = new SetTitlePacket();
		$pk->type = SetTitlePacket::TYPE_RESET_TITLE;
		$this->dataPacket($pk);
	}

	/**
	 * Sets the title duration.
	 *
	 * @param int $fadeIn Title fade-in time in ticks.
	 * @param int $stay Title stay time in ticks.
	 * @param int $fadeOut Title fade-out time in ticks.
	 */
	public function setTitleDuration(int $fadeIn, int $stay, int $fadeOut){
		if($fadeIn >= 0 and $stay >= 0 and $fadeOut >= 0){
			$pk = new SetTitlePacket();
			$pk->type = SetTitlePacket::TYPE_SET_ANIMATION_TIMES;
			$pk->fadeInTime = $fadeIn;
			$pk->stayTime = $stay;
			$pk->fadeOutTime = $fadeOut;
			$this->dataPacket($pk);
		}
	}

	/**
	 * Internal function used for sending titles.
	 *
	 * @param string $title
	 * @param int    $type
	 */
	protected function sendTitleText(string $title, int $type){
		$pk = new SetTitlePacket();
		$pk->type = $type;
		$pk->text = $title;
		$this->dataPacket($pk);
	}

	/**
	 * Sends a direct chat message to a player
	 *
	 * @param TextContainer|string $message
	 */
	public function sendMessage($message){
		if($message instanceof TextContainer){
			if($message instanceof TranslationContainer){
				$this->sendTranslation($message->getText(), $message->getParameters());
				return;
			}
			$message = $message->getText();
		}

		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_RAW;
		$pk->message = $this->server->getLanguage()->translateString($message);
		$this->dataPacket($pk);
	}

	/**
	 * @param string   $message
	 * @param string[] $parameters
	 */
	public function sendTranslation(string $message, array $parameters = []){
		$pk = new TextPacket();
		if(!$this->server->isLanguageForced()){
			$pk->type = TextPacket::TYPE_TRANSLATION;
			$pk->needsTranslation = true;
			$pk->message = $this->server->getLanguage()->translateString($message, $parameters, "pocketmine.");
			foreach($parameters as $i => $p){
				$parameters[$i] = $this->server->getLanguage()->translateString($p, [], "pocketmine.");
			}
			$pk->parameters = $parameters;
		}else{
			$pk->type = TextPacket::TYPE_RAW;
			$pk->message = $this->server->getLanguage()->translateString($message, $parameters);
		}
		$this->dataPacket($pk);
	}

	/**
	 * Sends a popup message to the player
	 *
	 * TODO: add translation type popups
	 *
	 * @param string $message
	 * @param string $subtitle @deprecated
	 */
	public function sendPopup(string $message, string $subtitle = ""){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_POPUP;
	    if($this->getProtocol() < ProtocolInfo::PROTOCOL_134){
	        $pk->message = $subtitle;
	        $pk->sourceName = $message;
	    }else{
	    	$pk->message = $message;
	    }
		$this->dataPacket($pk);
	}

	public function sendTip(string $message){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_TIP;
		$pk->message = $message;
		$pk->sourceName = "";
		$this->dataPacket($pk);
	}

	/**
	 * @param string $sender
	 * @param string $message
	 */
	public function sendWhisper(string $sender, string $message){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_WHISPER;
		$pk->sourceName = $sender;
		$pk->message = $message;
		$this->dataPacket($pk);
	}

	/**
	 * Sends a Form to the player, or queue to send it if a form is already open.
	 *
	 * @param Form $form
	 */
	public function sendForm(Form $form) : void{
		$id = $this->formIdCounter++;
		$pk = new ModalFormRequestPacket();
		$pk->formId = $id;
		$pk->formData = json_encode($form);
		if($pk->formData === false){
			throw new InvalidArgumentException("Failed to encode form JSON: " . json_last_error_msg());
		}
		if($this->dataPacket($pk)){
			$this->forms[$id] = $form;
		}
	}

	/**
	 * @param int   $formId
	 * @param mixed $responseData
	 *
	 * @return bool
	 */
	public function onFormSubmit(int $formId, $responseData) : bool{
		if(!isset($this->forms[$formId])){
			$this->server->getLogger()->debug("Got unexpected response for form $formId");
			return false;
		}

		try{
			$this->forms[$formId]->handleResponse($this, $responseData);
		}catch(FormValidationException $e){
			$this->server->getLogger()->critical("Failed to validate form " . get_class($this->forms[$formId]) . ": " . $e->getMessage());
			$this->server->getLogger()->logException($e);
		}finally{
			unset($this->forms[$formId]);
		}

		return true;
	}

	/**
	 * Note for plugin developers: use kick() with the isAdmin
	 * flag set to kick without the "Kicked by admin" part instead of this method.
	 *
	 * @param TextContainer|string $message Message to be broadcasted
	 * @param string               $reason Reason showed in console
	 * @param bool                 $notify
	 */
	final public function close($message = "", string $reason = "generic reason", bool $notify = true) : void{
		if($this->isConnected() and !$this->closed){
			if($notify and strlen($reason) > 0){
				$pk = new DisconnectPacket();
				$pk->reason = 0;
				$pk->message = $reason;
				if($this->getProtocol() >= ProtocolInfo::PROTOCOL_553 && !$this->isCompressionEnabled()){
				    $pk->setProtocol($this->protocol);

	            	$packet = new BatchPacket();
	            	$packet->setProtocol($this->protocol);
	            	$packet->addPacket($pk);
	            	$packet->compressionEnabled = false;

	            	$this->directDataPacket($packet);
				}else{
				    $this->directDataPacket($pk);
				}
			}
			$this->interface->close($this, $notify ? $reason : "");
			$this->sessionAdapter = null;

			PermissionManager::getInstance()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
			PermissionManager::getInstance()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);

			$this->stopSleep();

			if($this->spawned){
				$ev = new PlayerQuitEvent($this, $message, $reason);
				$ev->call();
				if($ev->getQuitMessage() != ""){
					$this->server->broadcastMessage($ev->getQuitMessage());
				}

				$this->save();
			}

			if($this->isValid()) {
                foreach ($this->usedChunks as $index => $d) {
                    Level::getXZ($index, $chunkX, $chunkZ);
                    $this->level->unregisterChunkLoader($this, $chunkX, $chunkZ);
                    foreach ($this->level->getChunkEntities($chunkX, $chunkZ) as $entity) {
                        $entity->despawnFrom($this);
                    }
                    unset($this->usedChunks[$index]);
                }
            }
			$this->usedChunks = [];
			$this->loadQueue = [];

			if($this->loggedIn){
				$this->server->onPlayerLogout($this);
				foreach($this->server->getOnlinePlayers() as $player){
					if(!$player->canSee($this)){
						$player->showPlayer($this);
					}
				}
				$this->hiddenPlayers = [];
			}

			$this->removeAllWindows(true);
			$this->windows = [];
			$this->windowIndex = [];
			$this->cursorInventory = null;
			$this->craftingGrid = null;

			if($this->constructed){
				parent::close();
			}
			$this->spawned = false;

			if($this->loggedIn){
				$this->loggedIn = false;
				$this->server->removeOnlinePlayer($this);
			}

			$this->server->removePlayer($this);

			$this->server->getLogger()->info($this->getServer()->getLanguage()->translateString("pocketmine.player.logOut", [
				TextFormat::GREEN . $this->getName() . TextFormat::WHITE,
				$this->ip,
				$this->port,
				$this->getServer()->getLanguage()->translateString($reason)
			]));

			$this->spawnPosition = null;

			if($this->perm !== null){
				$this->perm->clearPermissions();
				$this->perm = null;
			}
		}
	}

	public function __debugInfo(){
		return [];
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

	public function setCanSaveWithChunk(bool $value) : void{
		throw new BadMethodCallException("Players can't be saved with chunks");
	}

	/**
	 * Handles player data saving
	 *
	 * @throws InvalidStateException if the player is closed
	 */
	public function save(){
		if($this->closed){
			throw new InvalidStateException("Tried to save closed player");
		}

		parent::saveNBT();

		if($this->isValid()){
			$this->namedtag->setString("Level", $this->level->getFolderName());
		}

		if($this->hasValidSpawnPosition()){
			$this->namedtag->setString("SpawnLevel", $this->spawnPosition->getLevel()->getFolderName());
			$this->namedtag->setInt("SpawnX", $this->spawnPosition->getFloorX());
			$this->namedtag->setInt("SpawnY", $this->spawnPosition->getFloorY());
			$this->namedtag->setInt("SpawnZ", $this->spawnPosition->getFloorZ());

			if(!$this->isAlive()){
				//hack for respawn after quit
				$this->namedtag->setTag(new ListTag("Pos", [
					new DoubleTag("", $this->spawnPosition->x),
					new DoubleTag("", $this->spawnPosition->y),
					new DoubleTag("", $this->spawnPosition->z)
				]));
			}
		}

		$achievements = new CompoundTag("Achievements");
		foreach($this->achievements as $achievement => $status){
			$achievements->setByte($achievement, $status ? 1 : 0);
		}
		$this->namedtag->setTag($achievements);

		$this->namedtag->setInt("playerGameType", $this->gamemode);
		$this->namedtag->setLong("lastPlayed", (int) floor(microtime(true) * 1000));

		if($this->username != "" and $this->namedtag instanceof CompoundTag){
			$this->server->saveOfflinePlayerData($this->username, $this->namedtag);
		}
	}

	public function kill() : void{
		if(!$this->spawned){
			return;
		}

		parent::kill();

		$this->sendRespawnPacket($this->getSpawn());
	}

	protected function onDeath() : void{
		//Crafting grid must always be evacuated even if keep-inventory is true. This dumps the contents into the
		//main inventory and drops the rest on the ground.
		$this->doCloseInventory();

		$ev = new PlayerDeathEvent($this, $this->getDrops());
		$ev->call();

		if(!$ev->getKeepInventory()){
			foreach($ev->getDrops() as $item){
				$this->level->dropItem($this, $item);
			}

			if($this->inventory !== null){
			    $this->inventory->setHeldItemIndex(0);
				$this->inventory->clearAll();
			}
			if($this->armorInventory !== null){
				$this->armorInventory->clearAll();
			}
			if($this->offHandInventory !== null){
				$this->offHandInventory->clearAll();
			}
		}

		//TODO: allow this number to be manipulated during PlayerDeathEvent
		$this->level->dropExperience($this, $this->getXpDropAmount());
		$this->setXpAndProgress(0, 0);

		if($ev->getDeathMessage() != ""){
			$this->server->broadcastMessage($ev->getDeathMessage());
		}
	}

	protected function onDeathUpdate(int $tickDiff) : bool{
		parent::onDeathUpdate($tickDiff);
		return false; //never flag players for despawn
	}

	protected function respawn() : void{
		if($this->server->isHardcore()){
			$this->setBanned(true);
			return;
		}

		$ev = new PlayerRespawnEvent($this, $this->getSpawn());
		$ev->call();

		$realSpawn = Position::fromObject($ev->getRespawnPosition()->add(0.5, 0, 0.5), $ev->getRespawnPosition()->getLevel());
		$this->teleport($realSpawn);

		$this->setSprinting(false);
		$this->setSneaking(false);

		$this->extinguish();
		$this->setAirSupplyTicks($this->getMaxAirSupplyTicks());
		$this->deadTicks = 0;
		$this->noDamageTicks = 60;

		$this->removeAllEffects();
		$this->setHealth($this->getMaxHealth());

		foreach($this->attributeMap->getAll() as $attr){
			$attr->resetToDefault();
		}

		$this->sendData($this);
		$this->sendData($this->getViewers());

		$this->syncAbilities();
		$this->sendAllInventories();

		$this->spawnToAll();
		$this->scheduleUpdate();
	}

	protected function applyPostDamageEffects(EntityDamageEvent $source) : void{
		parent::applyPostDamageEffects($source);

		$this->exhaust(0.3, PlayerExhaustEvent::CAUSE_DAMAGE);
	}

	public function attack(EntityDamageEvent $source) : void{
		if(!$this->isAlive()){
			return;
		}

		if($this->isCreative()
			and $source->getCause() !== EntityDamageEvent::CAUSE_SUICIDE
			and $source->getCause() !== EntityDamageEvent::CAUSE_VOID
		){
			$source->setCancelled();
		}elseif($this->allowFlight and $source->getCause() === EntityDamageEvent::CAUSE_FALL){
			$source->setCancelled();
		}

		parent::attack($source);
	}

	public function broadcastEntityEvent(int $eventId, ?int $eventData = null, ?array $players = null) : void{
		if($this->spawned and $players === null){
			$players = $this->getViewers();
			$players[] = $this;
		}
		parent::broadcastEntityEvent($eventId, $eventData, $players);
	}

	public function getOffsetPosition(Vector3 $vector3) : Vector3{
		$result = parent::getOffsetPosition($vector3);
		$result->y += 0.001; //Hack for MCPE falling underground for no good reason (TODO: find out why it's doing this)
		return $result;
	}

	public function sendPosition(Vector3 $pos, float $yaw = null, float $pitch = null, int $mode = MovePlayerPacket::MODE_NORMAL, array $targets = null){
		$yaw = $yaw ?? $this->yaw;
		$pitch = $pitch ?? $this->pitch;

		$pk = new MovePlayerPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->position = $this->getOffsetPosition($pos);
		$pk->pitch = $pitch;
		$pk->headYaw = $yaw;
		$pk->yaw = $yaw;
		$pk->mode = $mode;
		$pk->ridingEid = intval($this->ridingEid);

		if($targets !== null){
			if(in_array($this, $targets, true)){
				$this->ySize = 0;
			}
			$this->server->broadcastPacket($targets, $pk);
		}else{
			$this->ySize = 0;
			$this->dataPacket($pk);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function teleport(Vector3 $pos, float $yaw = null, float $pitch = null) : bool{
		if(parent::teleport($pos, $yaw, $pitch)){

			$this->removeAllWindows();

			$this->sendPosition($this, $this->yaw, $this->pitch, MovePlayerPacket::MODE_TELEPORT, null);
			$this->sendPosition($this, $this->yaw, $this->pitch, MovePlayerPacket::MODE_TELEPORT, $this->getViewers());

			$this->spawnToAll();

			$this->resetFallDistance();
			$this->nextChunkOrderRun = 0;
			if($this->spawnChunkLoadCount !== -1){
				$this->spawnChunkLoadCount = 0;
			}
			$this->stopSleep();

			$this->forceMoveSync = $pos->asVector3();

			//TODO: workaround for player last pos not getting updated
			//Entity::updateMovement() normally handles this, but it's overridden with an empty function in Player
			$this->resetLastMovements();

			return true;
		}

		return false;
	}

	protected function addDefaultWindows(){
		$this->addWindow($this->getInventory(), ContainerIds::INVENTORY, true);

        $this->addWindow($this->getOffHandInventory(), ContainerIds::OFFHAND, true);

		$this->addWindow($this->getArmorInventory(), ContainerIds::ARMOR, true);

		$this->cursorInventory = new PlayerCursorInventory($this);
		$this->addWindow($this->cursorInventory, ContainerIds::UI, true);

		$this->craftingGrid = new CraftingGrid($this, CraftingGrid::SIZE_SMALL);
		//TODO: more windows
	}

	public function getCursorInventory() : PlayerCursorInventory{
		return $this->cursorInventory;
	}

	public function getCraftingGrid() : CraftingGrid{
		return $this->craftingGrid;
	}

	/**
	 * @param CraftingGrid $grid
	 */
	public function setCraftingGrid(CraftingGrid $grid) : void{
		$this->craftingGrid = $grid;
	}

	public function doCloseInventory(bool $closeTransactionQueue = true) : void{
		if($closeTransactionQueue){
            if($this->transactionQueue instanceof TransactionQueue){
                $this->transactionQueue->onCloseWindow();
		    }
        }

		/** @var Inventory[] $inventories */
		$inventories = [$this->craftingGrid, $this->cursorInventory];
		foreach($inventories as $inventory){
			$contents = $inventory->getContents();
			if(count($contents) > 0){
				$drops = $this->inventory->addItem(...$contents);
				foreach($drops as $drop){
					$this->dropItem($drop);
				}

				$inventory->clearAll();
			}
		}

		if($this->craftingGrid->getGridWidth() > CraftingGrid::SIZE_SMALL){
			$this->craftingGrid = new CraftingGrid($this, CraftingGrid::SIZE_SMALL);
		}
	}

	/**
	 * Opens the player's sign editor GUI for the sign at the given position.
	 * TODO: add support for editing the rear side of the sign (not currently supported due to technical limitations)
	 */
	public function openSignEditor(Vector3 $position) : void{
		$tile = $this->getLevelNonNull()->getTile($position);
		if($tile instanceof TileSign){
			$tile->setEditorEntityRuntimeId($this->getId());
			$this->onOpenSignEditor($position, true);
		}else{
			throw new \InvalidArgumentException("Tile at this position is not a sign");
		}
	}

	public function onOpenSignEditor(Vector3 $signPosition, bool $frontSide) : void{
		$this->sendDataPacket(OpenSignPacket::create($signPosition->x, $signPosition->y, $signPosition->z, $frontSide));
	}

	/**
	 * Returns the window ID which the inventory has for this player, or -1 if the window is not open to the player.
	 *
	 * @param Inventory $inventory
	 *
	 * @return int
	 */
	public function getWindowId(Inventory $inventory) : int{
		return $this->windows[spl_object_hash($inventory)] ?? ContainerIds::NONE;
	}

	/**
	 * Returns the inventory window open to the player with the specified window ID, or null if no window is open with
	 * that ID.
	 *
	 * @param int $windowId
	 *
	 * @return Inventory|null
	 */
	public function getWindow(int $windowId){
		return $this->windowIndex[$windowId] ?? null;
	}

	/**
	 * Opens an inventory window to the player. Returns the ID of the created window, or the existing window ID if the
	 * player is already viewing the specified inventory.
	 *
	 * @param Inventory $inventory
	 * @param int|null  $forceId Forces a special ID for the window
	 * @param bool      $isPermanent Prevents the window being removed if true.
	 *
	 * @return int
	 *
	 * @throws InvalidArgumentException if a forceID which is already in use is specified
	 * @throws InvalidStateException if trying to add a window without forceID when no slots are free
	 */
	public function addWindow(Inventory $inventory, int $forceId = null, bool $isPermanent = false) : int{
		if(($id = $this->getWindowId($inventory)) !== ContainerIds::NONE){
			return $id;
		}

		if($forceId === null){
			$cnt = $this->windowCnt;
			do{
				$cnt = max(ContainerIds::FIRST, ($cnt + 1) % self::RESERVED_WINDOW_ID_RANGE_START);
				if($cnt === $this->windowCnt){ //wraparound, no free slots
					throw new InvalidStateException("No free window IDs found");
				}
			}while(isset($this->windowIndex[$cnt]));
			$this->windowCnt = $cnt;
		}else{
			$cnt = $forceId;
			if(isset($this->windowIndex[$cnt]) or ($cnt >= self::RESERVED_WINDOW_ID_RANGE_START && $cnt <= self::RESERVED_WINDOW_ID_RANGE_END)){
				throw new InvalidArgumentException("Requested force ID $forceId already in use");
			}
		}

		$this->windowIndex[$cnt] = $inventory;
		$this->windows[spl_object_hash($inventory)] = $cnt;
		if($inventory->open($this)){
			if($isPermanent){
				$this->permanentWindows[$cnt] = true;
			}
			return $cnt;
		}else{
			$this->removeWindow($inventory);

			return -1;
		}
	}

	/**
	 * Removes an inventory window from the player.
	 *
	 * @param Inventory $inventory
	 * @param bool      $force Forces removal of permanent windows such as normal inventory, cursor
	 *
	 * @throws InvalidArgumentException if trying to remove a fixed inventory window without the `force` parameter as true
	 */
	public function removeWindow(Inventory $inventory, bool $force = false){
		$id = $this->windows[$hash = spl_object_hash($inventory)] ?? null;

		if($id !== null and !$force and isset($this->permanentWindows[$id])){
			throw new InvalidArgumentException("Cannot remove fixed window $id (" . get_class($inventory) . ") from " . $this->getName());
		}

		if($id !== null){
		    (new InventoryCloseEvent($inventory, $this))->call();
			$inventory->close($this);
			unset($this->windows[$hash], $this->windowIndex[$id], $this->permanentWindows[$id]);
		}
	}

	/**
	 * Removes all inventory windows from the player. By default this WILL NOT remove permanent windows.
	 *
	 * @param bool $removePermanentWindows Whether to remove permanent windows.
	 */
	public function removeAllWindows(bool $removePermanentWindows = false){
		foreach($this->windowIndex as $id => $window){
			if(!$removePermanentWindows and isset($this->permanentWindows[$id])){
				continue;
			}

			$this->removeWindow($window, $removePermanentWindows);
		}
	}

	protected function sendAllInventories(){
		foreach($this->windowIndex as $id => $inventory){
			$inventory->sendContents($this);
		}
	}

	public function setMetadata(string $metadataKey, MetadataValue $newMetadataValue){
		$this->server->getPlayerMetadata()->setMetadata($this, $metadataKey, $newMetadataValue);
	}

	public function getMetadata(string $metadataKey){
		return $this->server->getPlayerMetadata()->getMetadata($this, $metadataKey);
	}

	public function hasMetadata(string $metadataKey) : bool{
		return $this->server->getPlayerMetadata()->hasMetadata($this, $metadataKey);
	}

	public function removeMetadata(string $metadataKey, Plugin $owningPlugin){
		$this->server->getPlayerMetadata()->removeMetadata($this, $metadataKey, $owningPlugin);
	}

	public function onChunkChanged(Chunk $chunk){
		$hasSent = $this->usedChunks[$hash = Level::chunkHash($chunk->getX(), $chunk->getZ())] ?? false;
		if($hasSent){
			$this->usedChunks[$hash] = false;
			$this->nextChunkOrderRun = 0;
		}
	}

	public function onChunkLoaded(Chunk $chunk){

	}

	public function onChunkPopulated(Chunk $chunk){

	}

	public function onChunkUnloaded(Chunk $chunk){

	}

	public function onBlockChanged(Vector3 $block){

	}

	public function getLoaderId() : int{
		return $this->loaderId;
	}

	public function isLoaderActive() : bool{
		return $this->isConnected();
	}

    public function getOriginalProtocol() : int{
        return $this->originalProtocol;
    }

    public function getProtocol() : int{
	    return $this->protocol;
	}

    public function getChunkProtocol() : int{
        return $this->chunkProtocol;
    }

    public function getCraftingProtocol() : int{
        return $this->craftingProtocol;
    }

    public function getVanillaVersion() : string{
        return $this->vanillaVersion;
    }

    /**
     * @return bool
     */
    public function isBedrock() : bool{
        return $this->getProtocol() >= ProtocolInfo::PROTOCOL_130;
    }

	/**
	 * @return ?EnchantInventory
	 */
	public function findEnchantInventory() : ?EnchantInventory{
		$inventory = null;
		foreach($this->windowIndex as $window){
			if($window instanceof EnchantInventory){
				$inventory = $window;
				break;
			}
		}

		return $inventory;
	}

	/**
	 * @return ?AnvilInventory
	 */
	public function findAnvilInventory() : ?AnvilInventory{
		$inventory = null;
		foreach($this->windowIndex as $window){
			if($window instanceof AnvilInventory){
				$inventory = $window;
				break;
			}
		}

		return $inventory;
	}

	/**
	 * @return ?string
	 */
	public function getInteractiveTag() : ?string{
		return $this->propertyManager->getString(self::DATA_INTERACTIVE_TAG);
	}

	/**
	 * @param TextContainer|string $tag
	 */
	public function setInteractiveTag(TextContainer|string $tag) : void{
		if($tag instanceof TextContainer){
			$tag = $tag->getText();
		}

		$translate = $this->server->getLanguage()->translateString($tag);
		$this->propertyManager->setString(self::DATA_INTERACTIVE_TAG, $translate);
	}

	public function removeInteractiveTag() : void{
		if($this->getInteractiveTag() !== null){
			$this->propertyManager->setString(self::DATA_INTERACTIVE_TAG, "");
		}
	}

	public function setCurrentItemStackRequestId(?int $id) : void{
        $this->currentItemStackRequestId = $id;
    }

	public function getCurrentItemStackRequestId() : ?int{
		return $this->currentItemStackRequestId;
	}
}
