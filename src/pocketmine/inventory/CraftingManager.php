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

namespace pocketmine\inventory;

use Closure;
use Generator;
use pocketmine\crafting\CraftingDataBuildTask;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\network\mcpe\multiversion\block\BlockPalette;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\timings\Timings;
use function array_keys;
use function array_map;
use function array_diff;
use function array_diff_assoc;
use function scandir;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function usort;
use function krsort;
use const DIRECTORY_SEPARATOR;
use const pocketmine\RESOURCE_PATH;
use ReflectionClass;

class CraftingManager{
	/** @var ShapedRecipe[][] */
	protected $shapedRecipes = [];
	/** @var ShapelessRecipe[][] */
	protected $shapelessRecipes = [];
	/** @var FurnaceRecipe[] */
	protected $furnaceRecipes = [];
	/** @var BrewingRecipe[] */
	protected $brewingRecipes = [];

    /** @var BatchPacket[]|null[] */
    protected $craftingDataCache = [];
    /** @var CraftingDataBuildTask[]|null[] */
    protected $craftingDataBuildTask = [];
    /** @var Player[] */
    protected $waitingPlayers = [];

	public function __construct(){
		$this->init();
	}

	public function init() : void{
		self::registerBrewingStand();
		self::registerFurnaceRecipes();
		self::registerProtocolRecipes();
	}

	private function registerProtocolRecipes() : void{
		$itemDeserializerFunc = Closure::fromCallable([Item::class, 'jsonDeserialize']);

		$protocolRecipesDirectory = RESOURCE_PATH . DIRECTORY_SEPARATOR . "vanilla" . DIRECTORY_SEPARATOR . "recipes" . DIRECTORY_SEPARATOR . "protocols" . DIRECTORY_SEPARATOR;
        foreach(array_diff(scandir($protocolRecipesDirectory), ["..", "."]) as $protocol){
			$shapelessRecipes = json_decode(file_get_contents($protocolRecipesDirectory . $protocol . DIRECTORY_SEPARATOR . "shapeless_recipes.json"), true);
			$shapedRecipes = json_decode(file_get_contents($protocolRecipesDirectory . $protocol . DIRECTORY_SEPARATOR . "shaped_recipes.json"), true);

			foreach($shapelessRecipes as $recipe){
				if($recipe["block"] !== "crafting_table"){ //TODO: filter others out for now to avoid breaking economics
					continue;
				}
				$this->registerShapelessRecipe(new ShapelessRecipe(
					array_map($itemDeserializerFunc, $recipe["input"]),
					array_map($itemDeserializerFunc, $recipe["output"]),
					$recipe["priority"] ?? 50
				), (int) $protocol);
			}

			foreach($shapedRecipes as $recipe){
				if($recipe["block"] !== "crafting_table"){ //TODO: filter others out for now to avoid breaking economics
					continue;
				}
				$this->registerShapedRecipe(new ShapedRecipe(
					$recipe["shape"],
					array_map($itemDeserializerFunc, $recipe["input"]),
					array_map($itemDeserializerFunc, $recipe["output"]),
					$recipe["priority"] ?? 50
				), (int) $protocol);
			}
		}

		krsort($this->shapelessRecipes);
		krsort($this->shapedRecipes);
	}

	private function registerFurnaceRecipes() : void{
		$itemDeserializerFunc = Closure::fromCallable([Item::class, 'jsonDeserialize']);

		$recipes = json_decode(file_get_contents(RESOURCE_PATH . "vanilla" . DIRECTORY_SEPARATOR . "recipes" . DIRECTORY_SEPARATOR . "furnace_recipes.json"), true);
		foreach($recipes as $recipe){
			if($recipe["block"] !== "furnace"){ //TODO: filter others out for now to avoid breaking economics
				continue;
			}
			$this->registerFurnaceRecipe(new FurnaceRecipe(
				Item::jsonDeserialize($recipe["output"]),
				Item::jsonDeserialize($recipe["input"]))
			);
		}
	}

	/**
	 * @param int $protocol
	 */
	public function getShapelessRecipesListKey(int $protocol) : int{
		foreach($this->shapelessRecipes as $key => $recipes){
			if($protocol >= $key){
				return $key;
			}
		}

		return $protocol;
	}

	/**
	 * @param int $protocol
	 */
	public function getShapedRecipesListKey(int $protocol) : int{
		foreach($this->shapedRecipes as $key => $recipes){
			if($protocol >= $key){
				return $key;
			}
		}

		return $protocol;
	}

	/**
	 * @return int[]
	 */
	public function getShapelessRecipesProtocols() : array{
		return array_keys($this->shapelessRecipes);
	}

