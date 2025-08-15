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

namespace pocketmine\network\mcpe\protocol\types;

use Ahc\Json\Comment;
use InvalidArgumentException;
use pocketmine\entity\Skin;
use pocketmine\utils\Color;
use pocketmine\utils\UUID;
use SplFixedArray;
use function array_rand;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function json_last_error_msg;
use const pocketmine\RESOURCE_PATH;

class SerializedSkin{
    public const GEOMETRY_CUSTOM = "geometry.humanoid.custom";
    public const GEOMETRY_CUSTOM_SLIM = "geometry.humanoid.customSlim";

    public const ARM_SIZE_SLIM = "slim";
    public const ARM_SIZE_WIDE = "wide";

    public const DEFAULT_GEOMETRY_DATA_ENGINE_VERSION = "0.0.0";

    /** @var string[]|null */
    public static $defaultSkins = null;

	/** @var array[]|null */
	public static $skinIdToBedrockMap = null;

	public static function init() : void{
		$skinIdMap = json_decode(file_get_contents(RESOURCE_PATH . "/vanilla/skins/pw10_skins/skin_id_map.json"), true);
		foreach($skinIdMap as $skinId => $item){
			$geometryData = file_get_contents(RESOURCE_PATH . "/vanilla/skins/pw10_skins/geometry/" . $item["geometry"] . ".json");

			$data = [
				"geometry" => $item["geometry"],
				"geometryData" => $geometryData,
			];
			if(isset($item["cape"])){
				$capeData = file_get_contents(RESOURCE_PATH . "/vanilla/skins/pw10_skins/capes/" . $item["cape"] . ".skindata");

				$data["cape"] = $item["cape"];
				$data["capeData"] = $capeData;
			}
			self::$skinIdToBedrockMap[$skinId] = $data;
		}
	}

    public static function lazyInit() : void{
        if(self::$skinIdToBedrockMap === null){
            self::init();
        }
    }

    /**
     * @param string $skinId
     * 
     * @return bool
     */
    public static function isSkinIdPE(string $skinId) : bool{
        self::lazyInit();

        return isset(self::$skinIdToBedrockMap[$skinId]);
    }

    /**
     * @param Skin $skin
     * 
     * @return SerializedSkin
     */
    public static function fromSkin(Skin $skin) : SerializedSkin{
        self::lazyInit();

        $mapping = self::$skinIdToBedrockMap[$skin->getSkinId()] ?? null;
		if($mapping === null){
            return new self(
                $skin->getSkinId(),
                "",
                SkinImage::fromLegacy($skin->getSkinData()),
                "",
                $skin->getCapeData() === "" ? new SkinImage(0, 0, "") : SkinImage::fromLegacy($skin->getCapeData()),
                json_encode(["geometry" => ["default" => ($skin->getGeometryName() === "" ? self::GEOMETRY_CUSTOM : $skin->getGeometryName())]]),
                self::updateGeometry($skin->getGeometryData(), $skin->getGeometryName()),
                self::DEFAULT_GEOMETRY_DATA_ENGINE_VERSION,
                "",
                [],
                false,
                false,
                false,
                null,
                self::ARM_SIZE_WIDE,
                new Color(0, 0, 0),
                SplFixedArray::fromArray([]),
                SplFixedArray::fromArray([]),
                true,
                true
            );
        }else{
            return new self(
                $skin->getSkinId(),
                "",
                SkinImage::fromLegacy($skin->getSkinData()),
                $mapping["cape"] ?? "",
                ($mapping["capeData"] ?? $skin->getCapeData()) === "" ? new SkinImage(0, 0, "") : SkinImage::fromLegacy($mapping["capeData"] ?? $skin->getCapeData()),
                json_encode(["geometry" => ["default" => $mapping["geometry"]]]),
                $mapping["geometryData"],
                self::DEFAULT_GEOMETRY_DATA_ENGINE_VERSION,
                "",
                [],
                false,
                false,
                false,
                null,
                self::ARM_SIZE_WIDE,
                new Color(0, 0, 0),
                SplFixedArray::fromArray([]),
                SplFixedArray::fromArray([]),
                true,
                true
            );
        }
    }

    /**
     * @param string $skinGeometryData
     * @param string $skinGeometryName
     * 
     * @return string
     */
    public static function updateGeometry(string $skinGeometryData, string $skinGeometryName) : string{
        if($skinGeometryData === "" || $skinGeometryName === "" || $skinGeometryName === self::GEOMETRY_CUSTOM || $skinGeometryName === self::GEOMETRY_CUSTOM_SLIM){
            return "";
        }

        return $skinGeometryData;
    }

