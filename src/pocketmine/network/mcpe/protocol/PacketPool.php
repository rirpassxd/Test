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

use InvalidArgumentException;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketMagicNumbers;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds81;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds91;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds100;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds102;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds105;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds110;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds130;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds280;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds310;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds331;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds360;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds385;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds387;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds401;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds419;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds422;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds428;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds440;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds448;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds465;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds471;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds486;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds503;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds526;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds534;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds544;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds553;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds560;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds567;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds575;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds582;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds594;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds618;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds649;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds662;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds671;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds685;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds686;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds712;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds729;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds748;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds776;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds786;
use pocketmine\network\mcpe\protocol\PacketsIds\PacketsIds800;
use pocketmine\utils\Binary;
use ReflectionClass;
use SplFixedArray;
use function array_key_last;

class PacketPool {
    private const PROTOCOL_POOL = [
		ProtocolInfo::PROTOCOL_800 => PacketsIds800::class,
        ProtocolInfo::PROTOCOL_786 => PacketsIds786::class,
		ProtocolInfo::PROTOCOL_776 => PacketsIds776::class,
		ProtocolInfo::PROTOCOL_748 => PacketsIds748::class,
		ProtocolInfo::PROTOCOL_729 => PacketsIds729::class,
		ProtocolInfo::PROTOCOL_712 => PacketsIds712::class,
		ProtocolInfo::PROTOCOL_686 => PacketsIds686::class,
		ProtocolInfo::PROTOCOL_685 => PacketsIds685::class,
		ProtocolInfo::PROTOCOL_671 => PacketsIds671::class,
		ProtocolInfo::PROTOCOL_662 => PacketsIds662::class,
		ProtocolInfo::PROTOCOL_649 => PacketsIds649::class,
		ProtocolInfo::PROTOCOL_618 => PacketsIds618::class,
		ProtocolInfo::PROTOCOL_594 => PacketsIds594::class,
		ProtocolInfo::PROTOCOL_582 => PacketsIds582::class,
        ProtocolInfo::PROTOCOL_575 => PacketsIds575::class,
        ProtocolInfo::PROTOCOL_567 => PacketsIds567::class,
        ProtocolInfo::PROTOCOL_560 => PacketsIds560::class,
        ProtocolInfo::PROTOCOL_553 => PacketsIds553::class,
        ProtocolInfo::PROTOCOL_544 => PacketsIds544::class,
        ProtocolInfo::PROTOCOL_534 => PacketsIds534::class,
        ProtocolInfo::PROTOCOL_526 => PacketsIds526::class,
        ProtocolInfo::PROTOCOL_503 => PacketsIds503::class,
        ProtocolInfo::PROTOCOL_486 => PacketsIds486::class,
        ProtocolInfo::PROTOCOL_471 => PacketsIds471::class,
        ProtocolInfo::PROTOCOL_465 => PacketsIds465::class,
        ProtocolInfo::PROTOCOL_448 => PacketsIds448::class,
        ProtocolInfo::PROTOCOL_440 => PacketsIds440::class,
        ProtocolInfo::PROTOCOL_428 => PacketsIds428::class,
        ProtocolInfo::PROTOCOL_422 => PacketsIds422::class,
        ProtocolInfo::PROTOCOL_419 => PacketsIds419::class,
        ProtocolInfo::PROTOCOL_401 => PacketsIds401::class,
        ProtocolInfo::PROTOCOL_387 => PacketsIds387::class,
        ProtocolInfo::PROTOCOL_385 => PacketsIds385::class,
        ProtocolInfo::PROTOCOL_360 => PacketsIds360::class,
        ProtocolInfo::PROTOCOL_331 => PacketsIds331::class,
        ProtocolInfo::PROTOCOL_310 => PacketsIds310::class,
        ProtocolInfo::PROTOCOL_280 => PacketsIds280::class,
        ProtocolInfo::PROTOCOL_130 => PacketsIds130::class,
        ProtocolInfo::PROTOCOL_110 => PacketsIds110::class,
		ProtocolInfo::PROTOCOL_105 => PacketsIds105::class,
		ProtocolInfo::PROTOCOL_102 => PacketsIds102::class,
		ProtocolInfo::PROTOCOL_100 => PacketsIds100::class,
		ProtocolInfo::PROTOCOL_91 => PacketsIds91::class,
		ProtocolInfo::PROTOCOL_81 => PacketsIds81::class
    ];

