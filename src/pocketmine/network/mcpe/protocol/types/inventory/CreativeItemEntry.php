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

namespace pocketmine\network\mcpe\protocol\types\inventory;

use pocketmine\item\Item;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class CreativeItemEntry{
    public function __construct(
        private int $entryId,
        private Item $item,
        private int $groupId
    ){}

    public function getEntryId() : int{ return $this->entryId; }

    public function getItem() : Item{ return $this->item; }

    public function getGroupId() : int{ return $this->groupId; }

    public function setEntryId(int $entryId) : void{ $this->entryId = $entryId; }

    public static function read(NetworkBinaryStream $in) : self{
        if($in->getProtocol() >= ProtocolInfo::PROTOCOL_419){
            $entryId = $in->getVarInt();
        }else{
            $entryId = $in->getUnsignedVarInt();
            if($in->getProtocol() < ProtocolInfo::PROTOCOL_406){
                $in->getUnsignedVarInt();
            }
        }

        $item = $in->getSlot(false);
        if($in->getProtocol() >= ProtocolInfo::PROTOCOL_776){
            $groupId = $in->getUnsignedVarInt();
        }

        return new self($entryId, $item, $groupId ?? 0);
    }

    public function write(NetworkBinaryStream $out) : void{
        if($out->getProtocol() >= ProtocolInfo::PROTOCOL_419){
            $out->putVarInt($this->entryId);
        }else{
            $out->putUnsignedVarInt($this->entryId);
            if($out->getProtocol() < ProtocolInfo::PROTOCOL_406){
                $out->putUnsignedVarInt($this->groupId);
            }
        }

        $out->putSlot($this->item, false);
        if($out->getProtocol() >= ProtocolInfo::PROTOCOL_776){
            $out->putUnsignedVarInt($this->groupId);
        }
    }
}