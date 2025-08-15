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

namespace pocketmine\entity;

use pocketmine\math\Vector3;
use function abs;
use function asin;
use function atan;
use function rad2deg;

abstract class Creature extends Living{
	/**
	 * @param int $distance
	 *
	 * @return bool
	 */
	public function willMove(int $distance = 36) : bool{
		foreach($this->getViewers() as $viewer){
			if($this->distance($viewer->getLocation()) <= $distance) return true;
		}
		return false;
	}

	/**
	 * @param $mx
	 * @param $mz
	 *
	 * @return float|int
	 * 获取yaw角度
	 */
	public function getMyYaw($mx, $mz){  //根据motion计算转向角度
		//转向计算
		if($mz == 0){  //斜率不存在
			if($mx < 0){
				$yaw = -90;
			}else{
				$yaw = 90;
			}
		}else{  //存在斜率
			if($mx >= 0 and $mz > 0){  //第一象限
				$atan = atan($mx / $mz);
				$yaw = rad2deg($atan);
			}elseif($mx >= 0 and $mz < 0){  //第二象限
				$atan = atan($mx / abs($mz));
				$yaw = 180 - rad2deg($atan);
			}elseif($mx < 0 and $mz < 0){  //第三象限
				$atan = atan($mx / $mz);
				$yaw = -(180 - rad2deg($atan));
			}elseif($mx < 0 and $mz > 0){  //第四象限
				$atan = atan(abs($mx) / $mz);
				$yaw = -(rad2deg($atan));
			}else{
				$yaw = 0;
			}
		}

		$yaw = -$yaw;
		return $yaw;
	}

	/**
	 * @param Vector3 $from
	 * @param Vector3 $to
	 *
	 * @return float|int
	 * 获取pitch角度
	 */
	public function getMyPitch(Vector3 $from, Vector3 $to){
		$distance = $from->distance($to);
		$height = $to->y - $from->y;
		if($height > 0){
			return -rad2deg(asin($height / $distance));
		}elseif($height < 0){
			return rad2deg(asin(-$height / $distance));
		}else{
			return 0;
		}
	}
}
