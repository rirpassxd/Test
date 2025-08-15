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

namespace pocketmine\block;

use pocketmine\block\utils\ColorBlockMetaHelper;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\Player;
use pocketmine\tile\Container;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\ShulkerBox as TileShulkerBox;
use pocketmine\tile\Tile;

class ShulkerBox extends Transparent{
    protected $id = self::SHULKER_BOX;

    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }

    public function getBlastResistance() : float{
        return 30;
    }

    public function getHardness() : float{
        return 2;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_PICKAXE;
    }

    public function getName() : string{
        return ColorBlockMetaHelper::getColorFromMeta($this->meta) . " Shulker Box";
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $this->getLevelNonNull()->setBlock($this, $this, true, true);
        $nbt = TileShulkerBox::createNBT($this, $face, $item, $player);
        $items = $item->getNamedTag()->getTag(Container::TAG_ITEMS);
        if ($items != null) {
            $nbt->setTag($items);
        }
        Tile::createTile(TileShulkerBox::SHULKER_BOX, $this->getLevelNonNull(), $nbt);
        return true;
    }

    public function onBreak(Item $item, Player $player = null) : bool{
        $t = $this->level->getTile($this);
        if($t instanceof TileShulkerBox){
            $t->getInventory()->clearAll(false);
        }

        return parent::onBreak($item, $player);
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        if($player instanceof Player){
            $t = $this->getLevelNonNull()->getTile($this);
            $shulker = null;
            if($t instanceof TileShulkerBox){
                $shulker = $t;
            }else{
                $shulker = Tile::createTile(TileShulkerBox::SHULKER_BOX, $this->getLevelNonNull(), TileShulkerBox::createNBT($this));
                if(!($shulker instanceof TileShulkerBox)){
                    return true;
                }
            }
            if(
                !$this->getSide(Vector3::SIDE_UP)->isTransparent() or
                !$shulker->canOpenWith($item->getCustomName())
            ){
                return true;
            }
            $player->addWindow($shulker->getInventory());
        }
        return true;
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        $item = ItemFactory::get($this->id, $this->meta);

        $t = $this->level->getTile($this);
        if($t instanceof TileShulkerBox){
    	    $blockData = new CompoundTag();
			$t->writeBlockData($blockData);
			$item->setNamedTag($blockData);

			if($t->hasName()){
			    $item->setCustomName($t->getName());
			}
        }

        return [$item];
    }

    public function getBlockProtocol(int $protocol) : ?Block{
        if($protocol < ProtocolInfo::PROTOCOL_100){
            return new Chest();
        }

        return parent::getBlockProtocol($protocol);
    }
}