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

namespace pocketmine\network\mcpe\protocol;


use pocketmine\inventory\FurnaceRecipe;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\multiversion\inventory\ItemPalette;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\PotionContainerChangeRecipe;
use pocketmine\network\mcpe\protocol\types\PotionTypeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\MaterialReducerRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\MaterialReducerRecipeOutput;
use pocketmine\utils\Binary;
use UnexpectedValueException;
use function count;
use function pack;
use function str_repeat;
use function strlen;

class CraftingDataPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CRAFTING_DATA_PACKET;

	public const ENTRY_SHAPELESS = 0;
	public const ENTRY_SHAPED = 1;
	public const ENTRY_FURNACE = 2;
	public const ENTRY_FURNACE_DATA = 3;
	public const ENTRY_MULTI = 4; //TODO
	public const ENTRY_SHULKER_BOX = 5; //TODO
	public const ENTRY_SHAPELESS_CHEMISTRY = 6; //TODO
	public const ENTRY_SHAPED_CHEMISTRY = 7; //TODO

	/** @var object[] */
	public $entries = [];
	/** @var PotionTypeRecipe[] */
	public $potionTypeRecipes = [];
	/** @var PotionContainerChangeRecipe[] */
	public $potionContainerRecipes = [];
	/** @var MaterialReducerRecipe[] */
	public $materialReducerRecipes = [];
	/** @var bool */
	public $cleanRecipes = false;

	public $decodedEntries = [];

	public function clean(){
		$this->entries = [];
		$this->decodedEntries = [];
		return parent::clean();
	}

    protected function decodePayload() : void{
        $this->decodedEntries = [];
        $recipeCount = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getUnsignedVarInt() : $this->getInt();
        for($i = 0; $i < $recipeCount; ++$i){
            $entry = [];
            $entry["type"] = $recipeType = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getVarInt() : $this->getInt();
            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
                $this->getInt(); // buffer length
            }

            switch($recipeType){
                case self::ENTRY_SHAPELESS:
                case self::ENTRY_SHULKER_BOX:
                case self::ENTRY_SHAPELESS_CHEMISTRY:
                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_360){
                        $entry["recipe_id"] = $this->getString();
                    }
                    $ingredientCount = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getUnsignedVarInt() : $this->getInt();
                    /** @var Item */
                    $entry["input"] = [];
                    for($j = 0; $j < $ingredientCount; ++$j){
                        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_360){
                            $entry["input"][] = $in = $this->getRecipeIngredient();
                            $in->setCount(1); //TODO HACK: they send a useless count field which breaks the PM crafting system because it isn't always 1
                        }else{
                            $entry["input"][] = $this->getSlot(false);
                        }
                    }
                    $resultCount = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getUnsignedVarInt() : $this->getInt();
                    $entry["output"] = [];
                    for($k = 0; $k < $resultCount; ++$k){
                        $entry["output"][] = $this->getSlot(false);
                    }
                    $entry["uuid"] = $this->getUUID()->toString();
                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_350){
                        $entry["block"] = $this->getString();
                        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_360){
                            $entry["priority"] = $this->getVarInt();
                            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_685){
                                $unlockingContext = $this->getBool();
                                if(!$unlockingContext){
                                    for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; $i++){
                                        $this->getRecipeIngredient();
                                    }
                                }
                            }
                            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392){
                                $entry["net_id"] = $this->readRecipeNetId();
                            }
                        }
                    }

                    break;
                case self::ENTRY_SHAPED:
                case self::ENTRY_SHAPED_CHEMISTRY:
                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_360){
                        $entry["recipe_id"] = $this->getString();
                    }
                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
                        $entry["width"] = $this->getVarInt();
                        $entry["height"] = $this->getVarInt();
                    }else{
                        $entry["width"] = $this->getInt();
                        $entry["height"] = $this->getInt();
                    }
                    $count = $entry["width"] * $entry["height"];
                    $entry["input"] = [];
                    for($j = 0; $j < $count; ++$j){
                        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_360){
                            $entry["input"][] = $in = $this->getRecipeIngredient();
                            $in->setCount(1); //TODO HACK: they send a useless count field which breaks the PM crafting system
                        }else{
                            $entry["input"][] = $this->getSlot(false);
                        }
                    }
                    $resultCount = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getUnsignedVarInt() : $this->getInt();
                    $entry["output"] = [];
                    for($k = 0; $k < $resultCount; ++$k){
                        $entry["output"][] = $this->getSlot(false);
                    }
                    $entry["uuid"] = $this->getUUID()->toString();
                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_350){
                        $entry["block"] = $this->getString();
                        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_360){
                            $entry["priority"] = $this->getVarInt();
                            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392){
                                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_671){
                                    $entry["symmetric"] = $this->getBool();
                                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_685){
                                        $unlockingContext = $this->getBool();
                                        if(!$unlockingContext){
                                            for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; $i++){
                                                $this->getRecipeIngredient();
                                            }
                                        }
                                    }
                                }
                                $entry["net_id"] = $this->readRecipeNetId();
                            }
                        }
                    }

                    break;
                case self::ENTRY_FURNACE:
                case self::ENTRY_FURNACE_DATA:
                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
                        $inputId = $this->getVarInt();
                    }
                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
                        $palette = ItemPalette::getPalette($this->getProtocol());
                        if($recipeType === self::ENTRY_FURNACE){
                            [$inputId, $inputData] = $palette::getLegacyFromRuntimeIdWildcard($inputId, 0x7fff);
                        }else{
                            $inputData = $this->getVarInt();
                            [$inputId, $inputData] = $palette::getLegacyFromRuntimeIdWildcard($inputId, $inputData);
                        }
                    }else{
                        $inputData = -1;
                        if($recipeType === self::ENTRY_FURNACE_DATA){
                            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
                                $inputData = $this->getVarInt();
                            }else{
                                $value = $this->getInt();
                                $inputId = $value >> 16;
                                $damage = $value & 0xFFFF;
                            }
                            if($inputData === 0x7fff){
                                $inputData = -1;
                            }
                        }else{
                            $inputId = $this->getInt();
                        }
                    }
                    $entry["input"] = ItemFactory::get($inputId, $inputData);
                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_360){
                        $entry["output"] = $out = $this->getSlot(false);
                        if($out->getDamage() === 0x7fff){
                            $out->setDamage(0); //TODO HACK: some 1.12 furnace recipe outputs have wildcard damage values
                        }
                    }else{
                        $entry["output"] = $this->getSlot(false);
                    }
                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_350){
                        $entry["block"] = $this->getString();
                    }

                    break;
                case self::ENTRY_MULTI:
                    $entry["uuid"] = $this->getUUID()->toString();
                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_392){
                        $entry["net_id"] = $this->readRecipeNetId();
                    }
                    break;
                default:
                    throw new UnexpectedValueException("Unhandled recipe type $recipeType!"); //do not continue attempting to decode
            }
            $this->decodedEntries[] = $entry;
        }
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_385){
            for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
                $input = $this->getVarInt();
                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_401 && $this->getProtocol() !== ProtocolInfo::PROTOCOL_402){
                    $inputMeta = $this->getVarInt();
                }
                $ingredient = $this->getVarInt();
                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_401 && $this->getProtocol() !== ProtocolInfo::PROTOCOL_402){
                    $ingredientMeta = $this->getVarInt();
                }
                $output = $this->getVarInt();
                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_401 && $this->getProtocol() !== ProtocolInfo::PROTOCOL_402){
                    $outputMeta = $this->getVarInt();
                }
                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
                    $palette = ItemPalette::getPalette($this->getProtocol());
                    [$input, $inputMeta] = $palette::getLegacyFromRuntimeId($input, $inputMeta);
                    [$ingredient, $ingredientMeta] = $palette::getLegacyFromRuntimeId($ingredient, $ingredientMeta);
                    [$output, $outputMeta] = $palette::getLegacyFromRuntimeId($output, $outputMeta);
                }
                $this->potionTypeRecipes[] = new PotionTypeRecipe($input, $inputMeta ?? 0, $ingredient, $ingredientMeta ?? 0, $output, $outputMeta ?? 0);
            }
            for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
                $input = $this->getVarInt();
                $ingredient = $this->getVarInt();
                $output = $this->getVarInt();
                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
                    $palette = ItemPalette::getPalette($this->getProtocol());
                    [$input, ] = $palette::getLegacyFromRuntimeId($input, 0);
                    [$ingredient, ] = $palette::getLegacyFromRuntimeId($ingredient, 0);
                    [$output, ] = $palette::getLegacyFromRuntimeId($output, 0);
                }
                $this->potionContainerRecipes[] = new PotionContainerChangeRecipe($input, $ingredient, $output);
            }
            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_465){
                for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
                    $inputIdAndData = $this->getVarInt();
                    [$inputId, $inputMeta] = [$inputIdAndData >> 16, $inputIdAndData & 0x7fff];
                    $outputs = [];
                    for($j = 0, $outputCount = $this->getUnsignedVarInt(); $j < $outputCount; ++$j){
                        $outputItemId = $this->getVarInt();
                        $outputItemCount = $this->getVarInt();
                        $outputs[] = new MaterialReducerRecipeOutput($outputItemId, $outputItemCount);
                    }
                    $this->materialReducerRecipes[] = new MaterialReducerRecipe($inputId, $inputMeta, $outputs);
                }
            }
        }
        $this->cleanRecipes = $this->getBool();
    }

    private static function writeEntry($entry, NetworkBinaryStream $stream, int $pos, int $playerProtocol){
		if($entry instanceof ShapelessRecipe){
			return self::writeShapelessRecipe($entry, $stream, $pos, $playerProtocol);
		}elseif($entry instanceof ShapedRecipe){
			return self::writeShapedRecipe($entry, $stream, $pos, $playerProtocol);
		}elseif($entry instanceof FurnaceRecipe){
			return self::writeFurnaceRecipe($entry, $stream, $playerProtocol);
		}
		//TODO: add MultiRecipe

		return -1;
	}

	private static function writeShapelessRecipe(ShapelessRecipe $recipe, NetworkBinaryStream $stream, int $pos, int $playerProtocol){
	    if($playerProtocol >= ProtocolInfo::PROTOCOL_360){
            $stream->putString(Binary::writeInt($pos)); //some kind of recipe ID, doesn't matter what it is as long as it's unique
	    }
	    if($playerProtocol >= ProtocolInfo::PROTOCOL_90){
	    	$stream->putUnsignedVarInt($recipe->getIngredientCount());
	    }else{
	        $stream->putInt($recipe->getIngredientCount());
	    }
		foreach($recipe->getIngredientList() as $item){
		    if($playerProtocol >= ProtocolInfo::PROTOCOL_360){
		    	$stream->putRecipeIngredient($item);
		    }else{
		        $stream->putSlot($item, false);
		    }
		}

		$results = $recipe->getResults();
		if($playerProtocol >= ProtocolInfo::PROTOCOL_90){
	    	$stream->putUnsignedVarInt(count($results));
		}else{
		    $stream->putInt(count($results));
		}
		foreach($results as $item){
			$stream->putSlot($item, false);
		}

		$stream->put(str_repeat("\x00", 16)); //Null UUID
		if($playerProtocol >= ProtocolInfo::PROTOCOL_350){
	    	$stream->putString("crafting_table"); //TODO: blocktype (no prefix) (this might require internal API breaks)
	    	if($playerProtocol >= ProtocolInfo::PROTOCOL_361){
				$stream->putVarInt($recipe->getPriority());
	        	if($playerProtocol >= ProtocolInfo::PROTOCOL_392){
					if($playerProtocol >= ProtocolInfo::PROTOCOL_685){
                        $stream->putBool(true); //RecipeUnlockingRequirement
					}
	        	    $stream->writeRecipeNetId($pos); //TODO: ANOTHER recipe ID, only used on the network
	        	}
	    	}
		}

		return CraftingDataPacket::ENTRY_SHAPELESS;
	}

	private static function writeShapedRecipe(ShapedRecipe $recipe, NetworkBinaryStream $stream, int $pos, int $playerProtocol){
	    if($playerProtocol >= ProtocolInfo::PROTOCOL_360){
            $stream->putString(Binary::writeInt($pos)); //some kind of recipe ID, doesn't matter what it is as long as it's unique
	    }
	    if($playerProtocol >= ProtocolInfo::PROTOCOL_90){
	    	$stream->putVarInt($recipe->getWidth());
	    	$stream->putVarInt($recipe->getHeight());
	    }else{
	    	$stream->putInt($recipe->getWidth());
	    	$stream->putInt($recipe->getHeight());
	    }

		for($z = 0; $z < $recipe->getHeight(); ++$z){
			for($x = 0; $x < $recipe->getWidth(); ++$x){
			    $ingredient = $recipe->getIngredient($x, $z);
			    if($playerProtocol >= ProtocolInfo::PROTOCOL_360){
			    	$stream->putRecipeIngredient($ingredient);
			    }else{
			        $stream->putSlot($ingredient, false);
			    }
			}
		}

		$results = $recipe->getResults();
		if($playerProtocol >= ProtocolInfo::PROTOCOL_90){
	    	$stream->putUnsignedVarInt(count($results));
		}else{
		    $stream->putInt(count($results));
		}
		foreach($results as $item){
			$stream->putSlot($item, false);
		}

		$stream->put(str_repeat("\x00", 16)); //Null UUID
		if($playerProtocol >= ProtocolInfo::PROTOCOL_350){
	    	$stream->putString("crafting_table"); //TODO: blocktype (no prefix) (this might require internal API breaks)
	    	if($playerProtocol >= ProtocolInfo::PROTOCOL_361){
				$stream->putVarInt($recipe->getPriority());
	        	if($playerProtocol >= ProtocolInfo::PROTOCOL_392){
					if($playerProtocol >= ProtocolInfo::PROTOCOL_671){
						$stream->putBool(true); //symmetric
						if($playerProtocol >= ProtocolInfo::PROTOCOL_685){
                            $stream->putBool(true); //RecipeUnlockingRequirement
						}
					}
	        	    $stream->writeRecipeNetId($pos); //TODO: ANOTHER recipe ID, only used on the network
	        	}
	    	}
		}

		return CraftingDataPacket::ENTRY_SHAPED;
	}

	private static function writeFurnaceRecipe(FurnaceRecipe $recipe, NetworkBinaryStream $stream, int $playerProtocol){
	    $id = $recipe->getInput()->getId();
	    $damage = $recipe->getInput()->getDamage();
	    if($playerProtocol >= ProtocolInfo::PROTOCOL_419){
	        $palette = ItemPalette::getPalette($playerProtocol);
	    	if($recipe->getInput()->hasAnyDamageValue()){
		    	[$id, ] = $palette::getRuntimeFromLegacyId($id, 0);
		    	$damage = 0x7fff;
	    	}else{
		    	[$id, $damage] = $palette::getRuntimeFromLegacyId($id, $damage);
	    	}
		}
		if($playerProtocol >= ProtocolInfo::PROTOCOL_90){
	    	$stream->putVarInt($id);
		}
		if($playerProtocol >= ProtocolInfo::PROTOCOL_419){
		    $stream->putVarInt($damage);
		    $result = CraftingDataPacket::ENTRY_FURNACE_DATA;
		}else{
	    	$result = CraftingDataPacket::ENTRY_FURNACE;
	    	if(!$recipe->getInput()->hasAnyDamageValue()){ //Data recipe
	    	    if($playerProtocol >= ProtocolInfo::PROTOCOL_90){
		        	$stream->putVarInt($damage);
	    	    }else{
	    	        $stream->putInt(($recipe->getInput()->getId() << 16) | ($recipe->getInput()->getDamage()));
	    	    }
		    	$result = CraftingDataPacket::ENTRY_FURNACE_DATA;
	    	}elseif($playerProtocol < ProtocolInfo::PROTOCOL_90){
	    	    $stream->putInt($recipe->getInput()->getId());
	    	}
		}
		$stream->putSlot($recipe->getResult(), false);
		if($playerProtocol >= ProtocolInfo::PROTOCOL_350){
	    	$stream->putString("furnace"); //TODO: blocktype (no prefix) (this might require internal API breaks)
		}
		return $result;
	}

	public function addShapelessRecipe(ShapelessRecipe $recipe){
		$this->entries[] = $recipe;
	}

	public function addShapedRecipe(ShapedRecipe $recipe){
		$this->entries[] = $recipe;
	}

	public function addFurnaceRecipe(FurnaceRecipe $recipe){
		$this->entries[] = $recipe;
	}

	protected function encodePayload(){
	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->putUnsignedVarInt(count($this->entries));
	    }else{
	        $this->putInt(count($this->entries));
	    }

		$writer = new NetworkBinaryStream();
		$writer->setProtocol($this->getProtocol());
		$counter = 0;
		foreach($this->entries as $d){
			$entryType = self::writeEntry($d, $writer, $counter++, $this->getProtocol());
			if($entryType >= 0){
			    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
			    	$this->putVarInt($entryType);
			    }else{
			        $this->putInt($entryType);
			        $this->putInt(strlen($writer->getBuffer()));
			    }
                $this->put($writer->getBuffer());
			}else{
			    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
			    	$this->putVarInt(-1);
			    }else{
			        $this->putInt(-1);
			        $this->putInt(0);
			    }
			}

			$writer->reset();
		}
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_385){
	    	$this->putUnsignedVarInt(count($this->potionTypeRecipes));
	    	foreach($this->potionTypeRecipes as $recipe){
	    	    $input = $recipe->getInputItemId();
	    	    $inputMeta = $recipe->getInputItemMeta();
	    	    $ingredient = $recipe->getIngredientItemId();
	    	    $ingredientMeta = $recipe->getIngredientItemMeta();
	    	    $output = $recipe->getOutputItemId();
	    	    $outputMeta = $recipe->getOutputItemMeta();
	    	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
	    	        $palette = ItemPalette::getPalette($this->getProtocol());
	    	        [$input, $inputMeta] = $palette::getRuntimeFromLegacyId($input, $inputMeta);
	    	        [$ingredient, $ingredientMeta] = $palette::getRuntimeFromLegacyId($ingredient, $ingredientMeta);
	    	        [$output, $outputMeta] = $palette::getRuntimeFromLegacyId($output, $outputMeta);
	    	    }
		    	$this->putVarInt($input);
		    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_401 && $this->getProtocol() !== ProtocolInfo::PROTOCOL_402){
		        	$this->putVarInt($inputMeta);
		    	}
		    	$this->putVarInt($ingredient);
		    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_401 && $this->getProtocol() !== ProtocolInfo::PROTOCOL_402){
		        	$this->putVarInt($ingredientMeta);
		    	}
		    	$this->putVarInt($output);
		    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_401 && $this->getProtocol() !== ProtocolInfo::PROTOCOL_402){
		        	$this->putVarInt($outputMeta);
		    	}
	    	}
	    	$this->putUnsignedVarInt(count($this->potionContainerRecipes));
	    	foreach($this->potionContainerRecipes as $recipe){
	    	    $input = $recipe->getInputItemId();
	    	    $ingredient = $recipe->getIngredientItemId();
	    	    $output = $recipe->getOutputItemId();
	    	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
	    	        $palette = ItemPalette::getPalette($this->getProtocol());
	    	        [$input, ] = $palette::getRuntimeFromLegacyId($input, 0);
	    	        [$ingredient, ] = $palette::getRuntimeFromLegacyId($ingredient, 0);
	    	        [$output, ] = $palette::getRuntimeFromLegacyId($output, 0);
	    	    }
		    	$this->putVarInt($input);
		    	$this->putVarInt($ingredient);
		    	$this->putVarInt($output);
		    }
			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_465){
				$this->putUnsignedVarInt(count($this->materialReducerRecipes));
				foreach($this->materialReducerRecipes as $recipe){
					$this->putVarInt(($recipe->getInputItemId() << 16) | $recipe->getInputItemMeta());
					$this->putUnsignedVarInt(count($recipe->getOutputs()));
					foreach($recipe->getOutputs() as $output){
						$this->putVarInt($output->getItemId());
						$this->putVarInt($output->getCount());
					}
				}
			}
		}

        $this->putBool($this->cleanRecipes);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCraftingData($this);
	}
}
