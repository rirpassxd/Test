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

namespace pocketmine\network\mcpe;

use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\ActorFallPacket;
use pocketmine\network\mcpe\protocol\ActorPickRequestPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddBehaviorTreePacket;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\AddPaintingPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\AgentActionEventPacket;
use pocketmine\network\mcpe\protocol\AgentAnimationPacket;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\AnvilDamagePacket;
use pocketmine\network\mcpe\protocol\AutomationClientConnectPacket;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\AwardAchievementPacket;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\BlockPickRequestPacket;
use pocketmine\network\mcpe\protocol\BookEditPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\CameraInstructionPacket;
use pocketmine\network\mcpe\protocol\CameraPacket;
use pocketmine\network\mcpe\protocol\CameraPresetsPacket;
use pocketmine\network\mcpe\protocol\CameraShakePacket;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\ChangeMobPropertyPacket;
use pocketmine\network\mcpe\protocol\ChunkRadiusUpdatedPacket;
use pocketmine\network\mcpe\protocol\ClientboundCloseFormPacket;
use pocketmine\network\mcpe\protocol\ClientboundDebugRendererPacket;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\ClientCacheBlobStatusPacket;
use pocketmine\network\mcpe\protocol\ClientCacheMissResponsePacket;
use pocketmine\network\mcpe\protocol\ClientCacheStatusPacket;
use pocketmine\network\mcpe\protocol\ClientCheatAbilityPacket;
use pocketmine\network\mcpe\protocol\ClientToServerHandshakePacket;
use pocketmine\network\mcpe\protocol\CodeBuilderPacket;
use pocketmine\network\mcpe\protocol\CodeBuilderSourcePacket;
use pocketmine\network\mcpe\protocol\CommandBlockUpdatePacket;
use pocketmine\network\mcpe\protocol\CommandOutputPacket;
use pocketmine\network\mcpe\protocol\CommandRequestPacket;
use pocketmine\network\mcpe\protocol\CommandStepPacket;
use pocketmine\network\mcpe\protocol\CompletedUsingItemPacket;
use pocketmine\network\mcpe\protocol\CompressedBiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ContainerSetContentPacket;
use pocketmine\network\mcpe\protocol\ContainerSetDataPacket;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\network\mcpe\protocol\CorrectPlayerMovePredictionPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\CraftingEventPacket;
use pocketmine\network\mcpe\protocol\CreatePhotoPacket;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DeathInfoPacket;
use pocketmine\network\mcpe\protocol\DebugInfoPacket;
use pocketmine\network\mcpe\protocol\DimensionDataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\DropItemPacket;
use pocketmine\network\mcpe\protocol\EditorNetworkPacket;
use pocketmine\network\mcpe\protocol\EducationSettingsPacket;
use pocketmine\network\mcpe\protocol\EduUriResourcePacket;
use pocketmine\network\mcpe\protocol\EmoteListPacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\ExplodePacket;
use pocketmine\network\mcpe\protocol\FeatureRegistryPacket;
use pocketmine\network\mcpe\protocol\FilterTextPacket;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\GameTestRequestPacket;
use pocketmine\network\mcpe\protocol\GameTestResultsPacket;
use pocketmine\network\mcpe\protocol\GuiDataPickItemPacket;
use pocketmine\network\mcpe\protocol\HurtArmorPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemComponentPacket;
use pocketmine\network\mcpe\protocol\ItemFrameDropItemPacket;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\LabTablePacket;
use pocketmine\network\mcpe\protocol\LecternUpdatePacket;
use pocketmine\network\mcpe\protocol\LegacyTelemetryEventPacket;
use pocketmine\network\mcpe\protocol\LessonProgressPacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\LevelEventGenericPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacketV1;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacketV2;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MapCreateLockedCopyPacket;
use pocketmine\network\mcpe\protocol\MapInfoRequestPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\MotionPredictionHintsPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MoveActorDeltaPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\MultiplayerSettingsPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\NpcDialoguePacket;
use pocketmine\network\mcpe\protocol\NpcRequestPacket;
use pocketmine\network\mcpe\protocol\OnScreenTextureAnimationPacket;
use pocketmine\network\mcpe\protocol\OpenSignPacket;
use pocketmine\network\mcpe\protocol\PacketViolationWarningPacket;
use pocketmine\network\mcpe\protocol\PhotoInfoRequestPacket;
use pocketmine\network\mcpe\protocol\PhotoTransferPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerArmorDamagePacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\PlayerEnchantOptionsPacket;
use pocketmine\network\mcpe\protocol\PlayerFogPacket;
use pocketmine\network\mcpe\protocol\PlayerHotbarPacket;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\PlayerStartItemCooldownPacket;
use pocketmine\network\mcpe\protocol\PlayerToggleCrafterSlotRequestPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\PositionTrackingDBClientRequestPacket;
use pocketmine\network\mcpe\protocol\PositionTrackingDBServerBroadcastPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\PurchaseReceiptPacket;
use pocketmine\network\mcpe\protocol\RefreshEntitlementsPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\RemoveBlockPacket;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\RemoveVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\RequestAbilityPacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\RequestNetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\RequestPermissionsPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkDataPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\ResourcePackDataInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\RiderJumpPacket;
use pocketmine\network\mcpe\protocol\ScriptCustomEventPacket;
use pocketmine\network\mcpe\protocol\ScriptMessagePacket;
use pocketmine\network\mcpe\protocol\ServerPlayerPostMovePositionPacket;
use pocketmine\network\mcpe\protocol\ServerSettingsRequestPacket;
use pocketmine\network\mcpe\protocol\ServerSettingsResponsePacket;
use pocketmine\network\mcpe\protocol\ServerStatsPacket;
use pocketmine\network\mcpe\protocol\ServerToClientHandshakePacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\SetCommandsEnabledPacket;
use pocketmine\network\mcpe\protocol\SetDefaultGameTypePacket;
use pocketmine\network\mcpe\protocol\SetDifficultyPacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetHealthPacket;
use pocketmine\network\mcpe\protocol\SetHudPacket;
use pocketmine\network\mcpe\protocol\SetLastHurtByPacket;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\SetPlayerInventoryOptionsPacket;
use pocketmine\network\mcpe\protocol\SetScoreboardIdentityPacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\SetTimePacket;
use pocketmine\network\mcpe\protocol\SettingsCommandPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\ShowCreditsPacket;
use pocketmine\network\mcpe\protocol\ShowProfilePacket;
use pocketmine\network\mcpe\protocol\ShowStoreOfferPacket;
use pocketmine\network\mcpe\protocol\SimpleEventPacket;
use pocketmine\network\mcpe\protocol\SimulationTypePacket;
use pocketmine\network\mcpe\protocol\SpawnExperienceOrbPacket;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\StopSoundPacket;
use pocketmine\network\mcpe\protocol\StructureBlockUpdatePacket;
use pocketmine\network\mcpe\protocol\StructureTemplateDataRequestPacket;
use pocketmine\network\mcpe\protocol\StructureTemplateDataResponsePacket;
use pocketmine\network\mcpe\protocol\SubClientLoginPacket;
use pocketmine\network\mcpe\protocol\SyncActorPropertyPacket;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\TickingAreasLoadStatusPacket;
use pocketmine\network\mcpe\protocol\TickSyncPacket;
use pocketmine\network\mcpe\protocol\ToastRequestPacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\network\mcpe\protocol\TrimDataPacket;
use pocketmine\network\mcpe\protocol\UnlockedRecipesPacket;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\UpdateAdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPropertiesPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockSyncedPacket;
use pocketmine\network\mcpe\protocol\UpdateClientInputLocksPacket;
use pocketmine\network\mcpe\protocol\UpdateEquipPacket;
use pocketmine\network\mcpe\protocol\UpdatePlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\UpdateSoftEnumPacket;
use pocketmine\network\mcpe\protocol\UpdateSubChunkBlocksPacket;
use pocketmine\network\mcpe\protocol\UpdateTradePacket;
use pocketmine\network\mcpe\protocol\UseItemPacket;
use pocketmine\network\mcpe\protocol\VideoStreamConnectPacket;
use pocketmine\network\mcpe\protocol\ServerboundLoadingScreenPacket;
use pocketmine\network\mcpe\protocol\JigsawStructureDataPacket;
use pocketmine\network\mcpe\protocol\CurrentStructureFeaturePacket;
use pocketmine\network\mcpe\protocol\ServerboundDiagnosticsPacket;
use pocketmine\network\mcpe\protocol\CameraAimAssistPacket;
use pocketmine\network\mcpe\protocol\ContainerRegistryCleanupPacket;
use pocketmine\network\mcpe\protocol\MovementEffectPacket;
use pocketmine\network\mcpe\protocol\SetMovementAuthorityPacket;
use pocketmine\network\mcpe\protocol\ItemRegistryPacket;
use pocketmine\network\mcpe\protocol\ClientMovementPredictionSyncPacket;
use pocketmine\network\mcpe\protocol\UpdateClientOptionsPacket;
use pocketmine\network\mcpe\protocol\PlayerVideoCapturePacket;
use pocketmine\network\mcpe\protocol\PlayerUpdateEntityOverridesPacket;
use pocketmine\network\mcpe\protocol\handlePlayerLocation;
use pocketmine\network\mcpe\protocol\handleClientboundControlSchemeSet;

