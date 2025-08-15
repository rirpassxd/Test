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
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\resourcepacks\ResourcePack;
use function count;

class ResourcePackStackPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACK_STACK_PACKET;

	/** @var bool */
	public $mustAccept = false;

	/** @var ResourcePack[] */
	public $behaviorPackStack = [];
	/** @var ResourcePack[] */
	public $resourcePackStack = [];

	/** @var bool */
	public $isExperimental = false;
	/** @var string */
	public $baseGameVersion = ProtocolInfo::MINECRAFT_VERSION_NETWORK;
	/** @var Experiments */
	public $experiments;
	/** @var bool */
	public $useVanillaEditorPacks;

	protected function decodePayload(){
		$this->mustAccept = $this->getBool();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
	    	$behaviorPackCount = $this->getUnsignedVarInt();
		}else{
			$behaviorPackCount = $this->getLShort();
		}
		while($behaviorPackCount-- > 0){
			$this->getString();
			$this->getString();
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
		    	$this->getString();
			}
		}

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
		    $resourcePackCount = $this->getUnsignedVarInt();
		}else{
			$resourcePackCount = $this->getLShort();
		}
		while($resourcePackCount-- > 0){
			$this->getString();
			$this->getString();
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
		    	$this->getString();
			}
		}

		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_290) {
		    if($this->getProtocol() < ProtocolInfo::PROTOCOL_419){
	        	$this->isExperimental = $this->getBool();
		    }
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_385){
	        	$this->baseGameVersion = $this->getString();
	        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
	        	    $this->experiments = Experiments::read($this);
					if($this->getProtocol() >= ProtocolInfo::PROTOCOL_671){
				    	$this->useVanillaEditorPacks = $this->getBool();
					}
	        	}
	    	}
		}
	}

	protected function encodePayload(){
        $this->putBool($this->mustAccept);

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
			$this->putUnsignedVarInt(count($this->behaviorPackStack));
		}else{
	    	$this->putLShort(count($this->behaviorPackStack));
		}
		foreach($this->behaviorPackStack as $entry){
			$this->putString($entry->getPackId());
			$this->putString($entry->getPackVersion());
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
		    	$this->putString(""); //TODO: subpack name
			}
		}

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_110){
			$this->putUnsignedVarInt(count($this->resourcePackStack));
		}else{
		    $this->putLShort(count($this->resourcePackStack));
		}
		foreach($this->resourcePackStack as $entry){
			$this->putString($entry->getPackId());
			$this->putString($entry->getPackVersion());
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_130){
		    	$this->putString(""); //TODO: subpack name
			}
		}

		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_290) {
		    if($this->getProtocol() < ProtocolInfo::PROTOCOL_419){
                $this->putBool($this->isExperimental);
		    }
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_385){
	        	$this->putString($this->baseGameVersion);
	        	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
	        	    $this->experiments->write($this);
					if($this->getProtocol() >= ProtocolInfo::PROTOCOL_671){
                        $this->putBool($this->useVanillaEditorPacks);
					}
	        	}
	    	}
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleResourcePackStack($this);
	}
}
