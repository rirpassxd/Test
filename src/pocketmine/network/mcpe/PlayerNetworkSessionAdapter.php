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

use InvalidArgumentException;
use pocketmine\entity\passive\AbstractHorse;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\maps\MapData;
use pocketmine\maps\MapManager;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\ActorFallPacket;
use pocketmine\network\mcpe\protocol\ActorPickRequestPacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\BlockPickRequestPacket;
use pocketmine\network\mcpe\protocol\BookEditPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\ClientCacheStatusPacket;
use pocketmine\network\mcpe\protocol\ClientToServerHandshakePacket;
use pocketmine\network\mcpe\protocol\CommandBlockUpdatePacket;
use pocketmine\network\mcpe\protocol\CommandRequestPacket;
use pocketmine\network\mcpe\protocol\CommandStepPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\network\mcpe\protocol\CraftingEventPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DropItemPacket;
use pocketmine\network\mcpe\protocol\EmoteListPacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemFrameDropItemPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacketV1;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MapInfoRequestPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\PlayerHotbarPacket;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RemoveBlockPacket;
use pocketmine\network\mcpe\protocol\RequestAbilityPacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\RequestNetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\RiderJumpPacket;
use pocketmine\network\mcpe\protocol\ServerSettingsRequestPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\SetPlayerInventoryOptionsPacket;
use pocketmine\network\mcpe\protocol\ShowCreditsPacket;
use pocketmine\network\mcpe\protocol\SpawnExperienceOrbPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\TickSyncPacket;
use pocketmine\network\mcpe\protocol\UseItemPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\timings\Timings;
use RuntimeException;
use function base64_encode;
use function bin2hex;
use function implode;
use function json_decode;
use function json_last_error_msg;
use function preg_match;
use function strlen;
use function substr;
use function trim;

class PlayerNetworkSessionAdapter extends NetworkSession{

	/** @var Server */
	private $server;
	/** @var Player */
	private $player;

	public function __construct(Server $server, Player $player){
		$this->server = $server;
		$this->player = $player;
	}

    public function isCompressionEnabled() : bool{
        return $this->player->isCompressionEnabled();
    }

	public function getProtocol() : int{
	    return $this->player->getProtocol();
	}

	public function handleDataPacket(DataPacket $packet){
		if(!$this->player->isConnected()){
			return;
		}

        if($this->player->getProtocol() < ProtocolInfo::PROTOCOL_130){
            //HACK: InteractPacket spam fix
            if($packet->buffer === "\x21\x04\x00"){
                return;
            }
        }

		$this->player->getGamePacketLimiter()->decrement();

		$timings = Timings::getReceiveDataPacketTimings($packet);
		$timings->startTiming();

		if(!$packet->wasDecoded and $packet->mustBeDecoded()){ //Allow plugins to decode it
	    	$packet->decode();
		    if(!$packet->feof() and !$packet->mayHaveUnreadBytes()){
		    	$remains = substr($packet->buffer, $packet->offset);
		    	$this->server->getLogger()->debug("Still " . strlen($remains) . " bytes unread in " . $packet->getName() . ": 0x" . bin2hex($remains));
			}
		}

		$ev = new DataPacketReceiveEvent($this->player, $packet);
		$ev->call();
		if(!$ev->isCancelled() and $packet->mustBeDecoded() and !$packet->handle($this)){
			$this->server->getLogger()->debug("Unhandled " . $packet->getName() . " received from " . $this->player->getName() . ": " . base64_encode($packet->buffer));
		}

		$timings->stopTiming();
	}

    public function handleRequestNetworkSettings(RequestNetworkSettingsPacket $packet) : bool{
        return $this->player->handleRequestNetworkSettings($packet);
    }

	public function handleLogin(LoginPacket $packet) : bool{
		return $this->player->handleLogin($packet);
	}

	public function handleClientToServerHandshake(ClientToServerHandshakePacket $packet) : bool{
		return $this->player->onEncryptionHandshake();
	}

	public function handleResourcePackClientResponse(ResourcePackClientResponsePacket $packet) : bool{
		return $this->player->handleResourcePackClientResponse($packet);
	}

	public function handleText(TextPacket $packet) : bool{
		if($packet->type === TextPacket::TYPE_CHAT){
			return $this->player->chat($packet->message);
		}

		return false;
	}

	public function handleMovePlayer(MovePlayerPacket $packet) : bool{
		return $this->player->handleMovePlayer($packet);
	}

	public function handlePlayerAuthInput(PlayerAuthInputPacket $packet) : bool{
		return $this->player->handlePlayerAuthInput($packet);
	}

	public function handleLevelSoundEventPacketV1(LevelSoundEventPacketV1 $packet) : bool{
		return true; //useless leftover from 1.8
	}

	public function handleActorEvent(ActorEventPacket $packet) : bool{
		return $this->player->handleEntityEvent($packet);
	}

