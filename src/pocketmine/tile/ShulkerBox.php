<?php

declare(strict_types=1);

namespace pocketmine\tile;

use InvalidArgumentException;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\ShulkerBoxInventory;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

class ShulkerBox extends Spawnable implements InventoryHolder, Container, Nameable{
    use NameableTrait {
        addAdditionalSpawnData as addNameSpawnData;
    }
    use ContainerTrait;

    protected $facing = self::SIDE_UP;
    protected $inventory;

    public function __construct(Level $level, CompoundTag $nbt){
        parent::__construct($level, $nbt);
    }

    protected static function createAdditionalNBT(CompoundTag $nbt, Vector3 $pos, ?int $face = null, ?Item $item = null, ?Player $player = null) : void{
        if($item !== null and $item->hasCustomName()){
            $nbt->setString(Nameable::TAG_CUSTOM_NAME, $item->getCustomName());
        }
        if($face === null){
            $face = 1;
        }
        $nbt->setByte("facing", $face);
    }

    public function getDefaultName() : string{
        return "Shulker Box";
    }

    public function close() : void{
        if ($this->isClosed()) {
            $this->inventory->removeAllViewers(true);
            $this->inventory = null;
            parent::close();
        }
    }

    public function getRealInventory() : ?ShulkerBoxInventory{
        return $this->inventory;
    }

    public function getInventory() : ?ShulkerBoxInventory{
        return $this->inventory;
    }

    public function getFacing() : int{
        return $this->facing;
    }

    public function setFacing(int $face) : void{
        if($face < 0 or $face > 5){
            throw new InvalidArgumentException("Facing must be in range 0-5, not " . $face);
        }
        $this->facing = $face;
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt) : void{
        $nbt->setByte("facing", $this->facing);
        $this->addNameSpawnData($nbt);
    }

    protected function readSaveData(CompoundTag $nbt) : void{
        $this->loadName($nbt);
        $this->inventory = new ShulkerBoxInventory($this);
        $this->loadItems($nbt);
        $this->facing = $nbt->getByte("facing", 1);
    }

    protected function writeSaveData(CompoundTag $nbt) : void{
        $this->saveName($nbt);
        $this->saveItems($nbt);
        $nbt->setByte("facing", $this->facing);
    }

    public function writeBlockData(CompoundTag $nbt){
        $this->saveItems($nbt);
    }
}