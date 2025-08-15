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

namespace pocketmine\level\biome;

use pocketmine\block\BlockIds;
use pocketmine\block\Flower as FlowerBlock;
use pocketmine\entity\passive\Horse;
use pocketmine\level\generator\populator\Flower;
use pocketmine\level\generator\populator\TallGrass;

class PlainBiome extends GrassyBiome{

	public function __construct(){
		parent::__construct();

		$tallGrass = new TallGrass();
		$tallGrass->setBaseAmount(12);

		$flower = new Flower();
		$flower->setBaseAmount(2);
		$flower->addType([BlockIds::DANDELION, 0]);
		$flower->addType([BlockIds::RED_FLOWER, FlowerBlock::TYPE_POPPY]);
		$flower->addType([BlockIds::RED_FLOWER, FlowerBlock::TYPE_AZURE_BLUET]);
		$flower->addType([BlockIds::RED_FLOWER, FlowerBlock::TYPE_RED_TULIP]);
		$flower->addType([BlockIds::RED_FLOWER, FlowerBlock::TYPE_ORANGE_TULIP]);
		$flower->addType([BlockIds::RED_FLOWER, FlowerBlock::TYPE_WHITE_TULIP]);
		$flower->addType([BlockIds::RED_FLOWER, FlowerBlock::TYPE_PINK_TULIP]);
		$flower->addType([BlockIds::RED_FLOWER, FlowerBlock::TYPE_OXEYE_DAISY]);

		$this->addPopulator($tallGrass);
		$this->addPopulator($flower);

		$this->setElevation(63, 68);

		$this->temperature = 0.8;
		$this->rainfall = 0.4;

		$this->spawnableCreatureList[] = new SpawnListEntry(Horse::class, 5, 2, 6);
	}

	public function getName() : string{
		return "Plains";
	}
}