abstract class NetworkSession{

	abstract public function handleDataPacket(DataPacket $packet);

    public function isCompressionEnabled() : bool{
        return true;
    }

	public function getProtocol() : int{
	    return ProtocolInfo::CURRENT_PROTOCOL;
	}

    public function handleRequestNetworkSettings(RequestNetworkSettingsPacket $packet) : bool{
        return false;
    }

	public function handleLogin(LoginPacket $packet) : bool{
		return false;
	}

	public function handlePlayStatus(PlayStatusPacket $packet) : bool{
		return false;
	}

	public function handleServerToClientHandshake(ServerToClientHandshakePacket $packet) : bool{
		return false;
	}

	public function handleClientToServerHandshake(ClientToServerHandshakePacket $packet) : bool{
		return false;
	}

	public function handleDisconnect(DisconnectPacket $packet) : bool{
		return false;
	}

	public function handleResourcePacksInfo(ResourcePacksInfoPacket $packet) : bool{
		return false;
	}

	public function handleResourcePackStack(ResourcePackStackPacket $packet) : bool{
		return false;
	}

	public function handleResourcePackClientResponse(ResourcePackClientResponsePacket $packet) : bool{
		return false;
	}

	public function handleText(TextPacket $packet) : bool{
		return false;
	}

	public function handleSetTime(SetTimePacket $packet) : bool{
		return false;
	}

