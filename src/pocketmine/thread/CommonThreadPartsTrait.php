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

use pocketmine\errorhandler\ErrorToExceptionHandler;
use pocketmine\Server;
use function error_get_last;
use function error_reporting;
use function implode;
use function register_shutdown_function;
use function set_exception_handler;
use Throwable;
use ReflectionClass;
use GlobalLogger;

trait CommonThreadPartsTrait{
	private ?ThreadSafeClassLoader $classLoader = null;
	protected ?ThreadCrashInfo $crashInfo = null;
	protected ?string $composerAutoloaderPath = null;

	/** @var bool */
	protected $isKilled = false;

	/**
	 * @return ThreadCrashInfo|null
	 */
	public function getCrashInfo() : ?ThreadCrashInfo{
		return $this->crashInfo;
	}

	/**
	 * @return ThreadSafeClassLoader
	 */
	public function getClassLoader() : ?ThreadSafeClassLoader{
		return $this->classLoader;
	}

	/**
	 * @param ThreadSafeClassLoader $loader
	*/
	public function setClassLoader(ThreadSafeClassLoader $loader = null){
		$this->composerAutoloaderPath = \pocketmine\COMPOSER_AUTOLOADER_PATH;
		if($loader === null){
			$loader = Server::getInstance()->getLoader();
		}
		$this->classLoader = $loader;
	}

	/**
	 * Registers the class loaders for this thread.
	 *
	 * WARNING: This method MUST be called from any descendent threads' run() method to make autoloading usable.
	 * If you do not do this, you will not be able to use new classes that were not loaded when the thread was started
	 * (unless you are using a custom autoloader).
	 */

	public function registerClassLoader() : void{
		if($this->classLoader !== null){
			$this->classLoader->register(true);
		}
		if ($this->composerAutoloaderPath !== null) {
			require $this->composerAutoloaderPath;
		}
	}

	final public function run() : void{
		error_reporting(-1);
		$this->registerClassLoader();
		//set this after the autoloader is registered
		ErrorToExceptionHandler::set();
		set_exception_handler($this->onUncaughtException(...));
		register_shutdown_function($this->onShutdown(...));

		$this->onRun();
		$this->isKilled = true;
	}

	/**
	 * Called by set_exception_handler() when an uncaught exception is thrown.
	 */
	protected function onUncaughtException(Throwable $e) : void{
		$this->synchronized(function() use ($e) : void{
			$this->crashInfo = ThreadCrashInfo::fromThrowable($e, $this->getThreadName());
			GlobalLogger::get()->logException($e);
		});
	}

	/**
	 * Called by register_shutdown_function() when the thread shuts down. This may be because of a benign shutdown, or
	 * because of a fatal error. Use isKilled to determine which.
	 */
	protected function onShutdown() : void{
		$this->synchronized(function() : void{
			if(!$this->isTerminated() && $this->crashInfo === null){
				$last = error_get_last();
				if($last !== null){
					//fatal error
					$crashInfo = ThreadCrashInfo::fromLastErrorInfo($last, $this->getThreadName());
				}else{
					//probably misused exit()
					//$crashInfo = ThreadCrashInfo::fromThrowable(new \RuntimeException("Thread crashed without an error - perhaps exit() was called?"), $this->getThreadName());
					return;
				}
				$this->crashInfo = $crashInfo;

				$lines = [];
				//mimic exception printed format
				$lines[] = "Fatal error: " . $crashInfo->makePrettyMessage();
				$lines[] = "--- Stack trace ---";
				foreach($crashInfo->getTrace() as $frame){
					$lines[] = "  " . $frame->getPrintableFrame();
				}
				$lines[] = "--- End of fatal error information ---";
				GlobalLogger::get()->critical(implode("\n", $lines));
			}
		});
	}

	/**
	 * Runs code on the thread.
	 */
	abstract protected function onRun() : void;

	public function getThreadName() : string{
		return (new ReflectionClass($this))->getShortName();
	}
}