	/**
	 * @return int[]
	 */
	public function getShapedRecipesProtocols() : array{
		return array_keys($this->shapedRecipes);
	}

    /**
     * @param BatchPacket
     * @param int $protocol
     * 
     * @return void
     */
    public function buildCraftingDataCache(BatchPacket $payload, int $protocol) : void{
        unset($this->craftingDataBuildTask[$protocol]);
        $this->craftingDataCache[$protocol] = $payload;

        foreach($this->waitingPlayers[$protocol] as $player){
            if($player instanceof Player && $player->isConnected()){
                $this->requestCraftingDataPacket($player);
            }
        }

        unset($this->waitingPlayers[$protocol]);
    }

    /**
     * @param Player $player
     * 
     * @return void
     */
    public function requestCraftingDataPacket(Player $player) : void{
        if(!isset($this->craftingDataCache[$player->getCraftingProtocol()])){
            if(!isset($this->craftingDataBuildTask[$player->getCraftingProtocol()])){
                Timings::$craftingDataCacheRebuildTimer->startTiming();

                $player->getServer()->getAsyncPool()->submitTask($this->craftingDataBuildTask[$player->getCraftingProtocol()] = new CraftingDataBuildTask(
                    $this->getShapelessRecipes($player->getCraftingProtocol()),
                    $this->getShapedRecipes($player->getCraftingProtocol()),
                    $this->furnaceRecipes,
					$this->brewingRecipes,
                    $player->getCraftingProtocol(),
                    ($player->getCraftingProtocol() >= ProtocolInfo::PROTOCOL_429 ? BlockPalette::getPalette($player->getCraftingProtocol()) : null),
                    Server::getInstance()->networkCompressionLevel
                ));

                Timings::$craftingDataCacheRebuildTimer->stopTiming();
            }
            $this->waitingPlayers[$player->getCraftingProtocol()][] = $player;
            return;
        }

        $player->dataPacket($this->craftingDataCache[$player->getCraftingProtocol()]);
    }

	/**
	 * Function used to arrange Shapeless Recipe ingredient lists into a consistent order.
	 *
	 * @param Item $i1
	 * @param Item $i2
	 *
	 * @return int
	 */
	public static function sort(Item $i1, Item $i2){
		//Use spaceship operator to compare each property, then try the next one if they are equivalent.
		($retval = $i1->getId() <=> $i2->getId()) === 0 && ($retval = $i1->getDamage() <=> $i2->getDamage()) === 0 && ($retval = $i1->getCount() <=> $i2->getCount());

		return $retval;
	}

	/**
	 * @param Item[] $items
	 *
	 * @return Item[]
	 */
	private static function pack(array $items) : array{
		/** @var Item[] $result */
		$result = [];

		foreach($items as $i => $item){
			foreach($result as $otherItem){
				if($item->equals($otherItem)){
					$otherItem->setCount($otherItem->getCount() + $item->getCount());
					continue 2;
				}
			}

			//No matching item found
			$result[] = clone $item;
		}

		return $result;
	}

	private static function hashOutputs(array $outputs) : string{
		$outputs = self::pack($outputs);
		usort($outputs, [self::class, "sort"]);
		foreach($outputs as $o){
			//this reduces accuracy of hash, but it's necessary to deal with recipe book shift-clicking stupidity
			$o->setCount(1);
		}

		return json_encode($outputs);
	}

	/**
	 * @param int $protocol
	 * 
	 * @return ShapelessRecipe[][]
	 */
	public function getShapelessRecipes(int $protocol) : array{
		return $this->shapelessRecipes[$this->getShapelessRecipesListKey($protocol)] ?? [];
	}

	/**
	 * @param int $protocol
	 * 
	 * @return ShapedRecipe[][]
	 */
	public function getShapedRecipes(int $protocol) : array{
		return $this->shapedRecipes[$this->getShapedRecipesListKey($protocol)] ?? [];
	}

	/**
	 * @return FurnaceRecipe[]
	 */
	public function getFurnaceRecipes() : array{
		return $this->furnaceRecipes;
	}

	/**
	 * @param ShapedRecipe $recipe
	 * @param ?int $protocol
	 */
	public function registerShapedRecipe(ShapedRecipe $recipe, ?int $protocol = null) : void{
		if($protocol === null){
			$protocols = array_keys($this->shapedRecipes);
		}else{
			$protocols = [$protocol];
		}

		foreach($protocols as $protocol){
	    	$this->shapedRecipes[$protocol][self::hashOutputs($recipe->getResults())][] = $recipe;
		}

		$this->craftingDataCache = [];
	}

