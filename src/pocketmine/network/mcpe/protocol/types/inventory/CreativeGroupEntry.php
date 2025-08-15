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
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class CreativeGroupEntry{
    public function __construct(
        private int $categoryId,
        private string $categoryName,
        private Item $icon
    ){}

    public function getCategoryId() : int{ return $this->categoryId; }

    public function getCategoryName() : string{ return $this->categoryName; }

    public function getIcon() : Item{ return $this->icon; }

    public static function read(NetworkBinaryStream $in) : self{
        if($in->getProtocol() < ProtocolInfo::PROTOCOL_406){
            $categoryName = $in->getString();
            $icon = ItemFactory::get($in->getLInt());
            $in->getUnsignedVarInt(); // nbt count
        }else{
            $categoryId = $in->getLInt();
            $categoryName = $in->getString();
            $icon = $in->getSlot(false);
        }
        return new self($categoryId ?? 0, $categoryName, $icon);
    }

    public function write(NetworkBinaryStream $out) : void{
        if($out->getProtocol() < ProtocolInfo::PROTOCOL_406){
            $out->putString($this->categoryName);
            $out->putLInt($this->icon->getId());
            $out->putUnsignedVarInt(0); // nbt count
        }else{
            $out->putLInt($this->categoryId);
            $out->putString($this->categoryName);
            $out->putSlot($this->icon, false);
        }
    }
}