	public function handleInventoryTransaction(InventoryTransactionPacket $packet) : bool{
		return $this->player->handleInventoryTransaction($packet);
	}

	public function handleMobEquipment(MobEquipmentPacket $packet) : bool{
		return $this->player->handleMobEquipment($packet);
	}

	public function handleMobArmorEquipment(MobArmorEquipmentPacket $packet) : bool{
		return true; //Not used
	}

	public function handleTickSync(TickSyncPacket $packet) : bool{
		return true; //Not used
	}

	public function handleEmoteList(EmoteListPacket $packet) : bool{
		return true; // Not used
	}

	public function handleEmote(EmotePacket $packet) : bool{
		return $this->player->handleEmote($packet);
	}

	public function handleInteract(InteractPacket $packet) : bool{
		return $this->player->handleInteract($packet);
	}

	public function handleBlockPickRequest(BlockPickRequestPacket $packet) : bool{
		return $this->player->handleBlockPickRequest($packet);
	}

	public function handleActorPickRequest(ActorPickRequestPacket $packet) : bool{
		return false; //TODO
	}

	public function handlePlayerAction(PlayerActionPacket $packet) : bool{
		return $this->player->handlePlayerAction($packet);
	}

	public function handleActorFall(ActorFallPacket $packet) : bool{
		return true; //Not used
	}

	public function handleAnimate(AnimatePacket $packet) : bool{
		return $this->player->handleAnimate($packet);
	}

	public function handleRespawn(RespawnPacket $packet) : bool{
		return $this->player->handleRespawn($packet);
	}

	public function handleContainerClose(ContainerClosePacket $packet) : bool{
		return $this->player->handleContainerClose($packet);
	}

	public function handlePlayerHotbar(PlayerHotbarPacket $packet) : bool{
		return true; //this packet is useless
	}

	public function handleCraftingEvent(CraftingEventPacket $packet) : bool{
	    if($this->player->getProtocol() < ProtocolInfo::PROTOCOL_130){
	    	return $this->player->handleCraftingEvent($packet); // only <= 1.1
	    }

	    return true;
	}

	public function handleClientCacheStatus(ClientCacheStatusPacket $packet) : bool{
		return true; // Not used
	}

	public function handleAdventureSettings(AdventureSettingsPacket $packet) : bool{
		return $this->player->handleAdventureSettings($packet);
	}

	public function handleBlockActorData(BlockActorDataPacket $packet) : bool{
		return $this->player->handleBlockEntityData($packet);
	}

	public function handlePlayerInput(PlayerInputPacket $packet) : bool{
		$this->player->setMoveForward($packet->motionY);
		$this->player->setMoveStrafing($packet->motionX);

		return true;
	}

	public function handleRiderJump(RiderJumpPacket $packet) : bool{
		if($this->player->isRiding()){
			$horse = $this->player->getRidingEntity();
			if($horse instanceof AbstractHorse){
				$horse->setJumpPower($packet->jumpStrength);

				return true;
			}
		}
		return false;
	}

	public function handleSetPlayerGameType(SetPlayerGameTypePacket $packet) : bool{
		return $this->player->handleSetPlayerGameType($packet);
	}

	public function handleSetPlayerInventoryOptions(SetPlayerInventoryOptionsPacket $packet) : bool{
		return true; //debug spam
	}

	public function handleSpawnExperienceOrb(SpawnExperienceOrbPacket $packet) : bool{
		return false; //TODO
	}

	public function handleMapInfoRequest(MapInfoRequestPacket $packet) : bool{
		$data = MapManager::getMapDataById($packet->mapId);
		if($data instanceof MapData){
			// this is for first appearance
			$pk = new ClientboundMapItemDataPacket();
			$pk->originX = $pk->originY = $pk->originZ = 0;
			$pk->height = $pk->width = 128;
			$pk->dimensionId = $data->getDimension();
			$pk->scale = $data->getScale();
			$pk->colors = $data->getColors();
			$pk->mapId = $data->getId();
			$pk->decorations = $data->getDecorations();
			$pk->trackedEntities = $data->getTrackedObjects();
			if($this->player->getProtocol() >= ProtocolInfo::PROTOCOL_135){
				$pk->eids[] = $data->getId();
			}

			$this->player->sendDataPacket($pk);

			return true;
		}
		return false;
	}

	public function handleRequestAbility(RequestAbilityPacket $packet) : bool{
		return $this->player->handleRequestAbility($packet);
	}

	public function handleRequestChunkRadius(RequestChunkRadiusPacket $packet) : bool{
		$this->player->setViewDistance($packet->radius);

		return true;
	}

	public function handleItemFrameDropItem(ItemFrameDropItemPacket $packet) : bool{
		return $this->player->handleItemFrameDropItem($packet);
	}

	public function handleBossEvent(BossEventPacket $packet) : bool{
		return false; //TODO
	}

	public function handleShowCredits(ShowCreditsPacket $packet) : bool{
		return false; //TODO: handle resume
	}

