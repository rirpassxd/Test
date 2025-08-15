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

use InvalidArgumentException;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\MapDecoration;
use pocketmine\network\mcpe\protocol\types\MapTrackedObject;
use pocketmine\utils\Color;
use UnexpectedValueException;
use function chr;
use function count;
use function ord;
use function pack;
use function unpack;

class ClientboundMapItemDataPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_MAP_ITEM_DATA_PACKET;

	public const BITFLAG_TEXTURE_UPDATE = 0x02;
	public const BITFLAG_DECORATION_UPDATE = 0x04;

	/** @var int */
	public $mapId;
	/** @var int */
	public $type;
	/** @var int */
	public $dimensionId = DimensionIds::OVERWORLD;
	/** @var bool */
	public $isLocked = false;

    /** @var int */
    public $originX;
    /** @var int */
    public $originY;
    /** @var int */
    public $originZ;

	/** @var int[] */
	public $eids = [];
	/** @var int */
	public $scale;

	/** @var MapTrackedObject[] */
	public $trackedEntities = [];
	/** @var MapDecoration[] */
	public $decorations = [];

	/** @var int */
	public $width;
	/** @var int */
	public $height;
	/** @var int */
	public $xOffset = 0;
	/** @var int */
	public $yOffset = 0;
	/** @var Color[][] */
	public $colors = [];

	protected function decodePayload(){
		$this->mapId = $this->getEntityUniqueId();
		$this->type = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getUnsignedVarInt() : $this->getInt();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_135){
	    	$this->dimensionId = $this->getByte();
		}
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_351){
	    	$this->isLocked = $this->getBool();
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_544){
	    	    $this->getSignedBlockPosition($this->originX, $this->originY, $this->originZ);
	    	}
		}

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	if(($this->type & 0x08) !== 0){
		    	$count = $this->getUnsignedVarInt();
		    	for($i = 0; $i < $count; ++$i){
			    	$this->eids[] = $this->getEntityUniqueId();
		    	}
			}
		}

        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_140){
            $flags = (0x08 | self::BITFLAG_DECORATION_UPDATE | self::BITFLAG_TEXTURE_UPDATE);
        }else{
            $flags = (self::BITFLAG_DECORATION_UPDATE | self::BITFLAG_TEXTURE_UPDATE);
        }
		if(($this->type & ($flags)) !== 0){ //Decoration bitflag or colour bitflag
			$this->scale = $this->getByte();
		}

		if(($this->type & self::BITFLAG_DECORATION_UPDATE) !== 0){
		    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_135){
		    	for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			    	$object = new MapTrackedObject();
			    	$object->type = $this->getLInt();
			    	if($object->type === MapTrackedObject::TYPE_BLOCK){
				    	$this->getBlockPosition($object->x, $object->y, $object->z);
			    	}elseif($object->type === MapTrackedObject::TYPE_ENTITY){
				    	$object->entityUniqueId = $this->getEntityUniqueId();
			    	}else{
				    	throw new UnexpectedValueException("Unknown map object type $object->type");
			    	}
			    	$this->trackedEntities[] = $object;
		    	}
			}

			for($i = 0, $count = ($this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getUnsignedVarInt() : $this->getInt()); $i < $count; ++$i){
			    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_135){
			    	$icon = $this->getByte();
			    	$rotation = $this->getByte();
			    }else{
			    	$weird = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getVarInt() : $this->getInt();
			    	$rotation = $weird & 0x0f;
			    	$icon = $weird >> 4;
			    }
				$xOffset = $this->getByte();
				$yOffset = $this->getByte();
				$label = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getString() : $this->getShortString();
                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_135){
			    	$color = Color::fromABGR($this->getUnsignedVarInt());
                }else{
                    $color = Color::fromARGB($this->getLInt()); //already BE, don't need to reverse it again
                }
                $this->decorations[] = new MapDecoration($icon, $rotation, $xOffset, $yOffset, $label, $color);
			}
		}

		if(($this->type & self::BITFLAG_TEXTURE_UPDATE) !== 0){
		    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
		    	$this->width = $this->getVarInt();
		    	$this->height = $this->getVarInt();
		    	$this->xOffset = $this->getVarInt();
		    	$this->yOffset = $this->getVarInt();
		    }else{
		    	$this->width = $this->getInt();
		    	$this->height = $this->getInt();
		    	$this->xOffset = $this->getInt();
		    	$this->yOffset = $this->getInt();
		    }

            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_135){
		    	$count = $this->getUnsignedVarInt();
		    	if($count !== $this->width * $this->height){
			    	throw new UnexpectedValueException("Expected colour count of " . ($this->height * $this->width) . " (height $this->height * width $this->width), got $count");
		    	}
			}

			for($y = 0; $y < $this->height; ++$y){
				for($x = 0; $x < $this->width; ++$x){
					$this->colors[$y][$x] = Color::fromABGR($this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getUnsignedVarInt() : $this->getLInt());
				}
			}
		}
	}

	protected function encodePayload(){
		$this->putEntityUniqueId($this->mapId);

		$type = 0;
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	if(($eidsCount = count($this->eids)) > 0){
		    	$type |= 0x08;
	    	}
		}
		if(($decorationCount = count($this->decorations)) > 0){
			$type |= self::BITFLAG_DECORATION_UPDATE;
		}
		if(count($this->colors) > 0){
			$type |= self::BITFLAG_TEXTURE_UPDATE;
		}

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->putUnsignedVarInt($type);
		}else{
		    $this->putInt($type);
		}
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_135){
            $this->putByte($this->dimensionId);
		}
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_351){
            $this->putBool($this->isLocked);
	    	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_544){
	    	    $this->putSignedBlockPosition($this->originX, $this->originY, $this->originZ);
	    	}
		}

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	if(($type & 0x08) !== 0){ //TODO: find out what these are for
		    	$this->putUnsignedVarInt($eidsCount);
		    	foreach($this->eids as $eid){
			    	$this->putEntityUniqueId($eid);
		    	}
			}
		}

        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_140){
            $flags = (0x08 | self::BITFLAG_TEXTURE_UPDATE | self::BITFLAG_DECORATION_UPDATE);
        }else{
            $flags = (self::BITFLAG_TEXTURE_UPDATE | self::BITFLAG_DECORATION_UPDATE);
        }
		if(($type & ($flags)) !== 0){
			($this->buffer .= chr($this->scale));
		}

		if(($type & self::BITFLAG_DECORATION_UPDATE) !== 0){
		    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_135){
		    	$this->putUnsignedVarInt(count($this->trackedEntities));
		    	foreach($this->trackedEntities as $object){
                    $this->putLInt($object->type);
			    	if($object->type === MapTrackedObject::TYPE_BLOCK){
				    	$this->putBlockPosition($object->x, $object->y, $object->z);
			    	}elseif($object->type === MapTrackedObject::TYPE_ENTITY){
				    	$this->putEntityUniqueId($object->entityUniqueId);
			    	}else{
				    	throw new InvalidArgumentException("Unknown map object type $object->type");
			    	}
				}
			}

			if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
		    	$this->putUnsignedVarInt($decorationCount);
			}else{
			    $this->putInt($decorationCount);
			}
			foreach($this->decorations as $decoration){
			    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_135){
                    $this->putByte($decoration->getIcon());
                    $this->putByte($decoration->getRotation());
			    }else{
			        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
			            $this->putVarInt(($decoration->getRotation() & 0x0f) | ($decoration->getIcon() << 4));
			        }else{
			            $this->putInt(($decoration->getRotation() & 0x0f) | ($decoration->getIcon() << 4));
			        }
			    }
                $this->putByte($decoration->getXOffset());
                $this->putByte($decoration->getYOffset());
                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
			    	$this->putString($decoration->getLabel());
                }else{
                    $this->putShortString($decoration->getLabel());
                }
			   	if($this->getProtocol() >= ProtocolInfo::PROTOCOL_135){
			    	$this->putUnsignedVarInt($decoration->getColor()->toABGR());
				}else{
				    $this->putLInt($decoration->getColor()->toARGB());
				}
			}
		}

		if(($type & self::BITFLAG_TEXTURE_UPDATE) !== 0){
		    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
		    	$this->putVarInt($this->width);
		    	$this->putVarInt($this->height);
		    	$this->putVarInt($this->xOffset);
		    	$this->putVarInt($this->yOffset);
		    }else{
		    	$this->putInt($this->width);
		    	$this->putInt($this->height);
		    	$this->putInt($this->xOffset);
		    	$this->putInt($this->yOffset);
		    }

            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_135){
		    	$this->putUnsignedVarInt($this->width * $this->height); //list count, but we handle it as a 2D array... thanks for the confusion mojang
            }

			for($y = 0; $y < $this->height; ++$y){
				for($x = 0; $x < $this->width; ++$x){
				    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
				    	$this->putUnsignedVarInt($this->colors[$y][$x]->toABGR());
				    }else{
				        $this->putLInt($this->colors[$y][$x]->toABGR());
				    }
				}
			}
		}
	}

	/**
	 * Crops the texture to wanted size
	 *
	 * @param int $minX
	 * @param int $minY
	 * @param int $maxX
	 * @param int $maxY
	 */
	public function cropTexture(int $minX, int $minY, int $maxX, int $maxY) : void{
		$this->height = $maxY;
		$this->width = $maxX;
		$this->xOffset = $minX;
		$this->yOffset = $minY;
		$newColors = [];
		for($y = 0; $y < $maxY; $y++){
			for($x = 0; $x < $maxX; $x++){
				$newColors[$y][$x] = $this->colors[$minY + $y][$minX + $x];
			}
		}
		$this->colors = $newColors;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleClientboundMapItemData($this);
	}
}