	public function handleStartGame(StartGamePacket $packet) : bool{
		return false;
	}

	public function handleAddPlayer(AddPlayerPacket $packet) : bool{
		return false;
	}

	public function handleAddActor(AddActorPacket $packet) : bool{
		return false;
	}

	public function handleRemoveActor(RemoveActorPacket $packet) : bool{
		return false;
	}

	public function handleAddItemActor(AddItemActorPacket $packet) : bool{
		return false;
	}

	public function handleTakeItemActor(TakeItemActorPacket $packet) : bool{
		return false;
	}

	public function handleMoveActorAbsolute(MoveActorAbsolutePacket $packet) : bool{
		return false;
	}

	public function handleMovePlayer(MovePlayerPacket $packet) : bool{
		return false;
	}

	public function handleUpdateBlock(UpdateBlockPacket $packet) : bool{
		return false;
	}

	public function handleAddPainting(AddPaintingPacket $packet) : bool{
		return false;
	}

	public function handleExplode(ExplodePacket $packet) : bool{
		return false;
	}

	public function handleTickSync(TickSyncPacket $packet) : bool{
		return false;
	}

	public function handleLevelEvent(LevelEventPacket $packet) : bool{
		return false;
	}

	public function handleBlockEvent(BlockEventPacket $packet) : bool{
		return false;
	}

	public function handleActorEvent(ActorEventPacket $packet) : bool{
		return false;
	}

	public function handleMobEffect(MobEffectPacket $packet) : bool{
		return false;
	}

    public function handleUpdateAdventureSettings(UpdateAdventureSettingsPacket $packet) : bool{
        return false;
    }

    public function handleUpdateAbilities(UpdateAbilitiesPacket $packet) : bool{
        return false;
    }

    public function handleUnlockedRecipes(UnlockedRecipesPacket $packet) : bool{
        return false;
    }