	/** @var SplFixedArray<int>[] */
	protected static $magicPoolTo = null;
	/** @var SplFixedArray<int>[] */
	protected static $magicPoolFrom = null;
	/** @var SplFixedArray<DataPacket> */
    protected static $pool = null;
	/** @var int[] */
	protected static $protocolList = null;

	public static function init() : void{
		$magicNumbers = (new ReflectionClass(PacketMagicNumbers::class))->getConstants();
		self::$magicPoolTo = [];
		self::$magicPoolFrom = [];
	    self::$pool = new SplFixedArray(512);
		self::$protocolList = array_keys(self::PROTOCOL_POOL);
		foreach(self::$protocolList as $protocol){
			$protocolInfoClass = self::PROTOCOL_POOL[$protocol];
			$protocolMagicNumbers = (new ReflectionClass($protocolInfoClass))->getConstants();
			self::$magicPoolTo[$protocol] = new SplFixedArray(512);
			self::$magicPoolFrom[$protocol] = new SplFixedArray(512);
			foreach($protocolMagicNumbers as $name => $magic){
				if(isset($magicNumbers[$name])){
					self::$magicPoolTo[$protocol][$magicNumbers[$name]] = $magic;
					self::$magicPoolFrom[$protocol][$magic] = $magicNumbers[$name];
				}else{
					throw new InvalidArgumentException("Packet $name not founded in magic numbers class.($protocolInfoClass $magic)");
				}
			}
        }

		self::registerPacket(new LoginPacket());
		self::registerPacket(new PlayStatusPacket());
		self::registerPacket(new ServerToClientHandshakePacket());
		self::registerPacket(new ClientToServerHandshakePacket());
		self::registerPacket(new DisconnectPacket());
		self::registerPacket(new ResourcePacksInfoPacket());
		self::registerPacket(new ResourcePackStackPacket());
		self::registerPacket(new ResourcePackClientResponsePacket());
		self::registerPacket(new TextPacket());
		self::registerPacket(new SetTimePacket());
		self::registerPacket(new StartGamePacket());
		self::registerPacket(new AddPlayerPacket());
		self::registerPacket(new AddActorPacket());
		self::registerPacket(new RemoveActorPacket());
		self::registerPacket(new AddItemActorPacket());
		self::registerPacket(new ServerPlayerPostMovePositionPacket());
		self::registerPacket(new TakeItemActorPacket());
		self::registerPacket(new MoveActorAbsolutePacket());
		self::registerPacket(new MovePlayerPacket());
		self::registerPacket(new RiderJumpPacket());
		self::registerPacket(new UpdateBlockPacket());
		self::registerPacket(new AddPaintingPacket());
		self::registerPacket(new TickSyncPacket());
		self::registerPacket(new LevelSoundEventPacketV1());
		self::registerPacket(new LevelEventPacket());
		self::registerPacket(new BlockEventPacket());
		self::registerPacket(new ActorEventPacket());
		self::registerPacket(new MobEffectPacket());
		self::registerPacket(new UpdateAttributesPacket());
		self::registerPacket(new InventoryTransactionPacket());
		self::registerPacket(new MobEquipmentPacket());
		self::registerPacket(new MobArmorEquipmentPacket());
		self::registerPacket(new InteractPacket());
		self::registerPacket(new BlockPickRequestPacket());
		self::registerPacket(new ActorPickRequestPacket());
		self::registerPacket(new PlayerActionPacket());
		self::registerPacket(new ActorFallPacket());
		self::registerPacket(new HurtArmorPacket());
		self::registerPacket(new SetActorDataPacket());
		self::registerPacket(new SetActorMotionPacket());
		self::registerPacket(new SetActorLinkPacket());
		self::registerPacket(new SetHealthPacket());
		self::registerPacket(new SetSpawnPositionPacket());
		self::registerPacket(new AnimatePacket());
		self::registerPacket(new RespawnPacket());
		self::registerPacket(new ContainerOpenPacket());
		self::registerPacket(new ContainerClosePacket());
		self::registerPacket(new PlayerHotbarPacket());
		self::registerPacket(new InventoryContentPacket());
		self::registerPacket(new InventorySlotPacket());
		self::registerPacket(new ContainerSetDataPacket());
		self::registerPacket(new CraftingDataPacket());
		self::registerPacket(new CraftingEventPacket());
		self::registerPacket(new GuiDataPickItemPacket());
		self::registerPacket(new AdventureSettingsPacket());
		self::registerPacket(new BlockActorDataPacket());
		self::registerPacket(new PlayerInputPacket());
		self::registerPacket(new LevelChunkPacket());
		self::registerPacket(new SetCommandsEnabledPacket());
		self::registerPacket(new SetDifficultyPacket());
		self::registerPacket(new ChangeDimensionPacket());
		self::registerPacket(new SetPlayerGameTypePacket());
		self::registerPacket(new PlayerListPacket());
		self::registerPacket(new SimpleEventPacket());
		self::registerPacket(new LegacyTelemetryEventPacket());
		self::registerPacket(new SpawnExperienceOrbPacket());
		self::registerPacket(new ClientboundMapItemDataPacket());
		self::registerPacket(new MapInfoRequestPacket());
		self::registerPacket(new RequestChunkRadiusPacket());
		self::registerPacket(new ChunkRadiusUpdatedPacket());
		self::registerPacket(new ItemFrameDropItemPacket());
		self::registerPacket(new GameRulesChangedPacket());
		self::registerPacket(new CameraPacket());
		self::registerPacket(new BossEventPacket());
		self::registerPacket(new ShowCreditsPacket());
		self::registerPacket(new AvailableCommandsPacket());
		self::registerPacket(new CommandRequestPacket());
		self::registerPacket(new CommandBlockUpdatePacket());
		self::registerPacket(new CommandOutputPacket());
		self::registerPacket(new UpdateTradePacket());
		self::registerPacket(new UpdateEquipPacket());
		self::registerPacket(new ResourcePackDataInfoPacket());
		self::registerPacket(new ResourcePackChunkDataPacket());
		self::registerPacket(new ResourcePackChunkRequestPacket());
		self::registerPacket(new TransferPacket());
		self::registerPacket(new PlaySoundPacket());
		self::registerPacket(new StopSoundPacket());
		self::registerPacket(new SetTitlePacket());
		self::registerPacket(new AddBehaviorTreePacket());
		self::registerPacket(new StructureBlockUpdatePacket());
		self::registerPacket(new ShowStoreOfferPacket());
		self::registerPacket(new PurchaseReceiptPacket());
		self::registerPacket(new PlayerSkinPacket());
		self::registerPacket(new SubClientLoginPacket());
		self::registerPacket(new AutomationClientConnectPacket());
		self::registerPacket(new SetLastHurtByPacket());
		self::registerPacket(new BookEditPacket());
		self::registerPacket(new NpcRequestPacket());
		self::registerPacket(new PhotoTransferPacket());
		self::registerPacket(new ModalFormRequestPacket());
		self::registerPacket(new ModalFormResponsePacket());
		self::registerPacket(new ServerSettingsRequestPacket());
		self::registerPacket(new ServerSettingsResponsePacket());
		self::registerPacket(new ShowProfilePacket());
		self::registerPacket(new SetDefaultGameTypePacket());
		self::registerPacket(new RemoveObjectivePacket());
		self::registerPacket(new SetDisplayObjectivePacket());
		self::registerPacket(new SetScorePacket());
		self::registerPacket(new LabTablePacket());
		self::registerPacket(new UpdateBlockSyncedPacket());
		self::registerPacket(new MoveActorDeltaPacket());
		self::registerPacket(new SetScoreboardIdentityPacket());
		self::registerPacket(new SetLocalPlayerAsInitializedPacket());
		self::registerPacket(new UpdateSoftEnumPacket());
		self::registerPacket(new NetworkStackLatencyPacket());
		self::registerPacket(new ScriptCustomEventPacket());
		self::registerPacket(new SpawnParticleEffectPacket());
		self::registerPacket(new AvailableActorIdentifiersPacket());
		self::registerPacket(new LevelSoundEventPacketV2());
		self::registerPacket(new NetworkChunkPublisherUpdatePacket());
		self::registerPacket(new BiomeDefinitionListPacket());
		self::registerPacket(new LevelSoundEventPacket());
		self::registerPacket(new LevelEventGenericPacket());
		self::registerPacket(new LecternUpdatePacket());
		self::registerPacket(new VideoStreamConnectPacket());
		self::registerPacket(new ClientCacheStatusPacket());
		self::registerPacket(new OnScreenTextureAnimationPacket());
		self::registerPacket(new MapCreateLockedCopyPacket());
		self::registerPacket(new StructureTemplateDataRequestPacket());
		self::registerPacket(new StructureTemplateDataResponsePacket());
		self::registerPacket(new UpdateBlockPropertiesPacket());
		self::registerPacket(new ClientCacheBlobStatusPacket());
		self::registerPacket(new ClientCacheMissResponsePacket());
		self::registerPacket(new EducationSettingsPacket());
		self::registerPacket(new EmotePacket());
		self::registerPacket(new MultiplayerSettingsPacket());
		self::registerPacket(new SettingsCommandPacket());
		self::registerPacket(new AnvilDamagePacket());
		self::registerPacket(new CompletedUsingItemPacket());
		self::registerPacket(new NetworkSettingsPacket());
		self::registerPacket(new PlayerAuthInputPacket());
		self::registerPacket(new CreativeContentPacket());
		self::registerPacket(new PlayerEnchantOptionsPacket());
		self::registerPacket(new ItemStackRequestPacket());
		self::registerPacket(new ItemStackResponsePacket());
		self::registerPacket(new PlayerArmorDamagePacket());
		self::registerPacket(new CodeBuilderPacket());
		self::registerPacket(new UpdatePlayerGameTypePacket());
		self::registerPacket(new EmoteListPacket());
		self::registerPacket(new PositionTrackingDBServerBroadcastPacket());
		self::registerPacket(new PositionTrackingDBClientRequestPacket());
		self::registerPacket(new DebugInfoPacket());
		self::registerPacket(new PacketViolationWarningPacket());
		self::registerPacket(new MotionPredictionHintsPacket());
		self::registerPacket(new AnimateEntityPacket());
		self::registerPacket(new CameraShakePacket());
		self::registerPacket(new PlayerFogPacket());
		self::registerPacket(new CorrectPlayerMovePredictionPacket());
		self::registerPacket(new ItemRegistryPacket());
		self::registerPacket(new FilterTextPacket());
		self::registerPacket(new ClientboundDebugRendererPacket());
		self::registerPacket(new SyncActorPropertyPacket());
		self::registerPacket(new AddVolumeEntityPacket());
		self::registerPacket(new RemoveVolumeEntityPacket());
		self::registerPacket(new SimulationTypePacket());
		self::registerPacket(new NpcDialoguePacket());
		self::registerPacket(new EduUriResourcePacket());
		self::registerPacket(new CreatePhotoPacket());
		self::registerPacket(new UpdateSubChunkBlocksPacket());
		self::registerPacket(new PhotoInfoRequestPacket());
		self::registerPacket(new PlayerStartItemCooldownPacket());
		self::registerPacket(new ScriptMessagePacket());
		self::registerPacket(new CodeBuilderSourcePacket());
		self::registerPacket(new TickingAreasLoadStatusPacket());
		self::registerPacket(new DimensionDataPacket());
		self::registerPacket(new AgentActionEventPacket());
		self::registerPacket(new ChangeMobPropertyPacket());
		self::registerPacket(new LessonProgressPacket());
		self::registerPacket(new RequestAbilityPacket());
		self::registerPacket(new RequestPermissionsPacket());
		self::registerPacket(new ToastRequestPacket());
		self::registerPacket(new UpdateAbilitiesPacket());
		self::registerPacket(new UpdateAdventureSettingsPacket());
		self::registerPacket(new DeathInfoPacket());
		self::registerPacket(new EditorNetworkPacket());
		self::registerPacket(new FeatureRegistryPacket());
		self::registerPacket(new ServerStatsPacket());
		self::registerPacket(new RequestNetworkSettingsPacket());
		self::registerPacket(new GameTestRequestPacket());
		self::registerPacket(new GameTestResultsPacket());
		self::registerPacket(new UpdateClientInputLocksPacket());
		self::registerPacket(new ClientCheatAbilityPacket());
		self::registerPacket(new CameraPresetsPacket());
		self::registerPacket(new UnlockedRecipesPacket());
		self::registerPacket(new CameraInstructionPacket());
		self::registerPacket(new CompressedBiomeDefinitionListPacket());
		self::registerPacket(new TrimDataPacket());
		self::registerPacket(new OpenSignPacket());
		self::registerPacket(new AgentAnimationPacket());
		self::registerPacket(new RefreshEntitlementsPacket());
	    self::registerPacket(new PlayerToggleCrafterSlotRequestPacket());
		self::registerPacket(new SetPlayerInventoryOptionsPacket());
		self::registerPacket(new SetHudPacket());
		self::registerPacket(new AwardAchievementPacket());
		self::registerPacket(new ClientboundCloseFormPacket());
        self::registerPacket(new ServerboundLoadingScreenPacket());
        self::registerPacket(new JigsawStructureDataPacket());
        self::registerPacket(new CurrentStructureFeaturePacket());
        self::registerPacket(new ServerboundDiagnosticsPacket());
	    self::registerPacket(new CameraAimAssistPacket());
		self::registerPacket(new ContainerRegistryCleanupPacket());
		self::registerPacket(new MovementEffectPacket());
		self::registerPacket(new SetMovementAuthorityPacket());
		self::registerPacket(new ClientMovementPredictionSyncPacket());
		self::registerPacket(new UpdateClientOptionsPacket());
		self::registerPacket(new PlayerVideoCapturePacket());
		self::registerPacket(new PlayerUpdateEntityOverridesPacket());
		self::registerPacket(new PlayerLocationPacket());
		self::registerPacket(new ClientboundControlSchemeSetPacket());
		// ExplodePacket - TickSyncPacket
		self::registerPacket(new ExplodePacket());
		// ItemComponentPacket - ItemRegistryPacket
		self::registerPacket(new ItemComponentPacket());
		// 1.1
		self::registerPacket(new RemoveBlockPacket());
		self::registerPacket(new UseItemPacket());
		self::registerPacket(new DropItemPacket());
		self::registerPacket(new ContainerSetContentPacket());
		self::registerPacket(new ContainerSetSlotPacket());
		self::registerPacket(new CommandStepPacket());
		// 1.0 and older
		self::registerPacket(new OldBatchPacket());
	}

