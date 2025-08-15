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

namespace pocketmine\inventory;

use pocketmine\level\Position;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;
use function count;
use function max;
use function in_array;

class AnvilInventory extends ContainerInventory{

	/** @var Position */
	protected $holder;

	/** @var string[] */
	private $players = [];

	public function __construct(Position $pos){
		parent::__construct($pos->asPosition());
	}

	public function getNetworkType() : int{
		return WindowTypes::ANVIL;
	}

	public function getName() : string{
		return "Anvil";
	}

	public function getDefaultSize() : int{
		return 2; //1 input, 1 material
	}

	/**
	 * This override is here for documentation and code completion purposes only.
	 * @return Position
	 */
	public function getHolder(){
		return $this->holder;
	}

	public function onClose(Player $who) : void{
		parent::onClose($who);
        if(isset($this->players[$who->getName()])){
            unset($this->players[$who->getName()]);
        }
	}

    public function onResolve(Player $player, int $takeOffLevel) : void{
        if($player->getXpLevel() < $takeOffLevel){
            return;
        }

        $this->players[$player->getName()] = $takeOffLevel;
    }

    public function onRename(Player $player, Item $before, Item $after, int $slot) : bool{
        if(!$this->equals($before, $after)){
            return false;
        }

        $player->getInventory()->setItem($slot, $after);

        return true;
    }

    public function onRepair(Player $player, Item $before, Item $before1, Item $after, int $slot): bool{
        $result = $this->tryRepair($before, $before1);
        if($result !== null){
            if(!$this->equals($result["result"], $after)){
                return false;
            }

            $player->getInventory()->setItem($slot, $after);
            $player->getTransactionQueue()->getInventory()->clearAll();
            $player->getTransactionQueue()->getInventory()->addItem($result["left"]);

            return true;
        }

        $result = $this->pairItems($before, $before1);
        if($result === null){
            return false;
        }

        if(!$this->equals($result, $after, true)){
            return false;
        }

        $player->getInventory()->setItem($slot, $after);
        $player->getTransactionQueue()->getInventory()->clearAll();

        return true;
    }

    public function handleChange(Player $player, Item $real, Item $item, int $slot) : void{
        if(!isset($this->players[$player->getName()])){
            return;
        }

        $floatingContents = $player->getTransactionQueue()->getInventory()->getContents();
        if(count($floatingContents) === 1){
            $firstItem = null;
            foreach($floatingContents as $unknownItem){
                if(!$unknownItem instanceof Item){
                    continue;
                }
                if($firstItem === null){
                    $firstItem = $unknownItem;
                }
            }

            if($firstItem === null){
                return;
            }

            if($firstItem->getId() === Item::AIR){
                return;
            }

            if($this->onRename($player, $firstItem, $item, $slot)){
                $player->getLevel()->broadcastLevelSoundEvent($this->holder, LevelSoundEventPacket::SOUND_RANDOM_ANVIL_USE);
                $player->subtractXp($this->players[$player->getName()]);
                $player->getTransactionQueue()->getInventory()->clearAll();
                if($real->getId() !== Item::AIR){
                    $player->getTransactionQueue()->getInventory()->addItem($real);
                }
            }
        }elseif(count($floatingContents) === 2){
            $firstItem = null;
            $secondItem = null;
            foreach($floatingContents as $unknownItem){
                if(!$unknownItem instanceof Item){
                    continue;
                }
                if($firstItem === null){
                    $firstItem = $unknownItem;
                }elseif($secondItem === null){
                    $secondItem = $unknownItem;
                }
            }

            if($firstItem === null or $secondItem === null){
                return;
            }
            if($firstItem->getId() === Item::AIR or $secondItem->getId() === Item::AIR){
                return;
            }

            if($this->onRepair($player, $firstItem, $secondItem, $item, $slot)){
                $player->getLevel()->broadcastLevelSoundEvent($this->holder, LevelSoundEventPacket::SOUND_RANDOM_ANVIL_USE);
                $player->subtractXp($this->players[$player->getName()]);
                if($real->getId() !== Item::AIR){
                    $player->getTransactionQueue()->getInventory()->addItem($real);
                }
            }
        }
        unset($this->players[$player->getName()]);
    }

    public function equals(Item $item1, Item $item2, bool $paired = false) : bool{
        if($item1->getId() !== $item2->getId()){
            return false;
        }

        if($item1->getCount() !== $item2->getCount()){
            return false;
        }

        if($item1->getDamage() !== $item2->getDamage()){
            return false;
        }

        $enchantments1 = $item1->getEnchantments();
        $enchantments2 = $item2->getEnchantments();
        if(count($enchantments1) !== count($enchantments2) and !$paired){
            return false;
        }

        $count = 0;
        foreach($enchantments1 as $ench){
            if(!$item2->hasEnchantment($ench->getId(), $ench->getLevel(), true)){
                $count++;
            }
        }

        if($count > 0 and !$paired or ($paired and $count > 1)){
            return false;
        }

        return true;
    }

