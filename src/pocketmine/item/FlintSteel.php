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

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\block\Portal;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use function assert;
use function min;

class FlintSteel extends Tool{
	/** @var Vector3 */
	private $temporalVector = null;

	public function __construct(int $meta = 0){
		parent::__construct(self::FLINT_STEEL, $meta, "Flint and Steel");
		if($this->temporalVector === null){
			$this->temporalVector = new Vector3(0, 0, 0);
		}
	}

	public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector) : bool{
		if($blockClicked->getId() === BlockIds::OBSIDIAN and $player->getServer()->netherEnabled){//黑曜石 4*5最小 23*23最大

			$tx = $blockClicked->getX();
			$ty = $blockClicked->getY();
			$tz = $blockClicked->getZ();
			$level = $blockClicked->getLevelNonNull();
			//x方向
			$x_max = $tx;//x最大值
			$x_min = $tx;//x最小值
			for($x = $tx + 1; $level->getBlock($this->temporalVector->setComponents($x, $ty, $tz))->getId() === BlockIds::OBSIDIAN; $x++){
				$x_max++;
			}
			for($x = $tx - 1; $level->getBlock($this->temporalVector->setComponents($x, $ty, $tz))->getId() === BlockIds::OBSIDIAN; $x--){
				$x_min--;
			}
			$count_x = $x_max - $x_min + 1;//x方向方块
			if($count_x >= 4 and $count_x <= 23){//4 23
				$x_max_y = $ty;//x最大值时的y最大值
				$x_min_y = $ty;//x最小值时的y最大值
				for($y = $ty; $level->getBlock($this->temporalVector->setComponents($x_max, $y, $tz))->getId() === BlockIds::OBSIDIAN; $y++){
					$x_max_y++;
				}
				for($y = $ty; $level->getBlock($this->temporalVector->setComponents($x_min, $y, $tz))->getId() === BlockIds::OBSIDIAN; $y++){
					$x_min_y++;
				}
				$y_max = min($x_max_y, $x_min_y) - 1;//y最大值
				$count_y = $y_max - $ty + 2;//方向方块

				if($count_y >= 5 and $count_y <= 23){//5 23
					$count_up = 0;//上面
					for($ux = $x_min; ($level->getBlock($this->temporalVector->setComponents($ux, $y_max, $tz))->getId() === BlockIds::OBSIDIAN and $ux <= $x_max); $ux++){
						$count_up++;
					}

					if($count_up == $count_x){
						for($px = $x_min + 1; $px < $x_max; $px++){
							for($py = $ty + 1; $py < $y_max; $py++){
								$level->setBlock($this->temporalVector->setComponents($px, $py, $tz), new Portal());
							}
						}
						if($player->isSurvival()){
							$this->applyDamage(1);
							$player->getInventory()->setItemInHand($this);
						}
						return true;
					}
				}
			}

			//z方向
			$z_max = $tz;//z最大值
			$z_min = $tz;//z最小值
			for($z = $tz + 1; $level->getBlock($this->temporalVector->setComponents($tx, $ty, $z))->getId() === BlockIds::OBSIDIAN; $z++){
				$z_max++;
			}
			for($z = $tz - 1; $level->getBlock($this->temporalVector->setComponents($tx, $ty, $z))->getId() === BlockIds::OBSIDIAN; $z--){
				$z_min--;
			}
			$count_z = $z_max - $z_min + 1;
			if($count_z >= 4 and $count_z <= 23){//4 23
				$z_max_y = $ty;//z最大值时的y最大值
				$z_min_y = $ty;//z最小值时的y最大值
				for($y = $ty; $level->getBlock($this->temporalVector->setComponents($tx, $y, $z_max))->getId() === BlockIds::OBSIDIAN; $y++){
					$z_max_y++;
				}
				for($y = $ty; $level->getBlock($this->temporalVector->setComponents($tx, $y, $z_min))->getId() === BlockIds::OBSIDIAN; $y++){
					$z_min_y++;
				}
				$y_max = min($z_max_y, $z_min_y) - 1;//y最大值
				$count_y = $y_max - $ty + 2;//方向方块
				if($count_y >= 5 and $count_y <= 23){//5 23
					$count_up = 0;//上面
					for($uz = $z_min; ($level->getBlock($this->temporalVector->setComponents($tx, $y_max, $uz))->getId() === BlockIds::OBSIDIAN and $uz <= $z_max); $uz++){
						$count_up++;
					}

					if($count_up == $count_z){
						for($pz = $z_min + 1; $pz < $z_max; $pz++){
							for($py = $ty + 1; $py < $y_max; $py++){
								$level->setBlock($this->temporalVector->setComponents($tx, $py, $pz), new Portal());
							}
						}
						if($player->isSurvival()){
							$this->applyDamage(1);
							$player->getInventory()->setItemInHand($this);
						}
						return true;
					}
				}
			}
		}

		if($blockReplace->getId() === self::AIR){
			$level = $player->getLevelNonNull();
			assert($level !== null);
			$level->setBlock($blockReplace, BlockFactory::get(Block::FIRE), true);
			$level->broadcastLevelSoundEvent($blockReplace->add(0.5, 0.5, 0.5), LevelSoundEventPacket::SOUND_IGNITE);

			$this->applyDamage(1);

			return true;
		}

		return false;
	}

	public function getMaxDurability() : int{
		return 65;
	}
}