	public function handleUpdateAttributes(UpdateAttributesPacket $packet) : bool{
		return false;
	}

	public function handleInventoryTransaction(InventoryTransactionPacket $packet) : bool{
		return false;
	}

	public function handleMobEquipment(MobEquipmentPacket $packet) : bool{
		return false;
	}

	public function handleMobArmorEquipment(MobArmorEquipmentPacket $packet) : bool{
		return false;
	}

	public function handleInteract(InteractPacket $packet) : bool{
		return false;
	}

	public function handleBlockPickRequest(BlockPickRequestPacket $packet) : bool{
		return false;
	}

	public function handlePlayerAction(PlayerActionPacket $packet) : bool{
		return false;
	}

	public function handleSetActorData(SetActorDataPacket $packet) : bool{
		return false;
	}

	public function handleSetActorMotion(SetActorMotionPacket $packet) : bool{
		return false;
	}

	public function handleSetActorLink(SetActorLinkPacket $packet) : bool{
		return false;
	}

	public function handleSetSpawnPosition(SetSpawnPositionPacket $packet) : bool{
		return false;
	}

	public function handleAnimate(AnimatePacket $packet) : bool{
		return false;
	}

	public function handleRespawn(RespawnPacket $packet) : bool{
		return false;
	}

	public function handleContainerOpen(ContainerOpenPacket $packet) : bool{
		return false;
	}

	public function handleContainerClose(ContainerClosePacket $packet) : bool{
		return false;
	}

	public function handlePlayerHotbar(PlayerHotbarPacket $packet) : bool{
		return false;
	}

	public function handleInventoryContent(InventoryContentPacket $packet) : bool{
		return false;
	}

	public function handleInventorySlot(InventorySlotPacket $packet) : bool{
		return false;
	}

	public function handleContainerSetData(ContainerSetDataPacket $packet) : bool{
		return false;
	}

	public function handleContainerSetSlot(ContainerSetSlotPacket $packet) : bool{
		return false;
	}

	public function handleContainerSetContent(ContainerSetContentPacket $packet) : bool{
		return false;
	}

	public function handleDropItem(DropItemPacket $packet) : bool{
		return false;
	}

	public function handleRemoveBlock(RemoveBlockPacket $packet) : bool{
		return false;
	}

	public function handleUseItem(UseItemPacket $packet) : bool{
		return false;
	}

	public function handleCraftingData(CraftingDataPacket $packet) : bool{
		return false;
	}

	public function handleCraftingEvent(CraftingEventPacket $packet) : bool{
		return false;
	}

	public function handleAdventureSettings(AdventureSettingsPacket $packet) : bool{
		return false;
	}

	public function handleBlockActorData(BlockActorDataPacket $packet) : bool{
		return false;
	}

	public function handlePlayerInput(PlayerInputPacket $packet) : bool{
		return false;
	}

	public function handleLevelChunk(LevelChunkPacket $packet) : bool{
		return false;
	}

	public function handleSetCommandsEnabled(SetCommandsEnabledPacket $packet) : bool{
		return false;
	}

	public function handleSetDifficulty(SetDifficultyPacket $packet) : bool{
		return false;
	}

	public function handleChangeDimension(ChangeDimensionPacket $packet) : bool{
		return false;
	}

	public function handleSetPlayerGameType(SetPlayerGameTypePacket $packet) : bool{
		return false;
	}

	public function handlePlayerList(PlayerListPacket $packet) : bool{
		return false;
	}

	public function handleSpawnExperienceOrb(SpawnExperienceOrbPacket $packet) : bool{
		return false;
	}

	public function handleClientboundMapItemData(ClientboundMapItemDataPacket $packet) : bool{
		return false;
	}

	public function handleMapInfoRequest(MapInfoRequestPacket $packet) : bool{
		return false;
	}

	public function handleRequestChunkRadius(RequestChunkRadiusPacket $packet) : bool{
		return false;
	}

	public function handleChunkRadiusUpdated(ChunkRadiusUpdatedPacket $packet) : bool{
		return false;
	}

	public function handleItemFrameDropItem(ItemFrameDropItemPacket $packet) : bool{
		return false;
	}

