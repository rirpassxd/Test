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

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;

/** This is used for PlayerAuthInput packet when the flags include PERFORM_BLOCK_ACTIONS */
final class PlayerBlockActionWithBlockInfo implements PlayerBlockAction{
    public function __construct(
        private int $actionType,
        private int $x,
        private int $y,
        private int $z,
        private int $face
    ){
        if(!self::isValidActionType($actionType)){
            throw new \InvalidArgumentException("Invalid action type for " . self::class);
        }
    }

    public function getActionType() : int{ return $this->actionType; }

    public function getX() : int{ return $this->x; }

    public function getY() : int{ return $this->y; }

    public function getZ() : int{ return $this->z; }

    public function getFace() : int{ return $this->face; }

    public static function read(NetworkBinaryStream $in, int $actionType) : self{
        $x = $y = $z = 0;
        $in->getSignedBlockPosition($x, $y, $z);
        $face = $in->getVarInt();
        return new self($actionType, $x, $y, $z, $face);
    }

    public function write(NetworkBinaryStream $out) : void{
        $out->putSignedBlockPosition($this->x, $this->y, $this->z);
        $out->putVarInt($this->face);
    }

    public static function isValidActionType(int $actionType) : bool{
        return match($actionType){
            PlayerActionPacket::ACTION_ABORT_BREAK,
            PlayerActionPacket::ACTION_START_BREAK,
            PlayerActionPacket::ACTION_CONTINUE_BREAK,
            PlayerActionPacket::ACTION_PREDICT_DESTROY_BLOCK,
            PlayerActionPacket::ACTION_CONTINUE_DESTROY_BLOCK => true,
            default => false
        };
    }
}