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

use pocketmine\utils\Color;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\SerializedSkin;
use function count;

class PlayerListPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_LIST_PACKET;

	public const TYPE_ADD = 0;
	public const TYPE_REMOVE = 1;

	/** @var PlayerListEntry[] */
	public $entries = [];
	/** @var int */
	public $type;

    public static function getPESkinId(Skin $skin) : string{
		$skinId = $skin->getSkinId();

		if(SerializedSkin::isSkinIdPE($skinId)){
			return $skinId;
		}

        $type = "Custom";
		switch ($skin->getGeometryName()){
			case "geometry.humanoid.customSlim":
			    $type = "CustomSlim";
			    break;
			case "geometry.humanoid.custom":
			default:
			    $type = "Custom";
			    break;
		}

		return "Standard_" . $type;
	}

	public function clean(){
		$this->entries = [];
		return parent::clean();
	}

	protected function decodePayload(){
		$this->type = $this->getByte();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$count = $this->getUnsignedVarInt();
		}else{
		    $count = $this->getInt();
		}
		for($i = 0; $i < $count; ++$i){
			$entry = new PlayerListEntry();

			if($this->type === self::TYPE_ADD){
				$entry->uuid = $this->getUUID();
				$entry->entityUniqueId = $this->getEntityUniqueId();
				$entry->username = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getString() : $this->getShortString();
				if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
			    	if($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200 && $this->getProtocol() < ProtocolInfo::PROTOCOL_290){
		            	$entry->thirdPartyName = $this->getString();
		            	$entry->platform = $this->getVarInt();
			    	}

                    if($this->getProtocol() < ProtocolInfo::PROTOCOL_370){
                        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
			            	$skinId = $this->getString();
			            	$skinData = $this->getString();
                        }else{
			            	$skinId = $this->getShortString();
			            	$skinData = $this->getShortString();
                        }
			        	$capeData = "";
			        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_131){
			            	$capeData = $this->getString();
			        	}
			        	$geometryName = $this->getString();
			        	$geometryData = $this->getString();

			        	$entry->skin = new Skin(
				        	$skinId,
				        	$skinData,
				        	$capeData,
				        	$geometryName,
				        	$geometryData
			        	);
                    }
			    	$entry->xboxUserId = $this->getString();
			    	if($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200){
			        	$entry->platformChatId = $this->getString();
			        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_370){
			        	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_385){
			                	$entry->buildPlatform = $this->getLInt();
			        	    }
			            	$entry->skin = $this->getSkin();
			            	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_385){
			                	$entry->isTeacher = $this->getBool();
			                	$entry->isHost = $this->getBool();
								if($this->getProtocol() >= ProtocolInfo::PROTOCOL_649){
							    	$entry->isSubClient = $this->getBool();
									if($this->getProtocol() >= ProtocolInfo::PROTOCOL_800){
										$entry->color = Color::fromARGB($this->getLInt());
									}
								}
			            	}
			        	}
			    	}
				} else {
			    	$skinId = $this->getString();
			    	$skinData = $this->getString();

			    	$entry->skin = new Skin(
				    	$skinId,
				    	$skinData
			    	);
				}
			}else{
				$entry->uuid = $this->getUUID();
			}

			$this->entries[$i] = $entry;
		}
		if($this->getProtocol() === ProtocolInfo::PROTOCOL_390 || ($this->getProtocol() >= ProtocolInfo::PROTOCOL_401 && $this->getProtocol() !== ProtocolInfo::PROTOCOL_402)){
	    	if($this->type === self::TYPE_ADD){
		    	for($i = 0; $i < $count; ++$i){
			    	$this->entries[$i]->skin->getSerializedSkin()->setIsTrustedSkin($this->getBool());
		    	}
			}
		}
	}

	protected function encodePayload(){
        $this->putByte($this->type);
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->putUnsignedVarInt(count($this->entries));
        }else{
            $this->putInt(count($this->entries));
        }
		foreach($this->entries as $entry){
			if($this->type === self::TYPE_ADD){
				$this->putUUID($entry->uuid);
				$this->putEntityUniqueId($entry->entityUniqueId);
				if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
			    	$this->putString($entry->username);
				}else{
				    $this->putShortString($entry->username);
				}
				if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
			    	if($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200 && $this->getProtocol() < ProtocolInfo::PROTOCOL_290){
		            	$this->putString($entry->thirdPartyName);
		            	$this->putVarInt($entry->platform);
			    	}
			    	if($this->getProtocol() < ProtocolInfo::PROTOCOL_370){
			        	$this->putString($entry->skin->getSkinId());

			        	if($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200 && $this->getProtocol() < ProtocolInfo::PROTOCOL_220){
				            $this->putLInt(1); // num skins, always 1
			        	}

			        	$this->putString($entry->skin->getClientFriendlySkinData($this->getProtocol()));

			        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_131){
			            	$capeData = $entry->skin->getCapeData();
			            	if($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200 && $this->getProtocol() < ProtocolInfo::PROTOCOL_220){
				            	if(!empty($capeData)){
					            	$this->putLInt(1); // isNotEmpty
					            	$this->putString($capeData);
				            	} else {
					            	$this->putLInt(0); // isEmpty
				            	}
			            	} else {
				            	$this->putString($capeData);
			            	}
			        	}

			        	$skinGeometryName = $entry->skin->getGeometryName();
			        	$skinGeometryData = $entry->skin->getGeometryData();
			        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_310){
				            $skinGeometryName = strtolower($skinGeometryName);
			            	$tempData = json_decode($skinGeometryData, true);
			            	if(is_array($tempData)){
				            	foreach($tempData as $key => $value){
					            	unset($tempData[$key]);
					            	$tempData[strtolower($key)] = $value;
				            	}

				            	$skinGeometryData = json_encode($tempData);
			            	}
			        	}
			        	$this->putString($skinGeometryName);
			        	$this->putString($this->prepareGeometryDataForOld($skinGeometryData));
			    	}

			    	$this->putString($entry->xboxUserId);
			    	if($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200){
			        	$this->putString($entry->platformChatId);
			        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_370){
			        	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_385){
                                $this->putLInt($entry->buildPlatform);
			        	    }
			            	$this->putSkin($entry->skin);
			            	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_385){
                                $this->putBool($entry->isTeacher);
                                $this->putBool($entry->isHost);
								if($this->getProtocol() >= ProtocolInfo::PROTOCOL_649){
									$this->putBool($entry->isSubClient);
									if($this->getProtocol() >= ProtocolInfo::PROTOCOL_800){
										$this->putLInt(($entry->color ?? new Color(255, 255, 255))->toARGB());
									}
								}
			            	}
			        	}
			    	}
				}else{
				    $skinId = self::getPESkinId($entry->skin);
				    $skinData = $entry->skin->getClientFriendlySkinData($this->getProtocol());
				    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
				        $this->putString($skinId);
				        $this->putString($skinData);
				    }else{
				        $this->putShortString($skinId);
				        $this->putShortString($skinData);
				    }
				}
			}else{
				$this->putUUID($entry->uuid);
			}
		}
		if($this->getProtocol() === ProtocolInfo::PROTOCOL_390 || ($this->getProtocol() >= ProtocolInfo::PROTOCOL_401 && $this->getProtocol() !== ProtocolInfo::PROTOCOL_402)){
	    	if($this->type === self::TYPE_ADD){
		    	foreach($this->entries as $entry){
                    $this->putBool($entry->skin->getSerializedSkin()->isTrustedSkin());
		    	}
			}
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerList($this);
	}
}