	public function handleCommandRequest(CommandRequestPacket $packet) : bool{
		return $this->player->chat($packet->command);
	}

	public function handleCommandBlockUpdate(CommandBlockUpdatePacket $packet) : bool{
		return false; //TODO
	}

	public function handleCommandStep(CommandStepPacket $packet) : bool{
		return $this->player->handleCommandStep($packet);
	}

	public function handleContainerSetSlot(ContainerSetSlotPacket $packet) : bool{
		return $this->player->handleContainerSetSlot($packet);
	}

	public function handleDropItem(DropItemPacket $packet) : bool{
		return $this->player->handleDropItem($packet);
	}

	public function handleRemoveBlock(RemoveBlockPacket $packet) : bool{
		return $this->player->removeBlock(new Vector3($packet->x, $packet->y, $packet->z));
	}

	public function handleUseItem(UseItemPacket $packet) : bool{
		return $this->player->handleUseItem($packet);
	}

	public function handleResourcePackChunkRequest(ResourcePackChunkRequestPacket $packet) : bool{
		return $this->player->handleResourcePackChunkRequest($packet);
	}

	public function handlePlayerSkin(PlayerSkinPacket $packet) : bool{
		return $this->player->changeSkin($packet->skin, $packet->newSkinName, $packet->oldSkinName);
	}

	public function handleBookEdit(BookEditPacket $packet) : bool{
		return $this->player->handleBookEdit($packet);
	}

	public function handleModalFormResponse(ModalFormResponsePacket $packet) : bool{
		if($packet->cancelReason !== null){
			//TODO: make APIs for this to allow plugins to use this information
			return $this->player->onFormSubmit($packet->formId, null);
		}elseif($packet->formData !== null){
			return $this->player->onFormSubmit($packet->formId, self::stupid_json_decode($packet->formData, true));
		}else{
			throw new RuntimeException("Expected either formData or cancelReason to be set in ModalFormResponsePacket");
		}
	}

	/**
	 * Hack to work around a stupid bug in Minecraft W10 which causes empty strings to be sent unquoted in form responses.
	 *
	 * @param string $json
	 * @param bool   $assoc
	 *
	 * @return mixed
	 */
	private static function stupid_json_decode(string $json, bool $assoc = false){
		if(preg_match('/^\[(.+)\]$/s', $json, $matches) > 0){
			$raw = $matches[1];
			$lastComma = -1;
			$newParts = [];
			$quoteType = null;
			for($i = 0, $len = strlen($raw); $i <= $len; ++$i){
				if($i === $len or ($raw[$i] === "," and $quoteType === null)){
					$part = substr($raw, $lastComma + 1, $i - ($lastComma + 1));
					if(trim($part) === ""){ //regular parts will have quotes or something else that makes them non-empty
						$part = '""';
					}
					$newParts[] = $part;
					$lastComma = $i;
				}elseif($raw[$i] === '"'){
					if($quoteType === null){
						$quoteType = $raw[$i];
					}elseif($raw[$i] === $quoteType){
						for($backslashes = 0; $backslashes < $i && $raw[$i - $backslashes - 1] === "\\"; ++$backslashes){}
						if(($backslashes % 2) === 0){ //unescaped quote
							$quoteType = null;
						}
					}
				}
			}

			$fixed = "[" . implode(",", $newParts) . "]";
			if(($ret = json_decode($fixed, $assoc)) === null){
				throw new InvalidArgumentException("Failed to fix JSON: " . json_last_error_msg() . "(original: $json, modified: $fixed)");
			}

			return $ret;
		}

		return json_decode($json, $assoc);
	}

	public function handleServerSettingsRequest(ServerSettingsRequestPacket $packet) : bool{
		return true; //TODO: GUI stuff
	}

	public function handleSetLocalPlayerAsInitialized(SetLocalPlayerAsInitializedPacket $packet) : bool{
		$this->player->doFirstSpawn();
		return true;
	}

	public function handleLevelSoundEvent(LevelSoundEventPacket $packet) : bool{
		return $this->player->handleLevelSoundEvent($packet);
	}

	public function handleMoveActorAbsolute(MoveActorAbsolutePacket $packet) : bool{
		$target = $this->player->getServer()->findEntity($packet->entityRuntimeId);
		if($target !== null){
			$target->setClientPositionAndRotation($packet->position, $packet->yaw, $packet->pitch, 3, ($packet->flags & MoveActorAbsolutePacket::FLAG_TELEPORT) !== 0);
			//$target->onGround = ($packet->flags & MoveActorAbsolutePacket::FLAG_GROUND) !== 0;

		    return true;
		}

		return false;
	}

	public function handleSetActorMotion(SetActorMotionPacket $packet) : bool{
		return true;
	}

	public function handleNetworkStackLatency(NetworkStackLatencyPacket $packet) : bool{
		return true; //TODO: implement this properly - this is here to silence debug spam from MCPE dev builds
	}
}
