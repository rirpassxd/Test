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

namespace pocketmine\entity\passive;

use pocketmine\entity\Animal;
use pocketmine\entity\behavior\FloatBehavior;
use pocketmine\entity\behavior\FollowParentBehavior;
use pocketmine\entity\behavior\LookAtPlayerBehavior;
use pocketmine\entity\behavior\MateBehavior;
use pocketmine\entity\behavior\PanicBehavior;
use pocketmine\entity\behavior\RandomLookAroundBehavior;
use pocketmine\entity\behavior\RandomStrollBehavior;
use pocketmine\entity\behavior\TemptBehavior;
use pocketmine\item\Bucket;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\Player;
use function rand;

class Cow extends Animal{

	public const NETWORK_ID = self::COW;

	public $width = 0.9;
	public $height = 1.3;

	/** @var int */
	private $milkingTicks = 0;

    public const MILKING_COOLDOWN = 20 * 60 * 5; // 5 minutes

	protected function addBehaviors() : void{
		$this->behaviorPool->setBehavior(0, new FloatBehavior($this));
		$this->behaviorPool->setBehavior(1, new PanicBehavior($this, 2.0));
		$this->behaviorPool->setBehavior(2, new MateBehavior($this, 1.0));
		$this->behaviorPool->setBehavior(3, new TemptBehavior($this, [Item::WHEAT], 1.25));
		$this->behaviorPool->setBehavior(4, new FollowParentBehavior($this, 1.25));
		$this->behaviorPool->setBehavior(5, new RandomStrollBehavior($this, 1.0));
		$this->behaviorPool->setBehavior(6, new LookAtPlayerBehavior($this, 6.0));
		$this->behaviorPool->setBehavior(7, new RandomLookAroundBehavior($this));
	}

	protected function initEntity() : void{
		$this->setMaxHealth(10);
		$this->setMovementSpeed(0.2);
		$this->setFollowRange(10);
		$this->milkingTicks = $this->namedtag->getInt("MilkingCooldown", 0);

		parent::initEntity();
	}

	public function getName() : string{
		return "Cow";
	}

	public function onInteract(Player $player, Item $item, Vector3 $clickPos) : bool{
		if(!$this->isImmobile()){
			if($item instanceof Bucket and $item->getDamage() === 0){
				if($this->milkingTicks > 0){
					return false;
				}

				$item->pop();
				$player->getInventory()->addItem(ItemFactory::get(Item::BUCKET, 1));
				$this->milkingTicks = self::MILKING_COOLDOWN;
				return true;
			}
		}

		return parent::onInteract($player, $item, $clickPos);
	}

	public function hasMilkingCooldown() : bool{
		return $this->milkingTicks > 0;
	}

	public function entityBaseTick(int $diff = 1) : bool{
		if($this->milkingTicks > 0){
			$this->milkingTicks--;
		}

		return parent::entityBaseTick($diff);
	}

	public function saveNBT() : void{
		parent::saveNBT();

		$this->namedtag->setInt("MilkingCooldown", $this->milkingTicks);
	}

	public function getXpDropAmount() : int{
		return rand(1, ($this->isInLove() ? 7 : 3));
	}

	public function getDrops() : array{
		return [
			ItemFactory::get(Item::LEATHER, 0, rand(0, 2)),
			($this->isOnFire() ? ItemFactory::get(Item::STEAK, 0, rand(1, 3)) : ItemFactory::get(Item::RAW_BEEF, 0, rand(1, 3)))
		];
	}
}