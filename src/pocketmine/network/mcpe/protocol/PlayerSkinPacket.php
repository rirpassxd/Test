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

use pocketmine\entity\Skin;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\utils\UUID;

class PlayerSkinPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_SKIN_PACKET;

	/** @var UUID */
	public $uuid;
	/** @var string */
	public $oldSkinName = "";
	/** @var string */
	public $newSkinName = "";
	/** @var Skin */
	public $skin;
	/** @var bool */
	public $premiumSkin = false;

	protected function decodePayload(){
		$this->uuid = $this->getUUID();

        if($this->getProtocol() < ProtocolInfo::PROTOCOL_370){
	    	$skinId = $this->getString();
	    	$this->newSkinName = $this->getString();
	    	$this->oldSkinName = $this->getString();
	    	if ($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200 && $this->getProtocol() < ProtocolInfo::PROTOCOL_220) {
		    	$this->getLInt(); // num skin data, always 1
		    	$this->getLInt();
	     	}
	    	$skinData = $this->getString();
	    	if ($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200 && $this->getProtocol() < ProtocolInfo::PROTOCOL_220) {
		    	$this->getLInt();
		    	$this->getLInt();
	    	}
	    	$capeData = "";
	    	if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_131) {
	        	$capeData = $this->getString();
	    	}
	    	$geometryModel = $this->getString();
	    	$geometryData = $this->getString();

	    	$this->skin = new Skin($skinId, $skinData, $capeData, $geometryModel, $geometryData);

            if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_260 && !$this->feof()) {
	        	$this->premiumSkin = $this->getBool();
            }
        }else{
            $this->skin = $this->getSkin();
	    	$this->newSkinName = $this->getString();
	    	$this->oldSkinName = $this->getString();
	    	if($this->getProtocol() === ProtocolInfo::PROTOCOL_390 || ($this->getProtocol() >= ProtocolInfo::PROTOCOL_401 && $this->getProtocol() !== ProtocolInfo::PROTOCOL_402)){
                $this->getBool(); //TODO: trustedSkin
	    	}
        }
	}

	protected function encodePayload(){
		$this->putUUID($this->uuid);

        if($this->getProtocol() < ProtocolInfo::PROTOCOL_370){
    		$this->putString($this->skin->getSkinId());
	    	$this->putString($this->newSkinName);
	    	$this->putString($this->oldSkinName);
			$skinData = $this->skin->getClientFriendlySkinData($this->getProtocol());
	    	if ($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200 && $this->getProtocol() < ProtocolInfo::PROTOCOL_220) {
		    	$this->putLInt(1); // num skin data, always 1
		    	$this->putLInt(strlen($skinData));
	    	}
	    	$this->putString($skinData);
	    	if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_131) {
		        if ($this->getProtocol() !== ProtocolInfo::PROTOCOL_201 && $this->getProtocol() >= ProtocolInfo::PROTOCOL_200 && $this->getProtocol() < ProtocolInfo::PROTOCOL_220) {
		        	$this->putLInt($this->skin->getCapeData() === "" ? 0 : 1);
		        	$this->putLInt(strlen($this->skin->getCapeData()));
	        	}
	        	$this->putString($this->skin->getCapeData());
	    	}
	    	$this->putString($this->skin->getGeometryName());
	    	$this->putString($this->skin->getGeometryData());

            if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_260) {
                $this->putBool($this->premiumSkin);
            }
        }else{
            $this->putSkin($this->skin);
	    	$this->putString($this->newSkinName);
	    	$this->putString($this->oldSkinName);
	    	if($this->getProtocol() === ProtocolInfo::PROTOCOL_390 || ($this->getProtocol() >= ProtocolInfo::PROTOCOL_401 && $this->getProtocol() !== ProtocolInfo::PROTOCOL_402)){
                $this->putBool($this->skin->getSerializedSkin()->isTrustedSkin());
	    	}
        }
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerSkin($this);
	}
}