	public function handleGameRulesChanged(GameRulesChangedPacket $packet) : bool{
		return false;
	}

	public function handleBossEvent(BossEventPacket $packet) : bool{
		return false;
	}

	public function handleAvailableCommands(AvailableCommandsPacket $packet) : bool{
		return false;
	}

	public function handleCommandRequest(CommandRequestPacket $packet) : bool{
		return false;
	}

	public function handleCommandStep(CommandStepPacket $packet) : bool{
		return false;
	}

	public function handleResourcePackDataInfo(ResourcePackDataInfoPacket $packet) : bool{
		return false;
	}

	public function handleResourcePackChunkData(ResourcePackChunkDataPacket $packet) : bool{
		return false;
	}

	public function handleResourcePackChunkRequest(ResourcePackChunkRequestPacket $packet) : bool{
		return false;
	}

	public function handleTransfer(TransferPacket $packet) : bool{
		return false;
	}

	public function handlePlaySound(PlaySoundPacket $packet) : bool{
		return false;
	}

	public function handleSetTitle(SetTitlePacket $packet) : bool{
		return false;
	}

	public function handleShowStoreOffer(ShowStoreOfferPacket $packet) : bool{
		return false;
	}

	public function handlePurchaseReceipt(PurchaseReceiptPacket $packet) : bool{
		return false;
	}

	public function handlePlayerSkin(PlayerSkinPacket $packet) : bool{
		return false;
	}

	public function handleSubClientLogin(SubClientLoginPacket $packet) : bool{
		return false;
	}

	public function handleBookEdit(BookEditPacket $packet) : bool{
		return false;
	}

	public function handleModalFormRequest(ModalFormRequestPacket $packet) : bool{
		return false;
	}

	public function handleModalFormResponse(ModalFormResponsePacket $packet) : bool{
		return false;
	}

	public function handleServerSettingsRequest(ServerSettingsRequestPacket $packet) : bool{
		return false;
	}

	public function handleServerSettingsResponse(ServerSettingsResponsePacket $packet) : bool{
		return false;
	}

	public function handleShowProfile(ShowProfilePacket $packet) : bool{
		return false;
	}

	public function handleSetDefaultGameType(SetDefaultGameTypePacket $packet) : bool{
		return false;
	}

	public function handleRemoveObjective(RemoveObjectivePacket $packet) : bool{
		return false;
	}

	public function handleSetDisplayObjective(SetDisplayObjectivePacket $packet) : bool{
		return false;
	}

	public function handleSetScore(SetScorePacket $packet) : bool{
		return false;
	}

	public function handleSetScoreboardIdentity(SetScoreboardIdentityPacket $packet) : bool{
		return false;
	}

	public function handleSetLocalPlayerAsInitialized(SetLocalPlayerAsInitializedPacket $packet) : bool{
		return false;
	}

	public function handleSpawnParticleEffect(SpawnParticleEffectPacket $packet) : bool{
		return false;
	}

	public function handleAvailableActorIdentifiers(AvailableActorIdentifiersPacket $packet) : bool{
		return false;
	}

	public function handleNetworkChunkPublisherUpdate(NetworkChunkPublisherUpdatePacket $packet) : bool{
		return false;
	}

	public function handleBiomeDefinitionList(BiomeDefinitionListPacket $packet) : bool{
		return false;
	}

	public function handleLevelSoundEvent(LevelSoundEventPacket $packet) : bool{
		return false;
	}

	public function handleLevelSoundEventPacketV2(LevelSoundEventPacketV2 $packet) : bool{
		return false;
	}

	public function handleLabTable(LabTablePacket $packet) : bool{
		return false;
	}

	public function handleUpdateBlockSynced(UpdateBlockSyncedPacket $packet) : bool{
		return false;
	}

	public function handleMoveActorDelta(MoveActorDeltaPacket $packet) : bool{
		return false;
	}

	public function handleNpcRequest(NpcRequestPacket $packet) : bool{
		return false;
	}

	public function handlePhotoTransfer(PhotoTransferPacket $packet) : bool{
		return false;
	}

	public function handleAutomationClientConnect(AutomationClientConnectPacket $packet) : bool{
		return false;
	}

	public function handleSetLastHurtBy(SetLastHurtByPacket $packet) : bool{
		return false;
	}