	/**
	 * @param ShapelessRecipe $recipe
	 * @param ?int $protocol
	 */
	public function registerShapelessRecipe(ShapelessRecipe $recipe, ?int $protocol = null) : void{
		if($protocol === null){
			$protocols = array_keys($this->shapelessRecipes);
		}else{
			$protocols = [$protocol];
		}

		foreach($protocols as $protocol){
	    	$this->shapelessRecipes[$protocol][self::hashOutputs($recipe->getResults())][] = $recipe;
		}

		$this->craftingDataCache = [];
	}

	/**
	 * @param FurnaceRecipe $recipe
	 */
	public function registerFurnaceRecipe(FurnaceRecipe $recipe) : void{
		$input = $recipe->getInput();
		$this->furnaceRecipes[$input->getId() . ":" . ($input->hasAnyDamageValue() ? "?" : $input->getDamage())] = $recipe;
		$this->craftingDataCache = [];
	}

	/**
	 * @param CraftingGrid $grid
	 * @param Item[]       $outputs
	 * @param int          $protocol
	 *
	 * @return CraftingRecipe|null
	 */
	public function matchRecipe(CraftingGrid $grid, array $outputs, int $protocol) : ?CraftingRecipe{
		//TODO: try to match special recipes before anything else (first they need to be implemented!)

		$outputHash = self::hashOutputs($outputs);
		$shapelessKey = $this->getShapelessRecipesListKey($protocol);
		$shapedKey = $this->getShapedRecipesListKey($protocol);

		if(isset($this->shapedRecipes[$shapedKey][$outputHash])){
			foreach($this->shapedRecipes[$shapedKey][$outputHash] as $recipe){
				if($recipe->matchesCraftingGrid($grid)){
					return $recipe;
				}
			}
		}

		if(isset($this->shapelessRecipes[$shapelessKey][$outputHash])){
			foreach($this->shapelessRecipes[$shapelessKey][$outputHash] as $recipe){
				if($recipe->matchesCraftingGrid($grid)){
					return $recipe;
				}
			}
		}

		return null;
	}

	/**
	 * @param Item[] $outputs
	 * @param int $protocol
	 *
	 * @return CraftingRecipe[]|Generator
	 */
	public function matchRecipeByOutputs(array $outputs, int $protocol) : Generator{
		//TODO: try to match special recipes before anything else (first they need to be implemented!)

		$outputHash = self::hashOutputs($outputs);
		$shapelessKey = $this->getShapelessRecipesListKey($protocol);
		$shapedKey = $this->getShapedRecipesListKey($protocol);

		if(isset($this->shapedRecipes[$shapedKey][$outputHash])){
			foreach($this->shapedRecipes[$shapedKey][$outputHash] as $recipe){
				yield $recipe;
			}
		}

		if(isset($this->shapelessRecipes[$shapelessKey][$outputHash])){
			foreach($this->shapelessRecipes[$shapelessKey][$outputHash] as $recipe){
				yield $recipe;
			}
		}
	}

	/**
	 * @param Item $input
	 *
	 * @return FurnaceRecipe|null
	 */
	public function matchFurnaceRecipe(Item $input) : ?FurnaceRecipe{
		return $this->furnaceRecipes[$input->getId() . ":" . $input->getDamage()] ?? $this->furnaceRecipes[$input->getId() . ":?"] ?? null;
	}

	/**
	 * @param Recipe $recipe
	 * @param ?int $protocol
	 */
	public function registerRecipe(Recipe $recipe, ?int $protocol = null) : void{
		$recipe->registerToCraftingManager($this, $protocol);
	}

