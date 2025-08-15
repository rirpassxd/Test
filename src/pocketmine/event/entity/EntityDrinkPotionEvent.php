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

namespace pocketmine\event\entity;

use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\item\Potion;

class EntityDrinkPotionEvent extends EntityEvent{

	/* @var Potion */
	private $potion;

	/* @var EffectInstance[] */
	private $effects;

	/**
	 * EntityDrinkPotionEvent constructor.
	 *
	 * @param Entity $entity
	 * @param Potion $potion
	 */
	public function __construct(Entity $entity, Potion $potion){
		$this->entity = $entity;
		$this->potion = $potion;
		$this->effects = $potion->getAdditionalEffects();
	}

	/**
	 * @return array|EffectInstance[]
	 */
	public function getEffects(){
		return $this->effects;
	}

	/**
	 * @return Potion
	 */
	public function getPotion(){
		return $this->potion;
	}
}