	public function handleAddBehaviorTree(AddBehaviorTreePacket $packet) : bool{
		return false;
	}

	public function handleStructureBlockUpdate(StructureBlockUpdatePacket $packet) : bool{
		return false;
	}

	public function handleStopSound(StopSoundPacket $packet) : bool{
		return false;
	}

	public function handleCommandBlockUpdate(CommandBlockUpdatePacket $packet) : bool{
		return false;
	}

	public function handleCommandOutput(CommandOutputPacket $packet) : bool{
		return false;
	}

	public function handleUpdateTrade(UpdateTradePacket $packet) : bool{
		return false;
	}

	public function handleUpdateEquip(UpdateEquipPacket $packet) : bool{
		return false;
	}

	public function handleShowCredits(ShowCreditsPacket $packet) : bool{
		return false;
	}

    public function handleCamera(CameraPacket $packet) : bool{
        return false;
    }

    public function handleCameraInstruction(CameraInstructionPacket $packet) : bool{
        return false;
    }

	public function handleSimpleEvent(SimpleEventPacket $packet) : bool{
		return false;
	}

	public function handleLegacyTelemetryEvent(LegacyTelemetryEventPacket $packet) : bool{
		return false;
	}

	public function handleGuiDataPickItem(GuiDataPickItemPacket $packet) : bool{
		return false;
	}

	public function handleSetHealth(SetHealthPacket $packet) : bool{
		return false;
	}

	public function handleActorFall(ActorFallPacket $packet) : bool{
		return false;
	}

	public function handleHurtArmor(HurtArmorPacket $packet) : bool{
		return false;
	}

	public function handleActorPickRequest(ActorPickRequestPacket $packet) : bool{
		return false;
	}

	public function handleLevelSoundEventPacketV1(LevelSoundEventPacketV1 $packet) : bool{
		return false;
	}

	public function handleRiderJump(RiderJumpPacket $packet) : bool{
		return false;
	}

	public function handleUpdateSoftEnum(UpdateSoftEnumPacket $packet) : bool{
		return false;
	}

	public function handleNetworkStackLatency(NetworkStackLatencyPacket $packet) : bool{
		return false;
	}

	public function handleScriptCustomEvent(ScriptCustomEventPacket $packet) : bool{
		return false;
	}

	public function handleLevelEventGeneric(LevelEventGenericPacket $packet) : bool{
		return false;
	}

	public function handleLecternUpdate(LecternUpdatePacket $packet) : bool{
		return false;
	}

	public function handleVideoStreamConnect(VideoStreamConnectPacket $packet) : bool{
		return false;
	}

	public function handleClientCacheStatus(ClientCacheStatusPacket $packet) : bool{
		return false;
	}

	public function handleOnScreenTextureAnimation(OnScreenTextureAnimationPacket $packet) : bool{
		return false;
	}

	public function handleMapCreateLockedCopy(MapCreateLockedCopyPacket $packet) : bool{
		return false;
	}

	public function handleStructureTemplateDataRequest(StructureTemplateDataRequestPacket $packet) : bool{
		return false;
	}

	public function handleStructureTemplateDataResponse(StructureTemplateDataResponsePacket $packet) : bool{
		return false;
	}

	public function handleUpdateBlockProperties(UpdateBlockPropertiesPacket $packet) : bool{
		return false;
	}

	public function handleClientCacheBlobStatus(ClientCacheBlobStatusPacket $packet) : bool{
		return false;
	}

	public function handleClientCacheMissResponse(ClientCacheMissResponsePacket $packet) : bool{
		return false;
	}

    public function handleEducationSettings(EducationSettingsPacket $packet) : bool{
        return false;
    }

    public function handleEditorNetwork(EditorNetworkPacket $packet) : bool{
        return false;
    }

	public function handleEmote(EmotePacket $packet) : bool{
		return false;
	}

	public function handleMultiplayerSettings(MultiplayerSettingsPacket $packet) : bool{
		return false;
	}

	public function handleSettingsCommand(SettingsCommandPacket $packet) : bool{
		return false;
	}

	public function handleAnvilDamage(AnvilDamagePacket $packet) : bool{
		return false;
	}

