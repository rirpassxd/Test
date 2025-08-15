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

namespace pocketmine\level\weather;

use pocketmine\entity\Entity;
use pocketmine\event\level\WeatherChangeEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;
use function array_rand;
use function count;
use function is_int;
use function max;
use function min;
use function mt_rand;

class Weather{
	public const CLEAR = 0;
	public const SUNNY = 0;
	public const RAIN = 1;
	public const RAINY = 1;
	public const RAINY_THUNDER = 2;
	public const THUNDER = 3;

	/** @var Level */
	private $level;
	/** @var int */
	private $weatherNow = 0;
	/** @var int */
	private $strength1 = 100000;
	/** @var int */
	private $strength2 = 35000;
	/** @var int */
	private $duration;
	/** @var bool */
	private $canCalculate = true;

	/** @var Vector3 */
	private $temporalVector = null;

	/** @var int */
	private $lastUpdate = 0;

	/** @var int[] */
	private $randomWeatherData = [0, 1, 0, 1, 0, 1, 0, 2, 0, 3];

	/**
	 * Weather constructor.
	 *
	 * @param Level $level
	 * @param int   $duration
	 */
	public function __construct(Level $level, int $duration = 1200){
		$this->level = $level;
		$this->weatherNow = self::SUNNY;
		$this->duration = $duration;
		$this->lastUpdate = $level->getServer()->getTick();
		$this->temporalVector = new Vector3(0, 0, 0);
	}

	/**
	 * @return bool
	 */
	public function canCalculate() : bool{
		return $this->canCalculate;
	}

	/**
	 * @param bool $canCalc
	 */
	public function setCanCalculate(bool $canCalc) : void{
		$this->canCalculate = $canCalc;
	}

	/**
	 * @param int $currentTick
	 */
	public function calcWeather(int $currentTick) : void{
		if($this->canCalculate()){
			$tickDiff = $currentTick - $this->lastUpdate;
			$this->duration -= $tickDiff;

			if($this->duration <= 0){
				$duration = mt_rand(
					min($this->level->getServer()->weatherRandomDurationMin, $this->level->getServer()->weatherRandomDurationMax),
					max($this->level->getServer()->weatherRandomDurationMin, $this->level->getServer()->weatherRandomDurationMax));

				if($this->weatherNow === self::SUNNY){
					$weather = $this->randomWeatherData[array_rand($this->randomWeatherData)];
					$this->setWeather($weather, $duration);
				}else{
					$weather = self::SUNNY;
					$this->setWeather($weather, $duration);
				}
			}
			if(($this->weatherNow >= self::RAINY_THUNDER) and ($this->level->getServer()->lightningTime > 0) and is_int($this->duration / $this->level->getServer()->lightningTime)){
				$players = $this->level->getPlayers();
				if(count($players) > 0){
					$p = $players[array_rand($players)];
					$x = $p->x + mt_rand(-64, 64);
					$z = $p->z + mt_rand(-64, 64);
					$y = $this->level->getHighestBlockAt((int) $x, (int) $z);
					$nbt = Entity::createBaseNBT(new Vector3($x, $y, $z));
					$lightning = Entity::createEntity("Lightning", $this->level, $nbt);
					$lightning->spawnToAll();
				}
			}
		}
		$this->lastUpdate = $currentTick;
	}

	/**
	 * @param int $wea
	 * @param int $duration
	 */
	public function setWeather(int $wea, int $duration = 12000) : void{
		$this->level->getServer()->getPluginManager()->callEvent($ev = new WeatherChangeEvent($this->level, $wea, $duration));
		if(!$ev->isCancelled()){
			$this->weatherNow = $ev->getWeather();
			$this->strength1 = mt_rand(90000, 110000); //If we're clearing the weather, it doesn't matter what strength values we set
			$this->strength2 = mt_rand(30000, 40000);
			$this->duration = $ev->getDuration();
			$this->sendWeatherToAll();
		}
	}

	/**
	 * @return array
	 */
	public function getRandomWeatherData() : array{
		return $this->randomWeatherData;
	}

	/**
	 * @param array $randomWeatherData
	 */
	public function setRandomWeatherData(array $randomWeatherData) : void{
		$this->randomWeatherData = $randomWeatherData;
	}

	/**
	 * @return int
	 */
	public function getWeather() : int{
		return $this->weatherNow;
	}

	/**
	 * @param mixed $weather
	 *
	 * @return int
	 */
	public static function getWeatherFromString(mixed $weather) : int{
		if(is_int($weather)){
			if($weather <= 3){
				return $weather;
			}
			return self::SUNNY;
		}
		switch(strtolower($weather)){
			case "clear":
			case "sunny":
			case "fine":
				return self::SUNNY;
			case "rain":
			case "rainy":
				return self::RAINY;
			case "thunder":
				return self::THUNDER;
			case "rain_thunder":
			case "rainy_thunder":
			case "storm":
				return self::RAINY_THUNDER;
			default:
				return self::SUNNY;
		}
	}

	/**
	 * @return bool
	 */
	public function isSunny() : bool{
		return $this->getWeather() === self::SUNNY;
	}

	/**
	 * @return bool
	 */
	public function isRainy() : bool{
		return $this->getWeather() === self::RAINY;
	}

	/**
	 * @return bool
	 */
	public function isRainyThunder() : bool{
		return $this->getWeather() === self::RAINY_THUNDER;
	}

	/**
	 * @return bool
	 */
	public function isThunder() : bool{
		return $this->getWeather() === self::THUNDER;
	}

	/**
	 * @return array
	 */
	public function getStrength() : array{
		return [$this->strength1, $this->strength2];
	}

	/**
	 * @param Player $p
	 */
	public function sendWeather(Player $p) : void{
		$pks = [
			new LevelEventPacket(),
			new LevelEventPacket()
		];

		$pks[0]->position = new Vector3(0, 0, 0);
		$pks[1]->position = new Vector3(0, 0, 0);

		//Set defaults. These will be sent if the case statement defaults.
		$pks[0]->evid = LevelEventPacket::EVENT_STOP_RAIN;
		$pks[0]->data = $this->strength1;
		$pks[1]->evid = LevelEventPacket::EVENT_STOP_THUNDER;
		$pks[1]->data = $this->strength2;

		switch($this->weatherNow){
			//If the weather is not clear, overwrite the packet values with these
			case self::RAIN:
				$pks[0]->evid = LevelEventPacket::EVENT_START_RAIN;
				$pks[0]->data = $this->strength1;
				break;
			case self::RAINY_THUNDER:
				$pks[0]->evid = LevelEventPacket::EVENT_START_RAIN;
				$pks[0]->data = $this->strength1;
				$pks[1]->evid = LevelEventPacket::EVENT_START_THUNDER;
				$pks[1]->data = $this->strength2;
				break;
			case self::THUNDER:
				$pks[1]->evid = LevelEventPacket::EVENT_START_THUNDER;
				$pks[1]->data = $this->strength2;
				break;
			default:
				break;
		}

		foreach($pks as $pk){
			$p->dataPacket($pk);
		}
	}

	public function sendWeatherToAll() : void{
		foreach($this->level->getPlayers() as $player){
			$this->sendWeather($player);
		}
	}

}