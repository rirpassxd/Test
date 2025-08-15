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

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Player;
use pocketmine\tile\DLDetector;

class DaylightSensor extends RedstoneSource{
	protected $id = self::DAYLIGHT_SENSOR;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
		return "Daylight Sensor";
	}

	public function getHardness() : float{
		return 0.2;
	}

	public function getFuelTime() : int{
		return 300;
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_AXE;
	}

    public function getBoundingBox() : AxisAlignedBB {
        if($this->boundingBox === null){
            $boundingBox = $this->recalculateBoundingBox();
            if ($boundingBox === null) {
                $this->boundingBox = new AxisAlignedBB(0, 0, 0, 1, 1, 1);
            } else {
                $this->boundingBox = $boundingBox;
            }
        }
        return $this->boundingBox;
    }

	public function canBeFlowedInto() : bool{
		return false;
	}

	protected function getTile() : DLDetector{
		$t = $this->getLevelNonNull()->getTile($this);
		if($t instanceof DLDetector){
			return $t;
		}else{
			return new DLDetector($this->getLevelNonNull(), DLDetector::createNBT($this, null));
		}
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		$this->getLevelNonNull()->setBlock($this, new DaylightSensorInverted(), true, true);
		$this->getTile()->onUpdate();

		return true;
	}

	public function isActivated(Block $from = null) : bool{
		return $this->getTile()->isActivated();
	}

	public function onBreak(Item $item, Player $player = null) : bool{
		$this->getLevelNonNull()->setBlock($this, new Air(), true, true);
		if($this->isActivated()){
			$this->deactivate();
		}

		return true;
	}

	public function getDrops(Item $item) : array{
		return [
			Item::get(self::DAYLIGHT_SENSOR, 0, 1)
		];
	}
}