	public function handleCompletedUsingItem(CompletedUsingItemPacket $packet) : bool{
		return false;
	}

	public function handleNetworkSettings(NetworkSettingsPacket $packet) : bool{
		return false;
	}

	public function handlePlayerAuthInput(PlayerAuthInputPacket $packet) : bool{
		return false;
	}

	public function handleCreativeContent(CreativeContentPacket $packet) : bool{
		return false;
	}

	public function handlePlayerEnchantOptions(PlayerEnchantOptionsPacket $packet) : bool{
		return false;
	}

	public function handleItemStackRequest(ItemStackRequestPacket $packet) : bool{
		return false;
	}

	public function handleItemStackResponse(ItemStackResponsePacket $packet) : bool{
		return false;
	}

	public function handlePlayerArmorDamage(PlayerArmorDamagePacket $packet) : bool{
		return false;
	}

	public function handleCodeBuilder(CodeBuilderPacket $packet) : bool{
		return false;
	}

	public function handleUpdatePlayerGameType(UpdatePlayerGameTypePacket $packet) : bool{
		return false;
	}

	public function handleEmoteList(EmoteListPacket $packet) : bool{
		return false;
	}

	public function handlePositionTrackingDBServerBroadcast(PositionTrackingDBServerBroadcastPacket $packet) : bool{
		return false;
	}

	public function handlePositionTrackingDBClientRequest(PositionTrackingDBClientRequestPacket $packet) : bool{
		return false;
	}

    public function handleDebugInfo(DebugInfoPacket $packet) : bool{
        return false;
    }

    public function handleDeathInfo(DeathInfoPacket $packet) : bool{
        return false;
    }

	public function handlePacketViolationWarning(PacketViolationWarningPacket $packet) : bool{
		return false;
	}

	public function handleMotionPredictionHints(MotionPredictionHintsPacket $packet) : bool{
		return false;
	}

	public function handleAnimateEntity(AnimateEntityPacket $packet) : bool{
		return false;
	}

    public function handleCameraShake(CameraShakePacket $packet) : bool{
        return false;
    }

    public function handleCameraPresets(CameraPresetsPacket $packet) : bool{
        return false;
    }

	public function handlePlayerFog(PlayerFogPacket $packet) : bool{
		return false;
	}

	public function handleCorrectPlayerMovePrediction(CorrectPlayerMovePredictionPacket $packet) : bool{
		return false;
	}

	public function handleItemComponent(ItemComponentPacket $packet) : bool{
		return false;
	}

	public function handleFilterText(FilterTextPacket $packet) : bool{
		return false;
	}

	public function handleClientboundDebugRenderer(ClientboundDebugRendererPacket $packet) : bool{
		return false;
	}

	public function handleSyncActorProperty(SyncActorPropertyPacket $packet) : bool{
		return false;
	}

	public function handleAddVolumeEntity(AddVolumeEntityPacket $packet) : bool{
		return false;
	}

	public function handleRemoveVolumeEntity(RemoveVolumeEntityPacket $packet) : bool{
		return false;
	}

	public function handleSimulationType(SimulationTypePacket $packet) : bool{
	    return false;
	}

	public function handleNpcDialogue(NpcDialoguePacket $packet) : bool{
	    return false;
	}

	public function handleEduUriResource(EduUriResourcePacket $packet) : bool{
		return false;
	}

	public function handleCreatePhoto(CreatePhotoPacket $packet) : bool{
		return false;
	}

	public function handleUpdateSubChunkBlocks(UpdateSubChunkBlocksPacket $packet) : bool{
		return false;
	}

	public function handlePhotoInfoRequest(PhotoInfoRequestPacket $packet) : bool{
		return false;
	}

	public function handleCodeBuilderSource(CodeBuilderSourcePacket $packet) : bool{
		return false;
	}

	public function handlePlayerStartItemCooldown(PlayerStartItemCooldownPacket $packet) : bool{
		return false;
	}

	public function handleScriptMessage(ScriptMessagePacket $packet) : bool{
		return false;
	}

	public function handleDimensionData(DimensionDataPacket $packet) : bool{
		return false;
	}

	public function handleTickingAreasLoadStatus(TickingAreasLoadStatusPacket $packet) : bool{
		return false;
	}

