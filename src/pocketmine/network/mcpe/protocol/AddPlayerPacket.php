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

use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\utils\UUID;
use function count;

class AddPlayerPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_PLAYER_PACKET;

	/** @var UUID */
	public $uuid;
	/** @var string */
	public $username;
	/** @var string */
	public $thirdPartyName = "";
	/** @var int */
	public $platform = 0;
	/** @var int|null */
	public $entityUniqueId = null; //TODO
	/** @var int */
	public $entityRuntimeId;
	/** @var string */
	public $platformChatId = "";
	/** @var Vector3 */
	public $position;
	/** @var Vector3|null */
	public $motion;
	/** @var float */
	public $pitch = 0.0;
	/** @var float */
	public $yaw = 0.0;
	/** @var float|null */
	public $headYaw = null; //TODO
	/** @var Item */
	public $item;
	/** @var int */
	public $gameMode = GameMode::SURVIVAL;
	/** @var array */
	public $metadata = [];
	/** @var PropertySyncData */
	public $syncedProperties;
    /** @var AbilitiesData */
    public $abilitiesData;

	//TODO: adventure settings stuff
	public $uvarint1 = 0;
	public $uvarint2 = 0;
	public $uvarint3 = 0;
	public $uvarint4 = 0;
	public $uvarint5 = 0;

	public $long1 = 0;

	/** @var EntityLink[] */
	public $links = [];

	/** @var string */
	public $deviceId = ""; //TODO: fill player's device ID (???)
	/** @var int */
	public $buildPlatform = DeviceOS::UNKNOWN;

	protected function decodePayload(){
		$this->uuid = $this->getUUID();
		$this->username = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getString() : $this->getShortString();
		if($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200 && $this->getProtocol() < ProtocolInfo::PROTOCOL_290){
			$this->thirdPartyName = $this->getString();
			$this->platform = $this->getVarInt();
		}
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90 && $this->getProtocol() < ProtocolInfo::PROTOCOL_534){
	    	$this->entityUniqueId = $this->getEntityUniqueId();
		}
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		if($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200){
	    	$this->platformChatId = $this->getString();
		}
		$this->position = $this->getVector3();
		$this->motion = $this->getVector3();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->pitch = $this->getLFloat();
	    	$this->yaw = $this->getLFloat();
	    	$this->headYaw = $this->getLFloat();
		}else{
	    	$this->yaw = $this->getFloat();
	    	$this->headYaw = $this->getFloat();
	    	$this->pitch = $this->getFloat();
		}
		$this->item = $this->getSlot();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_503){
		    $this->gameMode = $this->getVarInt();
		}
		$this->metadata = $this->getEntityMetadata();

        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_534){
                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_557){
                    $this->syncedProperties = PropertySyncData::read($this);
                }
                $this->abilitiesData = AbilitiesData::decode($this);
            }else{
	        	$this->uvarint1 = $this->getUnsignedVarInt();
	        	$this->uvarint2 = $this->getUnsignedVarInt();
	        	$this->uvarint3 = $this->getUnsignedVarInt();
	        	$this->uvarint4 = $this->getUnsignedVarInt();
	        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_133){
	            	$this->uvarint5 = $this->getUnsignedVarInt();
	        	}

	        	$this->long1 = $this->getLLong();
            }

            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_132){
	        	$linkCount = $this->getUnsignedVarInt();
	        	for($i = 0; $i < $linkCount; ++$i){
		        	$this->links[$i] = $this->getEntityLink();
	        	}

                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_282){
	            	$this->deviceId = $this->getString();
	            	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_385){
	            	    $this->buildPlatform = $this->getLInt();
	            	}
                }
            }
        }
	}

	protected function encodePayload(){
		$this->putUUID($this->uuid);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->putString($this->username);
		}else{
		    $this->putShortString($this->username);
		}
		if($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200 && $this->getProtocol() < ProtocolInfo::PROTOCOL_290){
			$this->putString($this->thirdPartyName);
			$this->putVarInt($this->platform);
		}
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90 && $this->getProtocol() < ProtocolInfo::PROTOCOL_534){
	    	$this->putEntityUniqueId($this->entityUniqueId ?? $this->entityRuntimeId);
		}
		$this->putEntityRuntimeId($this->entityRuntimeId);
		if($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200){
	    	$this->putString($this->platformChatId);
		}
		$this->putVector3($this->position);
		$this->putVector3Nullable($this->motion);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
            $this->putLFloat($this->pitch);
            $this->putLFloat($this->yaw);
            $this->putLFloat($this->headYaw ?? $this->yaw);
		}else{
            $this->putFloat($this->yaw);
            $this->putFloat($this->headYaw ?? $this->yaw);
            $this->putFloat($this->pitch);
		}
		$this->putSlot($this->item);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_503){
		    $this->putVarInt($this->gameMode);
		}
		$this->putEntityMetadata($this->metadata);

        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_534){
                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_557){
                    ($this->syncedProperties ?? new PropertySyncData([], []))->write($this);
                }
                $this->abilitiesData->encode($this);
            }else{
	        	$this->putUnsignedVarInt($this->uvarint1);
	        	$this->putUnsignedVarInt($this->uvarint2);
	        	$this->putUnsignedVarInt($this->uvarint3);
	        	$this->putUnsignedVarInt($this->uvarint4);
	        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_133){
	             	$this->putUnsignedVarInt($this->uvarint5);
	        	}

                $this->putLLong($this->long1);
            }

            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_132){
	        	$this->putUnsignedVarInt(count($this->links));
	        	foreach($this->links as $link){
		        	$this->putEntityLink($link);
	        	}

                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_282){
	            	$this->putString($this->deviceId);
	            	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_385){
                        $this->putLInt($this->buildPlatform);
	            	}
                }
            }
        }
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddPlayer($this);
	}
}
