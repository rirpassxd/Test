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

namespace pocketmine\level\generator\populator;

use pocketmine\block\BlockIds;
use pocketmine\level\ChunkManager;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use function cos;
use function floor;
use function pi;
use function sin;

class Cave extends Populator {
	/**
	 * @param ChunkManager $level
	 * @param int          $chunkX
	 * @param int          $chunkZ
	 * @param Random       $random
	 *
	 * @return mixed
	 */
	public function populate(ChunkManager $level, int $chunkX, int $chunkZ, Random $random){
		$overLap = 8;
		$firstSeed = $random->nextInt();
		$secondSeed = $random->nextInt();
		for($cxx = 0; $cxx < 1; $cxx++){
			for($czz = 0; $czz < 1; $czz++){
				$dcx = $chunkX + $cxx;
				$dcz = $chunkZ + $czz;
				for($cxxx = -$overLap; $cxxx <= $overLap; $cxxx++){
					for($czzz = -$overLap; $czzz <= $overLap; $czzz++){
						$dcxx = $dcx + $cxxx;
						$dczz = $dcz + $czzz;
						$this->pop($level, $dcxx, $dczz, $dcx, $dcz, new Random(($dcxx * $firstSeed) ^ ($dczz * $secondSeed) ^ $random->getSeed()));
					}
				}
			}
		}
	}

	/**
	 * @param ChunkManager $level
	 * @param int          $x
	 * @param int          $z
	 * @param int          $chunkX
	 * @param int          $chunkZ
	 * @param Random       $random
	 */
	private function pop(ChunkManager $level, int $x, int $z, int $chunkX, int $chunkZ, Random $random) : void{
		$c = $level->getChunk($x, $z);
		$oC = $level->getChunk($chunkX, $chunkZ);
		if($c === null or $oC === null or ($c !== null and !$c->isGenerated()) or ($oC !== null and !$oC->isGenerated())){
			return;
		}
		$chunk = new Vector3($x << 4, 0, $z << 4);
		$originChunk = new Vector3($chunkX << 4, 0, $chunkZ << 4);
		if($random->nextBoundedInt(15) !== 0){
			return;
		}

		$numberOfCaves = $random->nextBoundedInt($random->nextBoundedInt($random->nextBoundedInt(40) + 1) + 1);
		for($caveCount = 0; $caveCount < $numberOfCaves; $caveCount++){
			$target = new Vector3($chunk->getX() + $random->nextBoundedInt(16), $random->nextBoundedInt($random->nextBoundedInt(120) + 8), $chunk->getZ() + $random->nextBoundedInt(16));

			$numberOfSmallCaves = 1;

			if($random->nextBoundedInt(4) === 0){
				$this->generateLargeCaveBranch($level, $originChunk, $target, new Random($random->nextInt()));
				$numberOfSmallCaves += $random->nextBoundedInt(4);
			}

			for($count = 0; $count < $numberOfSmallCaves; $count++){
				$randomHorizontalAngle = $random->nextFloat() * pi() * 2;
				$randomVerticalAngle = (($random->nextFloat() - 0.5) * 2) / 8;
				$horizontalScale = $random->nextFloat() * 2 + $random->nextFloat();

				if($random->nextBoundedInt(10) === 0){
					$horizontalScale *= $random->nextFloat() * $random->nextFloat() * 3 + 1;
				}

				$this->generateCaveBranch($level, $originChunk, $target, $horizontalScale, 1, $randomHorizontalAngle, $randomVerticalAngle, 0, 0, new Random($random->nextInt()));
			}
		}
	}

