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

namespace pocketmine\entity\hostile;

use pocketmine\entity\behavior\AvoidMobTypeBehavior;
use pocketmine\entity\behavior\FleeSunBehavior;
use pocketmine\entity\behavior\FloatBehavior;
use pocketmine\entity\behavior\LookAtPlayerBehavior;
use pocketmine\entity\behavior\NearestAttackableTargetBehavior;
use pocketmine\entity\behavior\RandomLookAroundBehavior;
use pocketmine\entity\behavior\RandomStrollBehavior;
use pocketmine\entity\behavior\RangedAttackBehavior;
use pocketmine\entity\behavior\RestrictSunBehavior;
use pocketmine\entity\Entity;
use pocketmine\entity\Monster;
use pocketmine\entity\passive\Wolf;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\RangedAttackerMob;
use pocketmine\entity\Smite;
use pocketmine\inventory\EntityEquipment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\biome\Biome;
use pocketmine\level\weather\Weather;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\Player;
use function in_array;
use function rand;

class Skeleton extends Monster implements RangedAttackerMob, Smite{

	public const NETWORK_ID = self::SKELETON;

	public $width = 0.6;
	public $height = 1.99;

	/** @var EntityEquipment */
	protected $equipment;

	protected function initEntity() : void{
		$this->setMovementSpeed(0.25);
		$this->setFollowRange(35);
		$this->setAttackDamage(2);

		parent::initEntity();

		$this->equipment = new EntityEquipment($this);

		$this->equipment->setItemInHand(ItemFactory::get(Item::BOW));

		// TODO: Armors
	}

	public function getName() : string{
		return "Skeleton";
	}

	public function getDrops() : array{
		return [
			ItemFactory::get(Item::BONE, 0, rand(0, 2)), ItemFactory::get(Item::ARROW, 0, rand(0, 2))
		];
	}

	public function getXpDropAmount() : int{
		return 5;
	}

	protected function addBehaviors() : void{
		$this->behaviorPool->setBehavior(0, new FloatBehavior($this));
		$this->behaviorPool->setBehavior(1, new RestrictSunBehavior($this));
		$this->behaviorPool->setBehavior(2, new FleeSunBehavior($this, 1.0));
		$this->behaviorPool->setBehavior(3, new AvoidMobTypeBehavior($this, Wolf::class, 6.0, 1.0, 1.2));
		$this->behaviorPool->setBehavior(4, new RandomStrollBehavior($this, 1.0));
		$this->behaviorPool->setBehavior(5, new RangedAttackBehavior($this, 1.0, 20, 60, 15.0));
		$this->behaviorPool->setBehavior(6, new LookAtPlayerBehavior($this, 8.0));
		$this->behaviorPool->setBehavior(7, new RandomLookAroundBehavior($this));

		$this->targetBehaviorPool->setBehavior(0, new NearestAttackableTargetBehavior($this, Player::class, true));
		//$this->targetBehaviorPool->setBehavior(2, new NearestAttackableTargetBehavior($this, IronGolem::class, false));
	}

	public function onRangedAttackToTarget(Entity $target, float $power) : void{
		$pos = $this->add(0, $this->getEyeHeight() - 0.1, 0);
		$motion = $target->add(0, $target->height / 3, 0)->subtract($pos)->normalize();
		$f = sqrt($motion->x ** 2 + $motion->z ** 2);

		/** @var Arrow $arrow */
		$arrow = Entity::createEntity("Arrow", $this->level, Entity::createBaseNBT($pos->add($motion)));
		// TODO: Enchants
		$arrow->setThrowableMotion($motion->add(0, $f * 0.2, 0), 1.6, (14 - $this->level->getDifficulty() * 4));
		$arrow->setPickupMode(Arrow::PICKUP_NONE);
		$arrow->setBaseDamage($power * 2 + $this->random->nextFloat() * 0.25 + ($this->level->getDifficulty() * 0.11));

		$this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_BOW);
		$arrow->spawnToAll();
	}

	public function entityBaseTick(int $diff = 1) : bool{
		if(!$this->isOnFire() and $this->level->isDayTime() and !$this->isImmobile()){
			if(!$this->isInsideOfWater() and $this->level->canSeeSky($this) and !(in_array($this->level->getWeather()->getWeather(), [Weather::RAIN, Weather::RAINY_THUNDER, Weather::THUNDER]) and $this->level->getBiomeId($this->getFloorX(), $this->getFloorZ()) !== Biome::DESERT)){
				$this->setOnFire(5);
			}
		}
		return parent::entityBaseTick($diff);
	}

	public function sendSpawnPacket(Player $player) : void{
		parent::sendSpawnPacket($player);

		$this->equipment->sendContents([$player]);

		// stupid hack for 1.1
		$pk = new MobEquipmentPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->inventorySlot = $pk->hotbarSlot = 10;
		$pk->item = $this->equipment->getItemInHand();
		$player->sendDataPacket($pk);
	}
}