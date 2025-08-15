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

namespace pocketmine\event\level;

use pocketmine\event\Cancellable;
use pocketmine\level\Level;
use pocketmine\level\weather\Weather;

class WeatherChangeEvent extends LevelEvent implements Cancellable{
	/** @var int */
	private $weather;
	/** @var int */
	private $duration;

	/**
	 * WeatherChangeEvent constructor.
	 *
	 * @param Level $level
	 * @param int   $weather
	 * @param int   $duration
	 */
	public function __construct(Level $level, int $weather, int $duration){
		parent::__construct($level);
		$this->weather = $weather;
		$this->duration = $duration;
	}

	/**
	 * @return int
	 */
	public function getWeather() : int{
		return $this->weather;
	}

	/**
	 * @param int $weather
	 */
	public function setWeather(int $weather = Weather::SUNNY){
		$this->weather = $weather;
	}

	/**
	 * @return int
	 */
	public function getDuration() : int{
		return $this->duration;
	}

	/**
	 * @param int $duration
	 */
	public function setDuration(int $duration){
		$this->duration = $duration;
	}

}