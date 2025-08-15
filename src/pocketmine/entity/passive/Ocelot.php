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

use pocketmine\entity\behavior\FloatBehavior;
use pocketmine\entity\behavior\FollowOwnerBehavior;
use pocketmine\entity\behavior\LookAtPlayerBehavior;
use pocketmine\entity\behavior\MateBehavior;
use pocketmine\entity\behavior\PanicBehavior;
use pocketmine\entity\behavior\RandomLookAroundBehavior;
use pocketmine\entity\behavior\RandomStrollBehavior;
use pocketmine\entity\behavior\StayWhileSittingBehavior;
use pocketmine\entity\behavior\TemptBehavior;
use pocketmine\entity\Tamable;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;
use function mt_rand;

class Ocelot extends Tamable{
	public const NETWORK_ID = self::OCELOT;

    public const TYPE_WILD = 0;
    public const TYPE_BLACK = 1;
    public const TYPE_RED = 2;
    public const TYPE_SIAMESE = 3;

	public $width = 0.35;
	public $height = 0.7;
	/** @var StayWhileSittingBehavior */
	protected $behaviorSitting;

	protected function addBehaviors() : void{
		$this->behaviorPool->setBehavior(0, new FloatBehavior($this));
		$this->behaviorPool->setBehavior(1, new PanicBehavior($this, 2.0));
		$this->behaviorPool->setBehavior(2, $this->behaviorSitting = new StayWhileSittingBehavior($this));
		$this->behaviorPool->setBehavior(3, new MateBehavior($this, 2.0));
		$this->behaviorPool->setBehavior(4, new TemptBehavior($this, [
			Item::RAW_SALMON,
			Item::RAW_FISH
		], 1.0));
		$this->behaviorPool->setBehavior(5, new FollowOwnerBehavior($this, 1, 10, 2));
		$this->behaviorPool->setBehavior(6, new RandomStrollBehavior($this, 0.8));
		$this->behaviorPool->setBehavior(7, new LookAtPlayerBehavior($this, 14.0));
		$this->behaviorPool->setBehavior(8, new RandomLookAroundBehavior($this));


		// TODO: attack turtle and rabbit
	}

	protected function initEntity() : void{
		$this->setMaxHealth(10);
		$this->setMovementSpeed(0.3);
		$this->setFollowRange(16);
		$this->setAttackDamage(3);
		if($this->namedtag->hasTag("CatType", IntTag::class)){
			$this->propertyManager->setInt(self::DATA_VARIANT, $this->namedtag->getInt("CatType"));
		}

		parent::initEntity();
	}

	public function getName() : string{
		return "Ocelot";
	}

	public function onInteract(Player $player, Item $item, Vector3 $clickPos) : bool{
		if(!$this->isImmobile()){
			if($item->getId() == Item::RAW_SALMON || $item->getId() == Item::RAW_FISH){
				if($player->isSurvival()){
					$item->pop();
				}
				if($this->isTamed()){
					$this->setInLove(true);
					$this->setHealth(min($this->getMaxHealth(), $this->getHealth() + 2));
				}elseif(mt_rand(0, 2) == 0){
					$this->setOwningEntity($player);
					$this->setTamed();
					$this->setSittingFromBehavior(true);
					$this->setType(mt_rand(1, 3));
					$this->broadcastEntityEvent(ActorEventPacket::TAME_SUCCESS);
				}else{
					$this->broadcastEntityEvent(ActorEventPacket::TAME_FAIL);
				}
				return true;
			}else{
				if($this->isTamed()){
					$this->setSittingFromBehavior(!$this->isSitting());
				}
			}
		}
		return parent::onInteract($player, $item, $clickPos);
	}

	public function getXpDropAmount() : int{
		$damage = $this->getLastDamageCause();
		if($damage instanceof EntityDamageByEntityEvent){
			$damager = $damage->getDamager();
			if($damager instanceof Player || ($damager instanceof Wolf && $damager->isTamed())){
				return rand(1, ($this->isInLove() ? 7 : 3));
			}
		}
		return 0;
	}

	public function getDrops() : array{
		return [
			ItemFactory::get(Item::STRING, 0, rand(0, 2)),
		];
	}

    public function saveNBT() : void{
        parent::saveNBT();

        $catType = $this->propertyManager->getInt(self::DATA_VARIANT);
        if($catType !== null){
            $this->namedtag->setInt("CatType", $catType);
        }
    }

	public function setSittingFromBehavior(bool $value) : void{
		$this->behaviorSitting->setSitting($value);
	}

	public function setType(int $type) : void{
		$this->propertyManager->setInt(self::DATA_VARIANT, $type);
	}

	public function attack(EntityDamageEvent $source) : void{
		if($source->getCause() !== EntityDamageEvent::CAUSE_FALL){
			parent::attack($source);
		}
	}
}