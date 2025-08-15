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

use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\multiversion\block\BlockPalette;
use pocketmine\network\mcpe\multiversion\inventory\ItemPalette;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\BlockPaletteEntry;
use pocketmine\network\mcpe\protocol\types\ChatRestrictionLevel;
use pocketmine\network\mcpe\protocol\types\EditorWorldType;
use pocketmine\network\mcpe\protocol\types\EducationEditionOffer;
use pocketmine\network\mcpe\protocol\types\EducationUriResource;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\GameRuleType;
use pocketmine\network\mcpe\protocol\types\GeneratorType;
use pocketmine\network\mcpe\protocol\types\MultiplayerGameVisibility;
use pocketmine\network\mcpe\protocol\types\NetworkPermissions;
use pocketmine\network\mcpe\protocol\types\PlayerMovementSettings;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\types\SpawnSettings;
use pocketmine\utils\UUID;
use UnexpectedValueException;
use function count;
use function file_get_contents;
use function json_decode;
use function ord;
use function pack;
use const pocketmine\RESOURCE_PATH;

class StartGamePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::START_GAME_PACKET;

	/** @var string|null */
	private static $itemTableCache = null;

	/** @var int */
	public $entityUniqueId;
	/** @var int */
	public $entityRuntimeId;
	/** @var int */
	public $playerGamemode;

	/** @var Vector3 */
	public $playerPosition;

	/** @var float */
	public $pitch;
	/** @var float */
	public $yaw;

	/** @var int */
	public $seed;
	/** @var SpawnSettings */
	public $spawnSettings;
	/** @var int */
	public $generator = GeneratorType::OVERWORLD;
	/** @var int */
	public $worldGamemode;
	/** @var bool */
	public $hardcore = false;
	/** @var int */
	public $difficulty;
	/** @var int */
	public $spawnX;
	/** @var int */
	public $spawnY;
	/** @var int */
	public $spawnZ;
	/** @var bool */
	public $hasAchievementsDisabled = true;
	/** @var bool */
	public $isEditorMode = false;
	/** @var int */
	public $editorWorldType = EditorWorldType::NON_EDITOR;
	/** @var bool */
	public $createdInEditorMode = false;
	/** @var bool */
	public $exportedFromEditorMode = false;
	/** @var int */
	public $time = -1;
	/** @var bool */
	public $dayCycleStopTime = false;
	/** @var int */
	public $eduEditionOffer = EducationEditionOffer::NONE;
	/** @var bool */
	public $eduMode = false;
	/** @var bool */
	public $hasEduFeaturesEnabled = false;
	/** @var string */
	public $eduProductUUID = "";
	/** @var float */
	public $rainLevel;
	/** @var float */
	public $lightningLevel;
	/** @var bool */
	public $hasConfirmedPlatformLockedContent = false;
	/** @var bool */
	public $isMultiplayerGame = true;
	/** @var bool */
	public $hasLANBroadcast = true;
	/** @var bool */
	public $hasXboxLiveBroadcast = false;
	/** @var int */
	public $xboxLiveBroadcastMode = MultiplayerGameVisibility::PUBLIC;
	/** @var int */
	public $platformBroadcastMode = MultiplayerGameVisibility::PUBLIC;
	/** @var bool */
	public $commandsEnabled;
	/** @var bool */
	public $isTexturePacksRequired = true;
	/** @var array */
	public $gameRules = [ //TODO: implement this
		"naturalregeneration" => [GameRuleType::BOOL, false, false] //Hack for client side regeneration
	];
	/** @var Experiments */
	public $experiments;
	/** @var bool */
	public $hasBonusChestEnabled = false;
	/** @var bool */
	public $hasStartWithMapEnabled = false;
	/** @var bool */
	public $hasTrustPlayersEnabled = false;
	/** @var int */
	public $defaultPlayerPermission = PlayerPermissions::MEMBER; //TODO

	/** @var int */
	public $serverChunkTickRadius = 4; //TODO (leave as default for now)

	/** @var bool */
	public $hasPlatformBroadcast = false;
	/** @var bool */
	public $xboxLiveBroadcastIntent = false;
	/** @var bool */
	public $hasLockedBehaviorPack = false;
	/** @var bool */
	public $hasLockedResourcePack = false;
	/** @var bool */
	public $isFromLockedWorldTemplate = false;
	/** @var bool */
	public $useMsaGamertagsOnly = false;
	/** @var bool */
	public $isFromWorldTemplate = false;
	/** @var bool */
	public $isWorldTemplateOptionLocked = false;
	/** @var bool */
	public $onlySpawnV1Villagers = false;
	/** @var bool */
	public $disablePersona = false;
	/** @var bool */
	public $disableCustomSkins = false;
	/** @var bool */
	public $muteEmoteAnnouncements = false;
	/** @var string */
	public $vanillaVersion = ProtocolInfo::MINECRAFT_VERSION_NETWORK;
	/** @var int */
	public $limitedWorldWidth = 0;
	/** @var int */
	public $limitedWorldLength = 0;
	/** @var bool */
	public $isNewNether = false;
	/** @var EducationUriResource|null */
	public $eduSharedUriResource = null;
	/** @var bool|null */
	public $experimentalGameplayOverride = null;
	/** @var int */
	public $chatRestrictionLevel = ChatRestrictionLevel::NONE;
	/** @var bool */
	public $disablePlayerInteractions = false;
	/** @var string */
	public $serverIdentifier = "";
	/** @var string */
	public $worldIdentifier = "";
	/** @var string */
	public $scenarioIdentifier = "";

	/** @var string */
	public $levelId = ""; //base64 string, usually the same as world folder name in vanilla
	/** @var string */
	public $worldName;
	/** @var string */
	public $premiumWorldTemplateId = "";
	/** @var bool */
	public $isTrial = false;
	/** @var PlayerMovementSettings */
	public $playerMovementSettings;
	/** @var bool */
	public $isMovementServerAuthoritative = false;
	/** @var int */
	public $currentTick = 0; //only used if isTrial is true
	/** @var int */
	public $enchantmentSeed = 0;
	/** @var string */
	public $multiplayerCorrelationId = ""; //TODO: this should be filled with a UUID of some sort
	/** @var bool */
	public $enableNewInventorySystem = false; //TODO
	/** @var string */
	public $serverSoftwareVersion;
	/** @var CompoundTag */
	public $playerActorProperties;
	/** @var int */
	public $blockPaletteChecksum;
    /** @var UUID */
    public $worldTemplateId;
    /** @var bool */
    public $enableClientSideChunkGeneration = false;
	/** @var bool */
	public $blockNetworkIdsAreHashes = false; //new in 1.19.80, possibly useful for multi version
	/** @var NetworkPermissions */
	public $networkPermissions;

	/** @var ListTag|array|null ["name" (string), "data" (int16), "legacy_id" (int16)] */
	public $blockTable = null;
	/** @var BlockPaletteEntry[] */
	public $blockPalette = [];
	/** @var array|null string (name) => int16 (legacyID) */
	public $itemTable = null;

	protected function decodePayload(){
	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->entityUniqueId = $this->getEntityUniqueId();
	    	$this->entityRuntimeId = $this->getEntityRuntimeId();
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
	        	$this->playerGamemode = $this->getVarInt();
	    	}

	    	$this->playerPosition = $this->getVector3();

	    	$this->pitch = $this->getLFloat();
	    	$this->yaw = $this->getLFloat();

	    	//Level settings
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_503){
		        $this->seed = $this->getLLong();
	    	}else{
	        	$this->seed = $this->getVarInt();
	    	}
	    	if($this->getProtocol() === ProtocolInfo::PROTOCOL_400){
		        $this->get(1);
		        $this->get(1);
		        $this->getString();
	    	}
	    	$this->spawnSettings = SpawnSettings::read($this);
	    	$this->generator = $this->getVarInt();
	    	$this->worldGamemode = $this->getVarInt();
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_671){
                $this->hardcore = $this->getBool();
	    	}
	    	$this->difficulty = $this->getVarInt();
	    	$this->getBlockPosition($this->spawnX, $this->spawnY, $this->spawnZ);
            $this->hasAchievementsDisabled = $this->getBool();
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_534){
		    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_618){
			    	$this->editorWorldType = $this->getVarInt();
		    	}else{
                    $this->isEditorMode = $this->getBool();
		    	}
		    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_582){
                    $this->createdInEditorMode = $this->getBool();
                    $this->exportedFromEditorMode = $this->getBool();
		    	}
	    	}
	    	$this->time = $this->getVarInt();
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_370){
		        if($this->getProtocol() === ProtocolInfo::PROTOCOL_400){
		            $this->get(1);
		        }
		        $this->eduEditionOffer = $this->getVarInt();
	    	}else{
                $this->eduMode = $this->getBool();
	    	}
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_260 && $this->vanillaVersion != '1.2.20.1'){
                $this->hasEduFeaturesEnabled = $this->getBool();
	        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_401){
	            	$this->eduProductUUID = $this->getString();
	        	}
	    	}
	    	$this->rainLevel = $this->getLFloat();
	    	$this->lightningLevel = $this->getLFloat();
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_332){
                $this->hasConfirmedPlatformLockedContent = $this->getBool();
	    	}
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
                $this->isMultiplayerGame = $this->getBool();
                $this->hasLANBroadcast = $this->getBool();
	        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_330){
	            	$this->xboxLiveBroadcastMode = $this->getVarInt();
	                $this->platformBroadcastMode = $this->getVarInt();
	            	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392 && $this->getProtocol() < ProtocolInfo::PROTOCOL_400){
	        	        $this->get(1);
	            	}
	        	} else {
                    $this->hasXboxLiveBroadcast = $this->getBool();
	        	}
	    	}
	    	$this->commandsEnabled = $this->getBool();
	    	$this->isTexturePacksRequired = $this->getBool();
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
		    	$this->gameRules = $this->getGameRules();
		    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
         	       if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
                	   $this->experiments = Experiments::read($this);
       		        }
	    	    	$this->hasBonusChestEnabled = $this->getBool();
	    	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_132){
	        	    	$this->hasStartWithMapEnabled = $this->getBool();
	    	    	}
	    	    	if($this->getProtocol() < ProtocolInfo::PROTOCOL_330){
			            $this->hasTrustPlayersEnabled = $this->getBool();
	   		     	}
	    	    	$this->defaultPlayerPermission = $this->getVarInt();
	    	    	if($this->getProtocol() < ProtocolInfo::PROTOCOL_330){
		    	        $this->xboxLiveBroadcastMode = $this->getVarInt();
	    	    	}
	    	    	if($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200){
	        	    	$this->serverChunkTickRadius = $this->getLInt();
	    	    	}
	    	    	if($this->getProtocol() < ProtocolInfo::PROTOCOL_330){
		    	        $this->hasPlatformBroadcast = $this->getBool();
		   		        $this->platformBroadcastMode = $this->getVarInt();
		    	        $this->xboxLiveBroadcastIntent = $this->getBool();
	    	    	}
                	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_260 && $this->vanillaVersion != '1.2.20.1'){
	        	    	$this->hasLockedBehaviorPack = $this->getBool();
	    	        	$this->hasLockedResourcePack = $this->getBool();
	        	    	$this->isFromLockedWorldTemplate = $this->getBool();
	        	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_290){
	    	            	$this->useMsaGamertagsOnly = $this->getBool();
		           	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_311){
	                	    	$this->isFromWorldTemplate = $this->getBool();
	                	    	$this->isWorldTemplateOptionLocked = $this->getBool();
	                	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_361){
	                    	    	$this->onlySpawnV1Villagers = $this->getBool();
	                    	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_544){
	                    	        	$this->disablePersona = $this->getBool();
	                    	        	$this->disableCustomSkins = $this->getBool();
	                    	        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_567){
	                    	            	$this->muteEmoteAnnouncements = $this->getBool();
	                    	        	}
	                    	    	}
	                    	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_370){
	                        	    	$this->vanillaVersion = $this->getString();
	                        	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392){
	                            	    	$this->limitedWorldWidth = $this->getLInt();
	                            	    	$this->limitedWorldLength = $this->getLInt();
	                            	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_400){
	                                		    $this->isNewNether = $this->getBool();
	                                    		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_407){
	                                	    	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_465){
	                                	        	    $this->eduSharedUriResource = EducationUriResource::read($this);
	                                	    	    }
	                                	    	    if($this->getBool()){
	                                	    	    	$this->experimentalGameplayOverride = $this->getBool();
	                                	    	    }else{
	                                	    	    	$this->experimentalGameplayOverride = null;
	                                	    	    }
	                                	    	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_544){
	                                	           	$this->chatRestrictionLevel = ord($this->get(1));
	                                	           	$this->disablePlayerInteractions = $this->getBool();
												    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_685){
													    	$this->serverIdentifier = $this->getString();
													    	$this->worldIdentifier = $this->getString();
													    	$this->scenarioIdentifier = $this->getString();
												    	}
	                                	    	    }
	                                    		}
	                                		}
	                        	    	}else{
	                            	    	if($this->getProtocol() === ProtocolInfo::PROTOCOL_386 || $this->getProtocol() === ProtocolInfo::PROTOCOL_387){
	        	                     	        $this->get(1);
	        	                        	    $this->get(1);
	        	                        	    $this->getLFloat();
									    	}
	                                	}
	                            	}
	                        	}
	                    	}
		                }
	            	}
                }
	    	}

	        $this->levelId = $this->getString();
	        $this->worldName = $this->getString();
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
	            $this->premiumWorldTemplateId = $this->getString();
	         	$this->isTrial = $this->getBool();
	   	     	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_388){
	   	         	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
	   	            	$this->playerMovementSettings = PlayerMovementSettings::read($this);
	   	         	}else{
	         	    	$this->isMovementServerAuthoritative = $this->getBool();
	   	        	}
	   	    	}
	        	 $this->currentTick = $this->getLLong();
	    	}

            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
	            $this->enchantmentSeed = $this->getVarInt();

                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_280){
                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
	                	$this->blockPalette = [];
	                	for($i = 0, $len = $this->getUnsignedVarInt(); $i < $len; ++$i){
		                 	$blockName = $this->getString();
		                	$state = $this->getNbtCompoundRoot();
		                	$this->blockPalette[] = new BlockPaletteEntry($blockName, $state);
	                	}
                    }elseif($this->getProtocol() >= ProtocolInfo::PROTOCOL_370){
	                	$blockTable = (new NetworkLittleEndianNBTStream())->read($this->buffer, false, $this->offset, 512);
	                	if(!($blockTable instanceof ListTag)){
		                	throw new UnexpectedValueException("Wrong block table root NBT tag type");
	                	}
	                	$this->blockTable = $blockTable;
                    }else{
	                	$this->blockTable = [];
	                	for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
		                	$id = $this->getString();
		                	$data = $this->getSignedLShort();
		                	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_360){
		                    	$legacy_id = $this->getSignedLShort();
			                }

		                	$this->blockTable[$i] = ["name" => $id, "data" => $data, "legacy_id" => $legacy_id ?? 0];
	                	}
	            	}
	            	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_360 && $this->getProtocol() < ProtocolInfo::PROTOCOL_776){
		                $this->itemTable = [];
	                	for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
		                	$id = $this->getString();
		                	$legacyId = $this->getSignedLShort();
		                	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
		                    	$this->getByte();
		                	}

		                	$this->itemTable[$id] = $legacyId;
	                	}
	            	}
                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_282){
	                	$this->multiplayerCorrelationId = $this->getString();
	                	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392){
	        	            $this->enableNewInventorySystem = $this->getBool();
	        	            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_440){
	        	                $this->serverSoftwareVersion = $this->getString();
	        	                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_526){
	        	                    $this->playerActorProperties = $this->getNbtCompoundRoot();
	        	                }
						    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_475){
							    	$this->blockPaletteChecksum = $this->getLLong();
							    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_526){
								        $this->worldTemplateId = $this->getUUID();
								        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_544){
								            $this->enableClientSideChunkGeneration = $this->getBool();
									    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_582){
									    		$this->blockNetworkIdsAreHashes = $this->getBool();
								    			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_589){
									   			$this->networkPermissions = NetworkPermissions::decode($this);
									    		}
									    	}
								        }
							    	}
						    	}
	        	            }
	                	}
	            	}
                }
            }
	    }else{
	        $this->seed = $this->getInt();
	    	$this->spawnSettings = SpawnSettings::read($this);
		    $this->generator = $this->getInt();
		    $this->worldGamemode = $this->getInt();
	    	$this->entityRuntimeId = $this->getEntityRuntimeId();
	    	$this->spawnX = $this->getInt();
	    	$this->spawnY = $this->getInt();
	    	$this->spawnZ = $this->getInt();
	    	$this->playerPosition = $this->getVector3();
	    	$this->hasAchievementsDisabled = $this->getBool();
	    	$this->dayCycleStopTime = $this->getBool();
	    	$this->eduMode = $this->getBool();
	    	$this->levelId = $this->getShortString();
	    }
	}

	protected function encodePayload(){
	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->putEntityUniqueId($this->entityUniqueId);
	    	$this->putEntityRuntimeId($this->entityRuntimeId);
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
		        $this->putVarInt($this->playerGamemode);
	    	}

	    	$this->putVector3($this->playerPosition);

            $this->putLFloat($this->pitch);
            $this->putLFloat($this->yaw);

	    	//Level settings
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_503){
	    	    $this->putLLong($this->seed);
	    	}else{
	        	$this->putVarInt($this->seed);
	    	}
	    	if($this->getProtocol() === ProtocolInfo::PROTOCOL_400){
                $this->putByte(0);
                $this->putByte(0);
                $this->putString("");
	    	}
	        $this->spawnSettings->write($this);
	    	$this->putVarInt($this->generator);
	    	$this->putVarInt($this->worldGamemode);
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_671){
                $this->putBool($this->hardcore);
		    }
		    $this->putVarInt($this->difficulty);
		    $this->putBlockPosition($this->spawnX, $this->spawnY, $this->spawnZ);
            $this->putBool($this->hasAchievementsDisabled);
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_534){
		    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_618){
			    	$this->putVarInt($this->editorWorldType);
		    	}else{
                    $this->putBool($this->isEditorMode);
		    	}
		    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_582){
                    $this->putBool($this->createdInEditorMode);
                    $this->putBool($this->exportedFromEditorMode);
		    	}
	    	}

		    $this->putVarInt($this->time);
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_370){
		        if($this->getProtocol() === ProtocolInfo::PROTOCOL_400){
                    $this->putByte(0);
		        }
		        $this->putVarInt($this->eduEditionOffer);
	    	}else{
                $this->putBool($this->eduMode);
	    	}
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_260 && $this->vanillaVersion != '1.2.20.1'){
                $this->putBool($this->hasEduFeaturesEnabled);
	        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_401){
	    	        $this->putString($this->eduProductUUID);
	        	}
	    	}
	    	($this->buffer .= (pack("g", $this->rainLevel)));
	    	($this->buffer .= (pack("g", $this->lightningLevel)));
		    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_332){
                $this->putBool($this->hasConfirmedPlatformLockedContent);
		    }
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
                $this->putBool($this->isMultiplayerGame);
                $this->putBool($this->hasLANBroadcast);
	        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_330){
	            	$this->putVarInt($this->xboxLiveBroadcastMode);
		            $this->putVarInt($this->platformBroadcastMode);
	            	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392 && $this->getProtocol() < ProtocolInfo::PROTOCOL_400){
                        $this->putByte(0);
	            	}
	        	} else {
                    $this->putBool($this->hasXboxLiveBroadcast);
	        	}
	    	}
            $this->putBool($this->commandsEnabled);
            $this->putBool($this->isTexturePacksRequired);
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
		        $this->putGameRules($this->gameRules);
		        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
    		        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
	    	            $this->experiments->write($this);
		            }
                    $this->putBool($this->hasBonusChestEnabled);
    	        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_132){
                        $this->putBool($this->hasStartWithMapEnabled);
	            	}
    	        	if($this->getProtocol() < ProtocolInfo::PROTOCOL_330){
                        $this->putBool($this->hasTrustPlayersEnabled);
	            	}
    	        	$this->putVarInt($this->defaultPlayerPermission);
	            	if($this->getProtocol() < ProtocolInfo::PROTOCOL_330){
		                $this->putVarInt($this->xboxLiveBroadcastMode);
	    	        }
	    	        if($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200){
                        $this->putLInt($this->serverChunkTickRadius);
	    	        }
	    	        if($this->getProtocol() < ProtocolInfo::PROTOCOL_330){
                        $this->putBool($this->hasPlatformBroadcast);
	        	        $this->putVarInt($this->platformBroadcastMode);
                        $this->putBool($this->xboxLiveBroadcastIntent);
	    	        }
	    	        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_260 && $this->vanillaVersion != '1.2.20.1'){
                        $this->putBool($this->hasLockedBehaviorPack);
                        $this->putBool($this->hasLockedResourcePack);
                        $this->putBool($this->isFromLockedWorldTemplate);
	                	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_290){
                            $this->putBool($this->useMsaGamertagsOnly);
	                    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_311){
                                $this->putBool($this->isFromWorldTemplate);
                                $this->putBool($this->isWorldTemplateOptionLocked);
    	                    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_361){
                                    $this->putBool($this->onlySpawnV1Villagers);
	                            	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_544){
                                        $this->putBool($this->disablePersona);
                                        $this->putBool($this->disableCustomSkins);
	                        	        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_567){
                                            $this->putBool($this->muteEmoteAnnouncements);
	                    	            }
	                    	        }
    	                        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_370){
	            	                    $this->putString($this->vanillaVersion);
                                        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392){
                                            $this->putLInt($this->limitedWorldWidth);
                                            $this->putLInt($this->limitedWorldLength);
	                                    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_400){
                                                $this->putBool($this->isNewNether);
	                                        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_407){
	                                    	        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_465){
	                                    	            ($this->eduSharedUriResource ?? new EducationUriResource("", ""))->write($this);
	                                	            }
                                                    $this->putBool($this->experimentalGameplayOverride !== null);
    	                                	        if($this->experimentalGameplayOverride !== null){
                                                        $this->putBool($this->experimentalGameplayOverride);
	                                    	        }
	                                    	        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_544){
                                                        $this->putByte($this->chatRestrictionLevel);
                                                        $this->putBool($this->disablePlayerInteractions);
						    					    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_685){
							    					    	$this->putString($this->serverIdentifier);
								    				    	$this->putString($this->worldIdentifier);
									    			    	$this->putString($this->scenarioIdentifier);
										    	    	}
	                                	            }
	                                        	}
                                            }
                                        }else{
	                                    	if($this->getProtocol() === ProtocolInfo::PROTOCOL_386 || $this->getProtocol() === ProtocolInfo::PROTOCOL_387){
                                               $this->putByte(0);
                                               $this->putByte(1);
			                                	$this->putLFloat(0);
	                            	        }
								    	}
                                    }
	                        	}
	                    	}
	                	}
	             	}
	        	}
            }

		    $this->putString($this->levelId);
		    $this->putString($this->worldName);
		    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
                $this->putString($this->premiumWorldTemplateId);
                $this->putBool($this->isTrial);
		        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_388){
		            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
		                $this->playerMovementSettings->write($this);
		            }else{
                        $this->putBool($this->isMovementServerAuthoritative);
			    	}
		        }
                $this->putLLong($this->currentTick);
		    }
            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
	        	$this->putVarInt($this->enchantmentSeed);

                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_280){
                    if($this->getProtocol() < ProtocolInfo::PROTOCOL_419){
                        $palette = BlockPalette::getPalette($this->getProtocol());
                        $this->put($palette::getEncodedPalette());
                    }else{
	                	$this->putUnsignedVarInt(count($this->blockPalette));
	                	$nbtWriter = new NetworkLittleEndianNBTStream();
	                	foreach($this->blockPalette as $entry){
		                	$this->putString($entry->getName());
		                	$this->put($nbtWriter->write($entry->getStates()));
	                	}
                    }
	            	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_360 && $this->getProtocol() < ProtocolInfo::PROTOCOL_776){
	        	        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
	        	            $palette = ItemPalette::getPalette($this->getProtocol());
	        	            $this->put($palette::getEncodedPalette());
	        	        }else{
	            	        if($this->itemTable === null){
		                    	if(self::$itemTableCache === null){
			                    	self::$itemTableCache = self::serializeItemTable(json_decode(file_get_contents(RESOURCE_PATH . '/vanilla/item_id_map.json'), true));
		                     	}
                                $this->put(self::$itemTableCache);
	                    	}else{
                                $this->put(self::serializeItemTable($this->itemTable));
	                    	}
	        	        }
		            }
                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_282){
	    	            $this->putString($this->multiplayerCorrelationId);
	    	            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392){
                            $this->putBool($this->enableNewInventorySystem);
	    	                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_440){
	    	                    $this->putString($this->serverSoftwareVersion);
	    	                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_526){
	    	                        $this->put((new NetworkLittleEndianNBTStream())->write($this->playerActorProperties));
	    	                    }
						    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_475){
							    	$this->putLLong($this->blockPaletteChecksum);
							    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_526){
								        $this->putUUID($this->worldTemplateId);
								        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_544){
                                            $this->putBool($this->enableClientSideChunkGeneration);
									    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_582){
                                                $this->putBool($this->blockNetworkIdsAreHashes);
									    		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_589){
							    					$this->networkPermissions->encode($this);
										    	}
									    	}
								        }
							    	}
						    	}
	    	                }
	    	            }
	    	        }
                }
            }
	    }else{
	    	$this->putInt($this->seed);
	    	$this->spawnSettings->write($this);
		    $this->putInt($this->generator);
		    $this->putInt($this->worldGamemode);
	    	$this->putEntityRuntimeId($this->entityRuntimeId);
	    	$this->putInt($this->spawnX);
	    	$this->putInt($this->spawnY);
	    	$this->putInt($this->spawnZ);
	    	$this->putVector3($this->playerPosition);
	    	$this->putBool($this->hasAchievementsDisabled);
	    	$this->putBool($this->dayCycleStopTime);
	    	$this->putBool($this->eduMode);
	    	$this->putShortString($this->levelId);
	    }
	}

	private static function serializeItemTable(array $table) : string{
		$stream = new NetworkBinaryStream();
		$stream->putUnsignedVarInt(count($table));
		foreach($table as $name => $legacyId){
			$stream->putString($name);
			$stream->putLShort($legacyId);
		}
		return $stream->getBuffer();
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleStartGame($this);
	}
}