	/**
	 * @param DataPacket $packet
	 */
	public static function registerPacket(DataPacket $packet){
		self::$pool[$packet->pid()] = clone $packet;
	}

	/**
	 * @param int $pid
	 * @param int $playerProtocol
	 *
	 * @return DataPacket
	 */
	public static function getPacketById(int $pid, int $playerProtocol) : DataPacket{
		$magicId = self::getPacketMagicById($pid, $playerProtocol);
		return isset(self::$pool[$magicId]) ? clone self::$pool[$magicId] : new UnknownPacket();
	}

	/**
	 * @param string $buffer
	 * @param int $playerProtocol
	 * 
	 * @return DataPacket
	 */
	public static function getPacket(string $buffer, int $playerProtocol) : DataPacket{
		$offset = 0;
		$pk = self::getPacketById(Binary::readUnsignedVarInt($buffer, $offset), $playerProtocol);
		$pk->setBuffer($buffer, $offset);

		return $pk;
	}

    /**
     * @param int $magic
     * @param int $playerProtocol
     * 
     * @return int
     */
    public static function getPacketIdByMagic(int $magic, int $playerProtocol) : int{
        foreach(($protocols = self::$protocolList) as $protocol){
            if($playerProtocol >= $protocol){
                $poolProtocol = $protocol;
                break;
            }
        }

        if(!isset($poolProtocol)){
            $poolProtocol = $protocols[array_key_last($protocols)];
        }

        return isset(self::$magicPoolTo[$poolProtocol][$magic]) ? self::$magicPoolTo[$poolProtocol][$magic] : 0;
    }

    /**
     * @param int $id
     * @param int $playerProtocol
     * 
     * @return int
     */
    public static function getPacketMagicById(int $id, int $playerProtocol) : int{
        foreach(self::$protocolList as $protocol){
            if($playerProtocol >= $protocol){
                return isset(self::$magicPoolFrom[$protocol][$id]) ? self::$magicPoolFrom[$protocol][$id] : -1;
            }
        }

        return -1;
    }

}