    /** @var string */
    protected $skinId;
	/** @var string */
	protected $playFabId;
    /** @var SkinImage */
    protected $skinImage;
    /** @var string */
    protected $capeId;
    /** @var SkinImage */
    protected $capeImage;
    /** @var string */
    protected $resourcePatch;
    /** @var string */
    protected $geometryData;
	/** @var string */
	protected $geometryDataEngineVersion;
    /** @var string */
    protected $animationData;
    /** @var SkinAnimation[] */
    protected $animationFrames;
    /** @var bool */
    protected $premiumSkin;
    /** @var bool */
    protected $personaSkin;
    /** @var bool */
    protected $capeOnClassicSkin;
    /** @var string|null */
    protected $fullSkinId;
    /** @var string */
    protected $armSize;
    /** @var Color */
    protected $skinColor;
    /** @var SplFixedArray */
    protected $personaPieces;
    /** @var SplFixedArray */
    protected $pieceTintColors;
    /** @var bool */
    protected $isTrusted = true;
    /** @var bool */
    protected $isPrimaryUser = true;
    /** @var bool */
    protected $override = true;

	/**
	 * @param string          $skinId
	 * @param string          $playFabId
	 * @param SkinImage       $skinImage
	 * @param string          $capeId
	 * @param SkinImage       $capeImage
	 * @param string          $resourcePatch
	 * @param string          $geometryData
	 * @param string          $geometryDataEngineVersion
	 * @param string          $animationData
	 * @param SkinAnimation[] $animationFrames
	 * @param bool            $premiumSkin
	 * @param bool            $personaSkin
	 * @param bool            $capeOnClassicSkin
	 * @param string|null     $fullSkinId
	 * @param string          $armSize
	 * @param Color           $skinColor
	 * @param SplFixedArray   $personaPieces
	 * @param SplFixedArray   $pieceTintColors
	 * @param bool            $isPrimaryUser
	 * @param bool            $override
	 */
    public function __construct(
        string $skinId,
        string $playFabId,
        SkinImage $skinImage,
        string $capeId,
        SkinImage $capeImage,
        string $resourcePatch,
        string $geometryData,
        string $geometryDataEngineVersion,
        string $animationData,
        array $animationFrames,
        bool $premiumSkin,
        bool $personaSkin,
        bool $capeOnClassicSkin,
        ?string $fullSkinId,
        string $armSize,
        Color $skinColor,
        SplFixedArray $personaPieces,
        SplFixedArray $pieceTintColors,
        bool $isPrimaryUser = true,
        bool $override = true
    ){
        if($geometryData !== ""){
            $decodedData = (new Comment())->decode($geometryData);
            if($decodedData === false){
                throw new InvalidArgumentException("Invalid geometry data (" . json_last_error_msg() . ")");
            }
            $geometryData = json_encode($decodedData);
        }

        $decodedPatch = json_decode($resourcePatch);
        if(!isset($decodedPatch->geometry->default)){
            throw new InvalidArgumentException("Invalid resource patch: $resourcePatch");
        }

        $resourcePatch = json_encode($decodedPatch);
        $this->skinId = $skinId;
        $this->playFabId = $playFabId;
        $this->skinImage = $skinImage;
        $this->capeId = $capeId;
        $this->capeImage = $capeImage;
        $this->resourcePatch = $resourcePatch;
        $this->geometryData = $geometryData;
		$this->geometryDataEngineVersion = $geometryDataEngineVersion;
        $this->animationData = $animationData;
        $this->animationFrames = $animationFrames;
        $this->premiumSkin = $premiumSkin;
        $this->personaSkin = $personaSkin;
        $this->capeOnClassicSkin = $capeOnClassicSkin;
        $this->fullSkinId = $this->generateFullSkinId();
        $this->armSize = $armSize;
        $this->skinColor = $skinColor;
        $this->personaPieces = $personaPieces;
        $this->pieceTintColors = $pieceTintColors;
        $this->isPrimaryUser = $isPrimaryUser;
        $this->override = $override;
    }

    /**
     * @return string
     */
    public function getSkinId() : string{
        return $this->skinId;
    }

    /**
     * @return string
     */
    public function getPlayFabId() : string{
        return $this->playFabId;
    }

    /**
     * @return SkinImage
     */
    public function getSkinImage() : SkinImage{
        return $this->skinImage;
    }

    /**
     * @return string
     */
    public function getCapeId() : string{
        return $this->capeId;
    }

    /**
     * @return SkinImage
     */
    public function getCapeImage() : SkinImage{
        return $this->capeImage;
    }

