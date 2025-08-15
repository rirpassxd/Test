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
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\utils\UUID;
use function count;

class ResourcePacksInfoPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACKS_INFO_PACKET;

	/** @var bool */
	public $mustAccept = false; //if true, forces client to use selected resource packs
	/** @var bool */
	public $hasAddons = false;
	/** @var bool */
	public $hasScripts = false; //if true, causes disconnect for any platform that doesn't support scripts yet
	/** @var UUID */
	public $worldTemplateId;
	/** @var string */
	public $worldTemplateVersion;
	/** @var bool */
	public $forceServerPacks = false;
	/** @var ResourcePack[] */
	public $behaviorPackEntries = [];
	/** @var ResourcePack[] */
	public $resourcePackEntries = [];
	/** @var string[] */
	public $cdnUrls = [];

	protected function decodePayload(){
		$this->mustAccept = $this->getBool();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_331){
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_662){
				$this->hasAddons = $this->getBool();
			}
	    	$this->hasScripts = $this->getBool();
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_448 && $this->getProtocol() < ProtocolInfo::PROTOCOL_729){
	    	    $this->forceServerPacks = $this->getBool();
	    	}elseif($this->getProtocol() >= ProtocolInfo::PROTOCOL_766){
				$this->worldTemplateId = $this->getUUID();
				$this->worldTemplateVersion = $this->getString();
			}
		}
		if($this->getProtocol() < ProtocolInfo::PROTOCOL_729){
	    	$behaviorPackCount = $this->getLShort();
	    	while($behaviorPackCount-- > 0){
		    	$this->getString();
		    	$this->getString();
		    	$this->getLLong();
		    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
		        	$this->getString();
		        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
		            	$this->getString();
		            	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_280){
		                	$this->getString();
		        	        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_331){
		                    	$this->getBool();
                                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_712){
                                    $this->getBool();
                                }
							}
                        }
		        	}
		    	}
			}
		}

		$resourcePackCount = $this->getLShort();
		while($resourcePackCount-- > 0){
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_766){
				$this->getUUID();
			}else{
				$this->getString();
			}
			$this->getString();
            $this->getLLong();
            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
		    	$this->getString();
		    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
		        	$this->getString();
		        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_280){
		    	        $this->getString();
		            	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_331){
		                	$this->getBool();
                            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_712){
                                $this->getBool();
                            }
		                	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_422){
		            	        $this->getBool();
						    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_748){
							    	$this->getString();
						    	}
							}
		            	}
		        	}
		    	}
			}
		}

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_618 && $this->getProtocol() < ProtocolInfo::PROTOCOL_748){
	    	$this->cdnUrls = [];
	    	for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; $i++){
		    	$packId = $this->getString();
		    	$cdnUrl = $this->getString();
		    	$this->cdnUrls[$packId] = $cdnUrl;
			}
		}
	}

	protected function encodePayload(){
        $this->putBool($this->mustAccept);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_331){
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_662){
                $this->putBool($this->hasAddons);
			}
            $this->putBool($this->hasScripts);
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_448 && $this->getProtocol() < ProtocolInfo::PROTOCOL_729){
	    	    $this->putBool($this->forceServerPacks);
	    	}elseif($this->getProtocol() >= ProtocolInfo::PROTOCOL_766){
				$this->putUUID($this->worldTemplateId);
				$this->putString($this->worldTemplateVersion);
			}
		}

		if($this->getProtocol() < ProtocolInfo::PROTOCOL_729){
            $this->putLShort(count($this->behaviorPackEntries));
		    foreach($this->behaviorPackEntries as $entry){
		    	$this->putString($entry->getPackId());
		    	$this->putString($entry->getPackVersion());
                $this->putLLong($entry->getPackSize());
                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
		        	$this->putString(""); //TODO: encryption key
			        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
		            	$this->putString(""); //TODO: subpack name
		            	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_280){
		                	$this->putString($entry->getPackId()); //TODO: content identity
		                	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_331){
                                $this->putBool(false); //TODO: has scripts (?)
                                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_712){
                                    $this->putBool(false); //TODO: is addon pack
                                }
							}
                        }
		        	}
		    	}
			}
		}
        $this->putLShort(count($this->resourcePackEntries));
		foreach($this->resourcePackEntries as $entry){
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_766){
				$this->putUUID(UUID::fromString($entry->getPackId()));
			}else{
			    $this->putString($entry->getPackId());
			}
			$this->putString($entry->getPackVersion());
            $this->putLLong($entry->getPackSize());
            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
		    	$this->putString($entry->getEncryptionKey() ?? ""); //TODO: encryption key
		    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
		        	$this->putString(""); //TODO: subpack name
		        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_280){
		            	$this->putString($entry->getPackId()); //TODO: content identity
		            	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_331){
                            $this->putBool(false); //TODO: seems useless for resource packs
                            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_712){
                                $this->putBool(false); //TODO: is addon pack
                            }
		                	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_422){
		            	        $this->putBool(false); //TODO: supports RTX
						    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_748){
							    	$this->putString(""); //TODO: cdn url
						    	}
							}
		            	}
		        	}
		    	}
			}
		}

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_618 && $this->getProtocol() < ProtocolInfo::PROTOCOL_748){
		    $this->putUnsignedVarInt(count($this->cdnUrls));
		    foreach($this->cdnUrls as $packId => $cdnUrl){
		    	$this->putString($packId);
		    	$this->putString($cdnUrl);
			}
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleResourcePacksInfo($this);
	}
}
