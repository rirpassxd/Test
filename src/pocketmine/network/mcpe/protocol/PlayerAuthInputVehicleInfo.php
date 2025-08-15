<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\NetworkBinaryStream;

final class PlayerAuthInputVehicleInfo{

    public function __construct(
        private float $vehicleRotationX,
        private float $vehicleRotationZ,
        private int $predictedVehicleActorUniqueId
    ){}

    public function getVehicleRotationX() : float{ return $this->vehicleRotationX; }

    public function getVehicleRotationZ() : float{ return $this->vehicleRotationZ; }

    public function getPredictedVehicleActorUniqueId() : int{ return $this->predictedVehicleActorUniqueId; }

    public static function read(NetworkBinaryStream $in) : self{
        if($in->getProtocol() >= ProtocolInfo::PROTOCOL_662){
            $vehicleRotationX = $in->getLFloat();
            $vehicleRotationZ = $in->getLFloat();
        }
        $predictedVehicleActorUniqueId = $in->getEntityUniqueId();

        return new self($vehicleRotationX ?? 0, $vehicleRotationZ ?? 0, $predictedVehicleActorUniqueId);
    }

    public function write(NetworkBinaryStream $out) : void{
        if($out->getProtocol() >= ProtocolInfo::PROTOCOL_662){
            $out->putLFloat($this->vehicleRotationX);
            $out->putLFloat($this->vehicleRotationZ);
        }
        $out->putEntityUniqueId($this->predictedVehicleActorUniqueId);
    }
}