    /**
     * @return string
     */
    public function getResourcePatch() : string{
        return $this->resourcePatch;
    }

    /**
     * @return string
     */
    public function getGeometryData() : string{
        return $this->geometryData;
    }

    /**
     * @return string
     */
	public function getGeometryDataEngineVersion() : string{
	    return $this->geometryDataEngineVersion;
	}

    /**
     * @return string
     */
    public function getAnimationData() : string{
        return $this->animationData;
    }

    /**
     * @return SkinAnimation[]
     */
    public function getAnimationFrames() : array{
        return $this->animationFrames;
    }

    /**
     * @return bool
     */
    public function isPremiumSkin() : bool{
        return $this->premiumSkin;
    }

    /**
     * @return bool
     */
    public function isPersonaSkin() : bool{
        return $this->personaSkin;
    }

    /**
     * @return bool
     */
    public function isCapeOnClassicSkin() : bool{
        return $this->capeOnClassicSkin;
    }

    /**
     * @return bool
     */
    public function isPrimaryUser() : bool{
        return $this->isPrimaryUser;
    }

    /**
     * @return bool
     */
    public function isOverride() : bool{
        return $this->override;
    }

    /**
     * @return string
     */
    public function getFullSkinId() : string{
        return $this->fullSkinId;
    }

    /**
     * @return string
     */
    public function getArmSize() : string{
        return $this->armSize;
    }

    /**
     * @return Color
     */
    public function getSkinColor() : Color{
        return $this->skinColor;
    }

    /**
     * @return SplFixedArray
     */
    public function getPersonaPieces() : SplFixedArray{
        return $this->personaPieces;
    }

    /**
     * @return SplFixedArray
     */
    public function getPieceTintColors() : SplFixedArray{
        return $this->pieceTintColors;
    }

    /**
     * @return bool
     */
    public function isTrustedSkin() : bool{
        return $this->isTrusted;
    }

    /**
     * @param bool $isTrusted
     */
    public function setIsTrustedSkin(bool $isTrusted) : void{
        $this->isTrusted = $isTrusted;
    }

    /**
     * @return Skin
     */
    public function toSkin() : Skin{
        if(!(
            ($this->skinImage->getWidth() === 64 and ($this->skinImage->getHeight() === 32 or $this->skinImage->getHeight() === 64))
        or
            ($this->skinImage->getWidth() === 128 and ($this->skinImage->getHeight() === 128))
        )){
			self::$defaultSkins = self::$defaultSkins ?? [
				"steve" => new Skin("Standard_Steve", file_get_contents(RESOURCE_PATH . '/vanilla/skins/steve.skindata')),
				"alex" => new Skin("Standard_Alex", file_get_contents(RESOURCE_PATH . '/vanilla/skins/alex.skindata')),
			];

			$skin = self::$defaultSkins[array_rand(self::$defaultSkins)];
			$skin->setSerializedSkin($this);

			return $skin;
        }

        $skinGeometryData = (new Comment())->decode($this->geometryData, true);
        if(isset($skinGeometryData["format_version"])){
            unset($skinGeometryData["format_version"]);
            if(isset($skinGeometryData["minecraft:geometry"])){
                foreach($skinGeometryData["minecraft:geometry"] as $geometry){
                    $skinGeometryData[$geometry["description"]["identifier"]] = [
                        "texturewidth" => $geometry["description"]["texture_width"],
                        "textureheight" => $geometry["description"]["texture_height"],
                        "bones" => $geometry["bones"]
                    ];
                }

                unset($skinGeometryData["minecraft:geometry"]);
            }
            $skinGeometryData = json_encode($skinGeometryData);
        }else{
            unset($skinGeometryData);
        }

        $skin = new Skin(
            $this->skinId,
            $this->skinImage->getData(),
            $this->capeImage->getData(),
            json_decode($this->resourcePatch)->geometry->default ?? self::GEOMETRY_CUSTOM,
            $skinGeometryData ?? $this->geometryData
        );
        $skin->setSerializedSkin($this);

        return $skin;
    }

	/**
	 * Hack to fix skins conflict.
	 * Full skin ID must be unique for any set of data.
	 */
	public function generateFullSkinId() : string{
		return UUID::fromData(
			$this->skinId,
			$this->resourcePatch,
			$this->skinImage->getData(),
			$this->capeImage->getData(),
			$this->geometryData,
			(string) $this->premiumSkin,
			(string) $this->personaSkin,
			(string) $this->capeOnClassicSkin,
			$this->capeId
		)->toString();
	}

}