	/**
	 * @param ChunkManager $level
	 * @param Vector3      $chunk
	 * @param Vector3      $target
	 * @param float|int    $horizontalScale
	 * @param float|int    $verticalScale
	 * @param float|int    $horizontalAngle
	 * @param float|int    $verticalAngle
	 * @param int          $startingNode
	 * @param int          $nodeAmount
	 * @param Random       $random
	 */
	private function generateCaveBranch(ChunkManager $level, Vector3 $chunk, Vector3 $target, mixed $horizontalScale, mixed $verticalScale, mixed $horizontalAngle, mixed $verticalAngle, int $startingNode, int $nodeAmount, Random $random) : void{
		$middle = new Vector3($chunk->getX() + 8, 0, $chunk->getZ() + 8);
		$horizontalOffset = 0;
		$verticalOffset = 0;

		if($nodeAmount <= 0){
			$size = 7 * 16;
			$nodeAmount = $size - $random->nextBoundedInt($size / 4);
		}

		$intersectionMode = ($random->nextInt() % (int) ($nodeAmount / 2));
		$intersectionMode = $intersectionMode + $nodeAmount / 4;
		$extraVerticalScale = $random->nextBoundedInt(6) === 0;

		if($startingNode === -1){
			$startingNode = $nodeAmount / 2;
			$lastNode = true;
		}else{
			$lastNode = false;
		}

		for(; $startingNode < $nodeAmount; $startingNode++){
			$horizontalSize = 1.5 + sin($startingNode * pi() / $nodeAmount) * $horizontalScale;
			$verticalSize = $horizontalSize * $verticalScale;
			$target = $target->add(self::getDirection3D($horizontalAngle, $verticalAngle));
			if($extraVerticalScale){
				$verticalAngle *= 0.92;
			}else{
				$verticalScale *= 0.7;
			}

			$verticalAngle += $verticalOffset * 0.1;
			$horizontalAngle += $horizontalOffset * 0.1;
			$verticalOffset *= 0.9;
			$horizontalOffset *= 0.75;
			$verticalOffset += ($random->nextFloat() - $random->nextFloat()) * $random->nextFloat() * 2;
			$horizontalOffset += ($random->nextFloat() - $random->nextFloat()) * $random->nextFloat() * 4;

			if(!$lastNode){
				if($startingNode === $intersectionMode and $horizontalScale > 1 and $nodeAmount > 0){
					$this->generateCaveBranch($level, $chunk, $target, $random->nextFloat() * 0.5 + 0.5, 1, $horizontalAngle - pi() / 2, $verticalAngle / 3, $startingNode, $nodeAmount, new Random($random->nextInt()));
					$this->generateCaveBranch($level, $chunk, $target, $random->nextFloat() * 0.5 + 0.5, 1, $horizontalAngle - pi() / 2, $verticalAngle / 3, $startingNode, $nodeAmount, new Random($random->nextInt()));
					return;
				}

				if($random->nextBoundedInt(4) === 0){
					continue;
				}
			}

			$xOffset = $target->getX() - $middle->getX();
			$zOffset = $target->getZ() - $middle->getZ();
			$nodesLeft = $nodeAmount - $startingNode;
			$offsetHorizontalScale = $horizontalScale + 18;

			if((($xOffset * $xOffset + $zOffset * $zOffset) - $nodesLeft * $nodesLeft) > ($offsetHorizontalScale * $offsetHorizontalScale)){
				return;
			}

			if($target->getX() < ($middle->getX() - 16 - $horizontalSize * 2)
				or $target->getZ() < ($middle->getZ() - 16 - $horizontalSize * 2)
				or $target->getX() > ($middle->getX() + 16 + $horizontalSize * 2)
				or $target->getZ() > ($middle->getZ() + 16 + $horizontalSize * 2)
			){
				continue;
			}

			$start = new Vector3(floor($target->getX() - $horizontalSize) - $chunk->getX() - 1, floor($target->getY() - $verticalSize) - 1, floor($target->getZ() - $horizontalSize) - $chunk->getZ() - 1);
			$end = new Vector3(floor($target->getX() + $horizontalSize) - $chunk->getX() + 1, floor($target->getY() + $verticalSize) + 1, floor($target->getZ() + $horizontalSize) - $chunk->getZ() + 1);
			$node = new CaveNode($level, $chunk, $start, $end, $target, $verticalSize, $horizontalSize);

			if($node->canPlace()){
				$node->place();
			}

			if($lastNode){
				break;
			}
		}
	}

	/**
	 * @param ChunkManager $level
	 * @param Vector3      $chunk
	 * @param Vector3      $target
	 * @param Random       $random
	 */
	private function generateLargeCaveBranch(ChunkManager $level, Vector3 $chunk, Vector3 $target, Random $random) : void{
		$this->generateCaveBranch($level, $chunk, $target, $random->nextFloat() * 6 + 1, 0.5, 0, 0, -1, -1, $random);
	}

	/**
	 * @param $azimuth
	 * @param $inclination
	 *
	 * @return Vector3
	 */
	public static function getDirection3D($azimuth, $inclination) : Vector3{
		$yFact = cos($inclination);
		return new Vector3($yFact * cos($azimuth), sin($inclination), $yFact * sin($azimuth));
	}
}

