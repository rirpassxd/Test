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

use pmmp\thread\ThreadSafe;
use Closure;

final class ThreadSafeClosure extends ThreadSafe{
	private Closure $closure;

	public function __construct(Closure $closure, $object = null){
		$closure = Closure::bind($closure, $object === null ? $this : $object);
		$this->closure = $closure;
	}

	public function getClosure(): Closure{
		return $this->closure;
	}

	public function execute(array $args = []): void{
		call_user_func_array($this->closure, $args);
	}
}
