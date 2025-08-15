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
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\block\Block;
use pocketmine\Player;
use function max;
use function mt_rand;
use function count;

class EnchantInventory extends ContainerInventory{

	/** @var Position */
	protected $holder;

	/** @var int[] */
    private $levels = null;
	/** @var int */
    private $bookshelfAmount = 0;
	/** @var string[] */
    private $players = [];

	public function __construct(Position $pos){
		parent::__construct($pos->asPosition());
	}

	public function getNetworkType() : int{
		return WindowTypes::ENCHANTMENT;
	}

	public function getName() : string{
		return "Enchantment Table";
	}

	public function getDefaultSize() : int{
		return 2; //1 input, 1 lapis
	}

	/**
	 * This override is here for documentation and code completion purposes only.
	 * @return Position
	 */
	public function getHolder(){
		return $this->holder;
	}

    public function onOpen(Player $who) : void{
        parent::onOpen($who);

        if($this->levels === null){
            $this->bookshelfAmount = $this->countBookshelf();

            if($this->bookshelfAmount < 0){
                $this->bookshelfAmount = 0;
            }

            if($this->bookshelfAmount > 15){
                $this->bookshelfAmount = 15;
            }

            $base = mt_rand(1, 8) + ($this->bookshelfAmount / 2) + mt_rand(0, $this->bookshelfAmount);
            $this->levels = [
                0 => max($base / 3, 1),
                1 => (($base * 2) / 3 + 1),
                2 => max($base, $this->bookshelfAmount * 2)
            ];
        }
    }

    public function onResolve(Player $player, int $takeOffLevel) : void{
        if($player->getXpLevel() < $takeOffLevel){
            return;
        }

        $this->players[$player->getName()] = $takeOffLevel;
    }

	public function onClose(Player $who) : void{
		parent::onClose($who);

        if(isset($this->players[$who->getName()])){
            unset($this->players[$who->getName()]);
        }

        if(count($this->getViewers()) === 0){
            $this->levels = null;
            $this->bookshelfAmount = 0;
        }
	}

    public function handleChange(Player $player, Item $real, Item $item, int $slot){
        if(!isset($this->players[$player->getName()])){
            return;
        }

        $floatingContents = $player->getTransactionQueue()->getInventory()->getContents();
        if(count($floatingContents) === 2){
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
            if($firstItem->getId() === Item::DYE and $firstItem->getDamage() === 4){ //slot 0 is lapis
                $before = $secondItem;
                $lapis = $firstItem;
            }else{
                $before = $firstItem;
                $lapis = $secondItem;
            }
            if($before instanceof Tool or $before instanceof Armor or $before->getId() === Item::BOOK){
                if($this->onEnchant($player, $before, $item, $slot)){
                    $player->subtractXp($this->players[$player->getName()]);
                    $player->getTransactionQueue()->getInventory()->clearAll();
                    $lapis->setCount($lapis->getCount() - $this->players[$player->getName()]);
                    $player->getTransactionQueue()->getInventory()->addItem($lapis);
                    if($real->getId() !== Item::AIR){
                        $player->getTransactionQueue()->getInventory()->addItem($real);
                    }
                }
            }
        }
        unset($this->players[$player->getName()]);
    }

    public function onEnchant(Player $who, Item $before, Item $after, $slot) : bool{
        $result = ($before->getId() === Item::BOOK) ? Item::ENCHANTED_BOOK : $before;
        if (!$before->hasEnchantments() and $after->hasEnchantments() and $after->getId() === $result->getId() and $this->levels !== null) {
            $enchantments = $after->getEnchantments();
            foreach ($enchantments as $enchantment) {
                $result->addEnchantment($enchantment);
            }
            $who->getInventory()->setItem($slot, $result);
            return true;
        }
        return false;
    }

    public function countBookshelf() : int{
        $count = 0;
        $pos = $this->getHolder();
        $offsets = [[2, 0], [-2, 0], [0, 2], [0, -2], [2, 1], [2, -1], [-2, 1], [-2, 1], [1, 2], [-1, 2], [1, -2], [-1, -2]];
        for ($i = 0; $i < 3; $i++) {
            foreach ($offsets as $offset) {
                if ($pos->getLevel()->getBlockIdAt($pos->x + $offset[0], $pos->y + $i, $pos->z + $offset[1]) == Block::BOOKSHELF) {
                    $count++;
                }
                if ($count >= 15) {
                    break 2;
                }
            }
        }
        return $count;
    }

    public function removeConflictEnchantment(Enchantment $enchantment, array $enchantments) : array{
        if(count($enchantments) > 0){
            foreach($enchantments as $id){
                if($id == $enchantment->getId()){
                    unset($enchantments[$id]);
                    continue;
                }

                if($id >= 0 and $id <= 4 and $enchantment->getId() >= 0 and $enchantment->getId() <= 4){
                    //Protection
                    unset($enchantments[$id]);
                    continue;
                }

                if($id >= 9 and $id <= 14 and $enchantment->getId() >= 9 and $enchantment->getId() <= 14){
                    //Weapon
                    unset($enchantments[$id]);
                    continue;
                }

                if(($id === Enchantment::SILK_TOUCH) or ($id === Enchantment::FORTUNE and $enchantment->getId() === Enchantment::SILK_TOUCH)){
                    //Protection
                    unset($enchantments[$id]);
                    continue;
                }
            }
        }
        $result = [];
        if(count($enchantments) > 0){
            foreach($enchantments as $enchantment){
                $result[] = $enchantment;
            }
        }
        return $result;
    }
}