class CaveNode{
	/** @var ChunkManager */
	private $level;
	/** @var Vector3 */
	private $chunk;
	/** @var Vector3 */
	private $start;
	/** @var Vector3 */
	private $end;
	/** @var Vector3 */
	private $target;
	private $verticalSize;
	private $horizontalSize;

	/**
	 * CaveNode constructor.
	 *
	 * @param ChunkManager $level
	 * @param Vector3      $chunk
	 * @param Vector3      $start
	 * @param Vector3      $end
	 * @param Vector3      $target
	 * @param float|int    $verticalSize
	 * @param float|int    $horizontalSize
	 */
	public function __construct(ChunkManager $level, Vector3 $chunk, Vector3 $start, Vector3 $end, Vector3 $target, mixed $verticalSize, mixed $horizontalSize){
		$this->level = $level;
		$this->chunk = $chunk;
		$this->start = $this->clamp($start);
		$this->end = $this->clamp($end);
		$this->target = $target;
		$this->verticalSize = $verticalSize;
		$this->horizontalSize = $horizontalSize;
	}

	/**
	 * @param Vector3 $pos
	 *
	 * @return Vector3
	 */
	private function clamp(Vector3 $pos) : Vector3{
		return new Vector3(
			self::mathClamp($pos->getFloorX(), 0, 16),
			self::mathClamp($pos->getFloorY(), 1, 120),
			self::mathClamp($pos->getFloorZ(), 0, 16)
		);
	}

	/**
	 * @param int $value
	 * @param int $low
	 * @param int $high
	 *
	 * @return mixed
	 */
	public static function mathClamp(int $value, int $low, int $high) : int{
		return min($high, max($low, $value));
	}

	/**
	 * @return bool
	 */
	public function canPlace() : bool{
		for($x = $this->start->getFloorX(); $x < $this->end->getFloorX(); $x++){
			for($z = $this->start->getFloorZ(); $z < $this->end->getFloorZ(); $z++){
				for($y = $this->end->getFloorY() + 1; $y >= $this->start->getFloorY() - 1; $y--){
					$blockId = $this->level->getBlockIdAt($this->chunk->getX() + $x, $y, $this->chunk->getZ() + $z);
					if($blockId === BlockIds::WATER or $blockId === BlockIds::STILL_WATER){
						return false;
					}
					if($y !== ($this->start->getFloorY() - 1) and $x !== ($this->start->getFloorX()) and $x !== ($this->end->getFloorX() - 1) and $z !== ($this->start->getFloorZ()) and $z !== ($this->end->getFloorZ() - 1)){
						$y = $this->start->getFloorY();
					}
				}
			}
		}
		return true;
	}

	public function place() : void{
		for($x = $this->start->getFloorX(); $x < $this->end->getFloorX(); $x++){
			$xOffset = ($this->chunk->getX() + $x + 0.5 - $this->target->getX()) / $this->horizontalSize;
			for($z = $this->start->getFloorZ(); $z < $this->end->getFloorZ(); $z++){
				$zOffset = ($this->chunk->getZ() + $z + 0.5 - $this->target->getZ()) / $this->horizontalSize;
				if(($xOffset * $xOffset + $zOffset * $zOffset) >= 1){
					continue;
				}
				for($y = $this->end->getFloorY() - 1; $y >= $this->start->getFloorY(); $y--){
					$yOffset = ($y + 0.5 - $this->target->getY()) / $this->verticalSize;
					if($yOffset > -0.7 and ($xOffset * $xOffset + $yOffset * $yOffset + $zOffset * $zOffset) < 1){
						$xx = $this->chunk->getX() + $x;
						$zz = $this->chunk->getZ() + $z;
						$blockId = $this->level->getBlockIdAt($xx, $y, $zz);
						if($blockId === BlockIds::STONE or $blockId === BlockIds::DIRT or $blockId === BlockIds::GRASS){
							if($y < 10){
								$this->level->setBlockIdAt($xx, $y, $zz, BlockIds::STILL_LAVA);
							}else{
								if($blockId === BlockIds::GRASS and $this->level->getBlockIdAt($xx, $y - 1, $zz) === BlockIds::DIRT){
									$this->level->setBlockIdAt($xx, $y - 1, $zz, BlockIds::GRASS);
								}
								$this->level->setBlockIdAt($xx, $y, $zz, BlockIds::AIR);
							}
						}
					}
				}
			}
		}
	}
}