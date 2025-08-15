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

namespace pocketmine\entity\behavior;

class BehaviorPool{

	/** @var BehaviorEntry[] */
	protected $behaviorEntries = [];
	/** @var BehaviorEntry[] */
	protected $workingBehaviors = [];
	/** @var int */
	protected $tickRate = 3;
	/** @var int */
	protected $tickCounter = 0;

	public function setBehavior(int $priority, Behavior $behavior) : void{
		$this->behaviorEntries[spl_object_id($behavior)] = new BehaviorEntry($priority, $behavior);
	}

	public function removeBehavior(Behavior $behavior) : void{
		unset($this->behaviorEntries[spl_object_id($behavior)]);
	}

	/**
	 * Updates behaviors
	 */
	public function onUpdate() : bool{
		if($this->tickCounter++ % $this->tickRate === 0){
			foreach($this->behaviorEntries as $id => $entry){
				$behavior = $entry->getBehavior();

				if(isset($this->workingBehaviors[$id])){
					if(!$this->canUse($entry) or !$behavior->canContinue()){
						$behavior->onEnd();

						unset($this->workingBehaviors[$id]);
					}
				}

				if($this->canUse($entry) and $behavior->canStart()){
					$behavior->onStart();

					$this->workingBehaviors[$id] = $entry;
				}
			}
		}else{
			foreach($this->workingBehaviors as $id => $entry){
				if(!$entry->getBehavior()->canContinue()){
					$entry->getBehavior()->onEnd();

					unset($this->workingBehaviors[$id]);
				}
			}
		}

		foreach($this->workingBehaviors as $entry){
			$entry->getBehavior()->onTick();
		}

		return count($this->workingBehaviors) > 0;
	}

	public function canUse(BehaviorEntry $entry) : bool{
		foreach($this->behaviorEntries as $id => $behaviorEntry){
			if($behaviorEntry->getBehavior() !== $entry->getBehavior()){
				if($entry->getPriority() >= $behaviorEntry->getPriority()){
					if(!$this->theyCanWorkCompatible($entry->getBehavior(), $behaviorEntry->getBehavior()) and isset($this->workingBehaviors[$id])){
						return false;
					}
				}elseif(!$behaviorEntry->getBehavior()->isMutable() and isset($this->workingBehaviors[$id])){
					return false;
				}
			}
		}

		return true;
	}

	public function theyCanWorkCompatible(Behavior $b1, Behavior $b2) : bool{
		return ($b1->getMutexBits() & $b2->getMutexBits()) === 0;
	}

	public function getTickRate() : int{
		return $this->tickRate;
	}

	public function setTickRate(int $tickRate) : void{
		$this->tickRate = $tickRate;
	}

	/**
	 * @return BehaviorEntry[]
	 */
	public function getBehaviorEntries() : array{
		return $this->behaviorEntries;
	}

	public function clearBehaviors() : void{
		$this->behaviorEntries = [];
		$this->workingBehaviors = [];
	}
}