	private function registerBrewingStand() : void{
		//Potion
		//WATER
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::AWKWARD, 1), Item::get(Item::NETHER_WART, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::THICK, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_MUNDANE, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::BLAZE_POWDER, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
		//To WEAKNESS
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::MUNDANE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::THICK, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LONG_MUNDANE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_WEAKNESS, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WEAKNESS, 1)));
		//GHAST_TEAR and BLAZE_POWDER
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::REGENERATION, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_REGENERATION, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::REGENERATION, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRONG_REGENERATION, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::REGENERATION, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRENGTH, 1), Item::get(Item::BLAZE_POWDER, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_STRENGTH, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::STRENGTH, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRONG_STRENGTH, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::STRENGTH, 1)));
		//SPIDER_EYE GLISTERING_MELON and PUFFERFISH
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::POISON, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_POISON, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::POISON, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRONG_POISON, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::POISON, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HEALING, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRONG_HEALING, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::HEALING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WATER_BREATHING, 1), Item::get(Item::PUFFERFISH, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_WATER_BREATHING, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WATER_BREATHING, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::WATER_BREATHING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::HEALING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::POISON, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRONG_HARMING, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::HARMING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRONG_HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::STRONG_HEALING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRONG_HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LONG_POISON, 1)));
		//SUGAR MAGMA_CREAM and RABBIT_FOOT
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SWIFTNESS, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_SWIFTNESS, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRONG_SWIFTNESS, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::FIRE_RESISTANCE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_FIRE_RESISTANCE, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::FIRE_RESISTANCE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LEAPING, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_LEAPING, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::LEAPING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRONG_LEAPING, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::LEAPING, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::FIRE_RESISTANCE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LEAPING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LONG_FIRE_RESISTANCE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LONG_LEAPING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LONG_SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SLOWNESS, 1)));
		//GOLDEN_CARROT
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::NIGHT_VISION, 1), Item::get(Item::GOLDEN_CARROT, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_NIGHT_VISION, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::NIGHT_VISION, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::INVISIBILITY, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::NIGHT_VISION, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_INVISIBILITY, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::INVISIBILITY, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_INVISIBILITY, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LONG_NIGHT_VISION, 1)));
		//SPLASH_POTION
		//WATER
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1), Item::get(Item::NETHER_WART, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::THICK, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_MUNDANE, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::BLAZE_POWDER, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
		//To WEAKNESS
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::THICK, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LONG_MUNDANE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_WEAKNESS, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WEAKNESS, 1)));
		//GHAST_TEAR and BLAZE_POWDER
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::REGENERATION, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_REGENERATION, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::REGENERATION, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRONG_REGENERATION, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::REGENERATION, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRENGTH, 1), Item::get(Item::BLAZE_POWDER, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_STRENGTH, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::STRENGTH, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRONG_STRENGTH, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::STRENGTH, 1)));
		//SPIDER_EYE GLISTERING_MELON and PUFFERFISH
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::POISON, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_POISON, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::POISON, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRONG_POISON, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::POISON, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::HEALING, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRONG_HEALING, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::HEALING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::WATER_BREATHING, 1), Item::get(Item::PUFFERFISH, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_WATER_BREATHING, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BREATHING, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BREATHING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::HEALING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::POISON, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRONG_HARMING, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::HARMING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRONG_HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::STRONG_HEALING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRONG_HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LONG_POISON, 1)));
		//SUGAR MAGMA_CREAM and RABBIT_FOOT
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SWIFTNESS, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_SWIFTNESS, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRONG_SWIFTNESS, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::FIRE_RESISTANCE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_FIRE_RESISTANCE, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::FIRE_RESISTANCE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LEAPING, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_LEAPING, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LEAPING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRONG_LEAPING, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LEAPING, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::FIRE_RESISTANCE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LEAPING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LONG_FIRE_RESISTANCE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LONG_LEAPING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LONG_SWIFTNESS, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1)));
		//GOLDEN_CARROT
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::NIGHT_VISION, 1), Item::get(Item::GOLDEN_CARROT, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_NIGHT_VISION, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::NIGHT_VISION, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::INVISIBILITY, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::NIGHT_VISION, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_INVISIBILITY, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::INVISIBILITY, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_INVISIBILITY, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LONG_NIGHT_VISION, 1)));

		$ref = new ReflectionClass(Potion::class);
		$potions = array_diff_assoc($ref->getConstants(), $ref->getParentClass()->getConstants());
		foreach($potions as $potion){
			$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, $potion, 1), Item::get(Item::GUNPOWDER, 0, 1), Item::get(Item::POTION, $potion, 1)));
			$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::LINGERING_POTION, $potion, 1), Item::get(Item::DRAGON_BREATH, 0, 1), Item::get(Item::SPLASH_POTION, $potion, 1)));
		}
	}

	/**
	 * @param BrewingRecipe $recipe
	 */
	public function registerBrewingRecipe(BrewingRecipe $recipe) : void{
		$input = $recipe->getInput();
		$potion = $recipe->getPotion();
		$this->brewingRecipes[$input->getId() . ":" . ($input->getDamage() === null ? "0" : $input->getDamage()) . ":" . $potion->getId() . ":" . ($potion->getDamage() === null ? "0" : $potion->getDamage())] = $recipe;
	}

	/**
	 * @param Item $input
	 * @param Item $potion
	 *
	 * @return ?BrewingRecipe
	 */
	public function matchBrewingRecipe(Item $input, Item $potion) : ?BrewingRecipe{
		$subscript = $input->getId() . ":" . ($input->getDamage() === null ? "0" : $input->getDamage()) . ":" . $potion->getId() . ":" . ($potion->getDamage() === null ? "0" : $potion->getDamage());
		if(isset($this->brewingRecipes[$subscript])){
			return $this->brewingRecipes[$subscript];
		}

		return null;
	}
}