    public function addEnchantments(Item $item, Item $result) : Item{
        foreach($item->getEnchantments() as $ench){
            if(($level = $result->getEnchantmentLevel($ench->getId())) > 0){
                if($level == $ench->getLevel()){
                    $finalLevel = $level + 1;
                }else{
                    $finalLevel = max($level, $ench->getLevel());
                }
                $ench->setLevel($finalLevel);
            }

            $result->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment($ench->getId())));
        }

        return $result;
    }

    public function pairItems(Item $item1, Item $item2) : ?Item{
        if($item1->getId() === 0 or $item2->getId() === 0){
            return null;
        }

        if(($item1->getId() === $item2->getId()) or ($item1->getId() === Item::ENCHANTED_BOOK or $item2->getId() === Item::ENCHANTED_BOOK)){
            if($item1->getId() !== Item::ENCHANTED_BOOK){
                $result = $this->addEnchantments($item2, clone $item1);
            }elseif($item2->getId() !== Item::ENCHANTED_BOOK){
                $result = $this->addEnchantments($item1, clone $item2);
            }elseif($item1->getId() === Item::ENCHANTED_BOOK and $item2->getId() === Item::ENCHANTED_BOOK){
                $result = $this->addEnchantments($item2, clone $item1);
            }

            if($item1->getId() === $item2->getId()){
                if($item1->getDamage() > 0){
                    $damage = $item2->getDamage() - $item1->getDamage();
                    $damage = $damage < 0 ? 0 : $damage;
                    $result->setDamage($damage);
                }elseif($item2->getDamage() > 0){
                    $damage = $item1->getDamage() - $item2->getDamage();
                    $damage = $damage < 0 ? 0 : $damage;
                    $result->setDamage($damage);
                }
            }

            return $result;
        }

        return null;
    }

    public function tryRepair(Item $item1, Item $item2) : ?array{
        if(!($item1 instanceof Tool or $item1 instanceof Armor) and !($item2 instanceof Tool or $item2 instanceof Armor)){
            return null;
        }

        if($item1 instanceof Tool or $item1 instanceof Armor){
            $repaired = $item1;
            $repairItem = $item2;
        }else{
            $repaired = $item2;
            $repairItem = $item1;
        }

        if(($repairItemId = $this->getRepairItemId($repaired)) === null){
            return null;
        }

        if($repairItemId !== $repairItem->getId()){
            return null;
        }

        $damage = $repaired->getDamage();
        $used = 0;
        for($i = $repairItem->getCount(); $i > 0; $i--){
            $damage -= 8 * $i;
            $used++;
            if($damage <= 0){
                break;
            }
        }

        $damage = $damage < 0 ? 0 : $damage;
        $result = clone $repaired;
        $result->setDamage($damage);

        $left = clone $repairItem;
        $left->setCount($repairItem->getCount() - $used);

        return [
			"result" => $result,
			"left" => $left
		];
    }

    public function getRepairItemId(Item $item) : ?int{
        $repairItems = [
            Item::DIAMOND => [
                Item::DIAMOND_AXE,
                Item::DIAMOND_BOOTS,
                Item::DIAMOND_CHESTPLATE,
                Item::DIAMOND_HELMET,
                Item::DIAMOND_HOE,
                Item::DIAMOND_HORSE_ARMOR,
                Item::DIAMOND_LEGGINGS,
                Item::DIAMOND_PICKAXE,
                Item::DIAMOND_SHOVEL,
                Item::DIAMOND_SWORD
            ],
            Item::GOLD_INGOT => [
                Item::GOLD_AXE,
                Item::GOLD_BOOTS,
                Item::GOLD_CHESTPLATE,
                Item::GOLD_HELMET,
                Item::GOLD_HOE,
                Item::GOLD_HORSE_ARMOR,
                Item::GOLD_LEGGINGS,
                Item::GOLD_PICKAXE,
                Item::GOLD_SHOVEL,
                Item::GOLD_SWORD
            ],
            Item::STONE => [
                Item::STONE_AXE,
                Item::STONE_HOE,
                Item::STONE_PICKAXE,
                Item::STONE_SHOVEL,
                Item::STONE_SWORD
            ],
            Item::IRON_INGOT => [
                Item::IRON_AXE,
                Item::IRON_BOOTS ,
                Item::IRON_CHESTPLATE,
                Item::IRON_HELMET,
                Item::IRON_HOE,
                Item::IRON_HORSE_ARMOR ,
                Item::IRON_LEGGINGS,
                Item::IRON_PICKAXE,
                Item::IRON_SHOVEL,
                Item::IRON_SWORD
            ],
            Item::LEATHER => [
                Item::LEATHER_BOOTS,
                Item::LEATHER_CAP,
                Item::LEATHER_HORSE_ARMOR,
                Item::LEATHER_PANTS,
                Item::LEATHER_TUNIC
            ],
            Item::STRING => [
                Item::BOW
            ]
        ];

        foreach($repairItems as $repairItem => $canBeRepaired){
            if(in_array($item->getId(), $canBeRepaired)){
                return $repairItem;
            }
        }

        return null;
    }
}