	public function handleAgentActionEvent(AgentActionEventPacket $packet) : bool{
		return false;
	}

	public function handleChangeMobProperty(ChangeMobPropertyPacket $packet) : bool{
		return false;
	}

	public function handleLessonProgress(LessonProgressPacket $packet) : bool{
		return false;
	}

	public function handleRequestAbility(RequestAbilityPacket $packet) : bool{
		return false;
	}

	public function handleRequestPermissions(RequestPermissionsPacket $packet) : bool{
		return false;
	}

	public function handleToastRequest(ToastRequestPacket $packet) : bool{
		return false;
	}

	public function handleFeatureRegistry(FeatureRegistryPacket $packet) : bool{
		return false;
	}

	public function handleGameTestRequest(GameTestRequestPacket $packet) : bool{
		return false;
	}

	public function handleGameTestResults(GameTestResultsPacket $packet) : bool{
		return false;
	}

	public function handleServerStats(ServerStatsPacket $packet) : bool{
		return false;
	}

	public function handleUpdateClientInputLocks(UpdateClientInputLocksPacket $packet) : bool{
		return false;
	}

	public function handleClientCheatAbility(ClientCheatAbilityPacket $packet) : bool{
		return false;
	}

	public function handleCompressedBiomeDefinitionList(CompressedBiomeDefinitionListPacket $packet) : bool{
		return false;
	}

	public function handleTrimData(TrimDataPacket $packet) : bool{
		return false;
	}

	public function handleOpenSign(OpenSignPacket $packet) : bool{
		return false;
	}

	public function handleAgentAnimation(AgentAnimationPacket $packet) : bool{
		return false;
	}

	public function handleRefreshEntitlements(RefreshEntitlementsPacket $packet) : bool{
		return false;
	}

	public function handlePlayerToggleCrafterSlotRequest(PlayerToggleCrafterSlotRequestPacket $packet) : bool{
		return false;
	}

	public function handleSetPlayerInventoryOptions(SetPlayerInventoryOptionsPacket $packet) : bool{
		return false;
	}

	public function handleServerPlayerPostMovePosition(ServerPlayerPostMovePositionPacket $packet) : bool{
		return false;
	}

	public function handleSetHud(SetHudPacket $packet) : bool{
		return false;
	}

	public function handleAwardAchievement(AwardAchievementPacket $packet) : bool{
		return false;
	}

	public function handleClientboundCloseForm(ClientboundCloseFormPacket $packet) : bool{
		return false;
	}

    public function handleServerboundLoadingScreen(ServerboundLoadingScreenPacket $packet) : bool{
        return false;
    }

    public function handleJigsawStructureData(JigsawStructureDataPacket $packet) : bool{
        return false;
    }

    public function handleCurrentStructureFeature(CurrentStructureFeaturePacket $packet) : bool{
        return false;
    }

    public function handleServerboundDiagnostics(ServerboundDiagnosticsPacket $packet) : bool{
        return false;
    }

	public function handleCameraAimAssist(CameraAimAssistPacket $packet) : bool{
		return false;
	}

	public function handleContainerRegistryCleanup(ContainerRegistryCleanupPacket $packet) : bool{
		return false;
	}

	public function handleMovementEffect(MovementEffectPacket $packet) : bool{
		return false;
	}

	public function handleSetMovementAuthority(SetMovementAuthorityPacket $packet) : bool{
		return false;
	}

	public function handleItemRegistry(ItemRegistryPacket $packet) : bool{
		return false;
	}

	public function handleClientMovementPredictionSync(ClientMovementPredictionSyncPacket $packet) : bool{
		return false;
	}

	public function handleUpdateClientOptions(UpdateClientOptionsPacket $packet) : bool{
		return false;
	}

	public function handlePlayerVideoCapturePacket(PlayerVideoCapturePacket $packet) : bool{
		return false;
	}

	public function handlePlayerUpdateEntityOverridesPacket(PlayerUpdateEntityOverridesPacket $packet) : bool{
		return false;
	}

	public function handlePlayerLocation(PlayerLocationPacket $packet) : bool{
		return false;
	}

	public function handleClientboundControlSchemeSet(ClientboundControlSchemeSetPacket $packet) : bool{
		return false;
	}
}
