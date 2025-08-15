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

namespace pocketmine\thread;

use pocketmine\thread\log\ThreadSafeLogger;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\utils\Utils;
use function getmypid;

class AutoKillThread extends Thread{
    /** @var bool */
	public $isResponded = true;

    /** @var bool */
	private $running = true;

    /** @var int */
	private $preTimeout = 0;

    /** @var SleeperNotifier */
	private $notifier;

    /** @var int */
	private $timeout;

    /** @var ThreadSafeLogger */
	private $logger;

	public function __construct(SleeperNotifier $notifier, int $timeout, ThreadSafeLogger $logger){
	    $this->notifier = $notifier;
	    $this->timeout = $timeout;
	    $this->logger = $logger;
	}

	public function onRun() : void{
		$this->registerClassLoader();
		$unit = 1000000;
		while($this->running){
			if(!$this->isResponded){
				$this->performTimeout();
				$this->notifier->wakeupSleeper();

				$this->synchronized(function() use ($unit){
					$this->wait($unit); // wtf 1 second
				});
			}else{
				$this->preTimeout = 0;

				// 1000000 = 1 second
				$this->synchronized(function() use ($unit){
					$this->wait(15 * $unit); // seconds to milliseconds

					$this->notifier->wakeupSleeper();
					$this->isResponded = false;
				});
			}
		}
	}

	private function performTimeout() : void{
		$this->preTimeout++;
		if ($this->preTimeout > $this->timeout) {
			$this->logger->emergency("The server has been stopped! Killing it...");
	    	@Utils::kill(getmypid());
			exit(5);
		}
	}

	public function quit() : void{
		$this->running = false;
		parent::quit();
	}

	public function getThreadName() : string{
		return 'Auto Kill';
	}
}