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

use pocketmine\entity\behavior\FloatBehavior;
use pocketmine\entity\behavior\HurtByTargetBehavior;
use pocketmine\entity\behavior\LookAtPlayerBehavior;
use pocketmine\entity\behavior\NearestAttackableTargetBehavior;
use pocketmine\entity\behavior\RandomLookAroundBehavior;
use pocketmine\entity\behavior\RandomStrollBehavior;
use pocketmine\entity\behavior\RangedAttackBehavior;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\entity\Monster;
use pocketmine\entity\RangedAttackerMob;
use pocketmine\entity\Smite;
use pocketmine\inventory\EntityEquipment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\Potion;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use function array_rand;
use function lcg_value;
use function sqrt;

class Witch extends Monster implements RangedAttackerMob, Smite{

    public const NETWORK_ID = self::WITCH;

    public $width = 0.6;
    public $height = 1.95;

    private $healCooldown = 0;
    private $potionVisibleTicks = 0;

    /** @var EntityEquipment */
    protected $equipment;

    protected function initEntity() : void{
        $this->setMaxHealth(26);
        $this->setHealth(26);
        $this->setMovementSpeed(0.2);
        $this->setFollowRange(35);

        parent::initEntity();

        $this->equipment = new EntityEquipment($this);
    }

    public function getName() : string{
        return "Witch";
    }

    public function getDrops() : array{
        return [
            ItemFactory::get(Item::GLASS_BOTTLE, 0, rand(0, 2)),
            ItemFactory::get(Item::GLOWSTONE_DUST, 0, rand(0, 2))
        ];
    }

    public function getXpDropAmount() : int{
        return 5;
    }

    protected function addBehaviors() : void{
        $this->behaviorPool->setBehavior(0, new FloatBehavior($this));
        $this->behaviorPool->setBehavior(1, new RandomStrollBehavior($this, 1.0));
        $this->behaviorPool->setBehavior(2, new RangedAttackBehavior($this, 1.0, 20, 60, 10.0));
        $this->behaviorPool->setBehavior(3, new LookAtPlayerBehavior($this, 8.0));
        $this->behaviorPool->setBehavior(4, new RandomLookAroundBehavior($this));

        $this->targetBehaviorPool->setBehavior(0, new NearestAttackableTargetBehavior($this, Player::class, true));
        $this->targetBehaviorPool->setBehavior(1, new HurtByTargetBehavior($this));
    }

    public function onRangedAttackToTarget(Entity $target, float $power) : void{
        if($this->potionVisibleTicks > 0){
            return;
        }

        $diff = new Vector3(
            $target->x - $this->x,
            ($target->y + $target->getEyeHeight() - 1) - $this->y,
            $diffZ = $target->z - $this->z
        );
        $horizontalForce = sqrt(($diff->x ** 2) + ($diff->z ** 2));
        $targetVector = $diff->add(0, $horizontalForce * 0.2)->normalize()->multiply(0.75);           

        $nbt = Entity::createBaseNBT($this->add(0, $this->getEyeHeight(), 0), $targetVector, lcg_value() * 360, 0);

        $entity = Entity::createEntity("SplashPotion", $this->level, $nbt);
        if($entity instanceof Entity){
            $potions = [
                Potion::POISON,
                Potion::HARMING,
                Potion::SLOWNESS,
                Potion::WEAKNESS
            ];
            $entity->setPotionId($potions[array_rand($potions)]);

            $entity->setOwningEntity($this);
            $entity->spawnToAll();
        }

        $this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_THROW, -1, EntityIds::PLAYER);
    }

    public function entityBaseTick(int $diff = 1) : bool{
        if(!$this->isImmobile()){
            if($this->healCooldown > 0){
                $this->healCooldown -= $diff;
            }

            if($this->potionVisibleTicks > 0){
                $this->potionVisibleTicks -= $diff;
            }

            if($this->potionVisibleTicks <= 0){
                $this->equipment->setItemInHand(ItemFactory::get(Item::AIR));
                $this->equipment->sendSlot(0, $this->getViewers());
            }

            if($this->getHealth() < $this->getMaxHealth() && $this->healCooldown <= 0){
                $this->healWitch();
            }
        }

        return parent::entityBaseTick($diff);
    }

    private function healWitch() : void{
        $this->healCooldown = 100;
        $this->setHealth($this->getHealth() + 4);
        $this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_DRINK);

        $this->equipment->setItemInHand(ItemFactory::get(Item::POTION, 21));
        $this->equipment->sendSlot(0, $this->getViewers());

        $this->potionVisibleTicks = 32;
    }

    public function sendSpawnPacket(Player $player) : void{
        parent::sendSpawnPacket($player);
    }
}
