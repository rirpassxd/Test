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

namespace pocketmine\maps;

use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\Player;
use function max;
use function min;

class MapInfo{
	/** @var Player */
	public $player;
	/** @var int */
	public $packetSendTimer = 0;
	/** @var bool */
	public $dirty = true;

	/** @var int */
	public $minX = 0;
	/** @var int */
	public $minY = 0;
	/** @var int */
	public $maxX = 127;
	/** @var int */
	public $maxY = 127;

	public function __construct(Player $player){
		$this->player = $player;
	}

	public function getPacket(MapData $mapData) : ?ClientboundMapItemDataPacket{
		if($this->dirty){
			$this->dirty = false;

			$pk = new ClientboundMapItemDataPacket();
			$pk->originX = $pk->originY = $pk->originZ = 0;
			$pk->height = $pk->width = 128;
			$pk->dimensionId = $mapData->getDimension();
			$pk->scale = $mapData->getScale();
			$pk->colors = $mapData->getColors();
			$pk->mapId = $mapData->getId();
			$pk->decorations = $mapData->getDecorations();
			$pk->trackedEntities = $mapData->getTrackedObjects();

			$pk->cropTexture($this->minX, $this->minY, $this->maxX + 1 - $this->minX, $this->maxY + 1 - $this->minY);

			return $pk;
		}elseif(($this->packetSendTimer++ % 5) === 0){ // update decorations
			$pk = new ClientboundMapItemDataPacket();
			$pk->originX = $pk->originY = $pk->originZ = 0;
			$pk->height = $pk->width = 128;
			$pk->dimensionId = $mapData->getDimension();
			$pk->scale = $mapData->getScale();
			$pk->mapId = $mapData->getId();
			$pk->decorations = $mapData->getDecorations();
			$pk->trackedEntities = $mapData->getTrackedObjects();

			return $pk;
		}

		return null;
	}


	/**
	 * Calculates map canvas
	 *
	 * @param int $x
	 * @param int $y
	 */
	public function updateTextureAt(int $x, int $y) : void{
		if($this->dirty){
			$this->minX = min($this->minX, $x);
			$this->minY = min($this->minY, $y);
			$this->maxX = max($this->maxX, $x);
			$this->maxY = max($this->maxY, $y);
		}else{
			$this->dirty = true;

			$this->minX = $x;
			$this->minY = $y;
			$this->maxX = $x;
			$this->maxY = $y;
		}
	}
}