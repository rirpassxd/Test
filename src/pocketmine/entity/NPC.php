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

namespace pocketmine\entity;

use pocketmine\Player;
use pocketmine\entity\Human;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\entity\Skin;

class NPC extends Human {

    /** @var Skin */
    protected $skin;

    /**
     * NPC constructor.
     * @param Level $level
     * @param float $x
     * @param float $y
     * @param float $z
     * @param float $yaw
     * @param float $pitch
     * @param Skin $skin
     */
    public function __construct(Level $level, float $x, float $y, float $z, float $yaw, float $pitch, Skin $skin) {
        $this->skin = $skin;

        $nbt = new CompoundTag("", [
            new ListTag("Pos", [
                new DoubleTag("", $x),
                new DoubleTag("", $y),
                new DoubleTag("", $z)
            ]),
            new ListTag("Motion", [
                new DoubleTag("", 0.0),
                new DoubleTag("", 0.0),
                new DoubleTag("", 0.0)
            ]),
            new ListTag("Rotation", [
                new FloatTag("", $yaw),
                new FloatTag("", $pitch)
            ]),
            new CompoundTag("Skin", [
                new ByteArrayTag("Data", $skin->getSkinData()),
                new StringTag("Name", $skin->getSkinId())
            ])
        ]);

        parent::__construct($level, $nbt);
        $this->setSkin($skin);
        $this->sendSkin();
        $this->spawnToAll();
    }

    /**
     * Spawns the NPC to all players in the level
     */
    public function spawnToAll(): void {
        foreach ($this->getLevelNonNull()->getPlayers() as $player) {
            $this->spawnTo($player);
        }
    }

    /**
     * Spawns the NPC to a specific player
     * @param Player $player
     */
    public function spawnTo(Player $player): void {
        parent::spawnTo($player);
    }

    /**
     * Despawns the NPC from all players in the level
     */
    public function despawnFromAll(): void {
        foreach ($this->getLevelNonNull()->getPlayers() as $player) {
            $this->despawnFrom($player, true);
        }
    }

    /**
     * Despawns the NPC from a specific player
     * @param Player $player
     * @param bool $send
     */
    public function despawnFrom(Player $player, bool $send = true): void {
        parent::despawnFrom($player, $send);
    }
}

?>
