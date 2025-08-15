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

namespace pocketmine\network\mcpe\multiversion\block;

use pocketmine\network\mcpe\multiversion\block\palettes\Palette;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\thread\NonThreadSafeValue;
use Closure;

class BlockPaletteInitTask extends AsyncTask{
    /** @phpstan-var NonThreadSafeValue<Palette> */
    private $pool;
    /** @var int */
    private $poolProtocol;

    public function __construct(Palette $pool, int $poolProtocol, Closure $onCompletion){
        $this->pool = new NonThreadSafeValue($pool);
        $this->poolProtocol = $poolProtocol;
        $this->storeLocal($onCompletion);
    }

	public function onRun() : void{
	    $pool = $this->pool->deserialize();
	    $pool::init();
		$this->setResult($pool);
	}

	public function onCompletion(Server $server) : void{
		/**
		 * @var Closure $callback
		 * @phpstan-var Closure(Palette $palette, int $poolProtocol) : void $callback
		 */
		$callback = $this->fetchLocal();
	    $callback($this->getResult(), $this->poolProtocol);
	}
}