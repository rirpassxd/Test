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
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\multiversion\MultiversionEnums;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\network\mcpe\protocol\types\InteractionMode;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use pocketmine\network\mcpe\protocol\types\ItemInteractionData;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\types\PlayerBlockAction;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionStopBreak;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionWithBlockInfo;
use pocketmine\network\mcpe\protocol\types\PlayMode;
use function assert;

class PlayerAuthInputPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_AUTH_INPUT_PACKET;

    /** @var Vector3 */
	private $position;
	/** @var float */
    private $pitch;
	/** @var float */
    private $yaw;
	/** @var float */
    private $headYaw;
	/** @var float */
    private $moveVecX;
	/** @var float */
    private $moveVecZ;
	/** @var int */
    private $inputFlags;
	/** @var int */
    private $inputMode;
	/** @var int */
    private $playMode;
	/** @var int */
    private $interactionMode;
	/** @var ?Vector3 */
    private $vrGazeDirection = null;
	/** @var Vector2 */
    private $interactRotation;
	/** @var int */
    private $tick;
	/** @var Vector3 */
    private $delta;
	/** @var ?ItemInteractionData */
    private $itemInteractionData = null;
	/** @var ?ItemStackRequest */
    private $itemStackRequest = null;
    /** @var PlayerBlockAction[]|null */
    private $blockActions = null;
	/** @var ?PlayerAuthInputVehicleInfo */
    private $vehicleInfo = null;
	/** @var float */
    private $analogMoveVecX;
	/** @var float */
    private $analogMoveVecZ;
	/** @var Vector3 */
    private $cameraOrientation;
	/** @var Vector2 */
    private $rawMove;

    /**
     * @param int                      $inputFlags @see PlayerAuthInputFlags
     * @param int                      $inputMode @see InputMode
     * @param int                      $playMode @see PlayMode
     * @param int                      $interactionMode @see InteractionMode
     * @param Vector3|null             $vrGazeDirection only used when PlayMode::VR
     * @param PlayerBlockAction[]|null $blockActions Blocks that the client has interacted with
     */
    public static function create(
        Vector3 $position,
        float $pitch,
        float $yaw,
        float $headYaw,
        float $moveVecX,
        float $moveVecZ,
        int $inputFlags,
        int $inputMode,
        int $playMode,
        int $interactionMode,
        ?Vector3 $vrGazeDirection,
        Vector2 $interactRotation,
        int $tick,
        Vector3 $delta,
        ?ItemInteractionData $itemInteractionData,
        ?ItemStackRequest $itemStackRequest,
        ?array $blockActions,
        ?PlayerAuthInputVehicleInfo $vehicleInfo,
        float $analogMoveVecX,
        float $analogMoveVecZ,
		Vector3 $cameraOrientation,
        Vector2 $rawMove,
    ) : self{
        if($playMode === PlayMode::VR and $vrGazeDirection === null){
            //yuck, can we get a properly written packet just once? ...
            throw new InvalidArgumentException("Gaze direction must be provided for VR play mode");
        }
        $result = new self;
        $result->position = $position->asVector3();
        $result->pitch = $pitch;
        $result->yaw = $yaw;
        $result->headYaw = $headYaw;
        $result->moveVecX = $moveVecX;
        $result->moveVecZ = $moveVecZ;

        $result->inputFlags = $inputFlags & ~((1 << PlayerAuthInputFlags::PERFORM_ITEM_STACK_REQUEST) | (1 << PlayerAuthInputFlags::PERFORM_ITEM_INTERACTION) | (1 << PlayerAuthInputFlags::PERFORM_BLOCK_ACTIONS));
        if($itemStackRequest !== null){
            $result->inputFlags |= 1 << PlayerAuthInputFlags::PERFORM_ITEM_STACK_REQUEST;
        }
        if($itemInteractionData !== null){
            $result->inputFlags |= 1 << PlayerAuthInputFlags::PERFORM_ITEM_INTERACTION;
        }
        if($blockActions !== null){
            $result->inputFlags |= 1 << PlayerAuthInputFlags::PERFORM_BLOCK_ACTIONS;
        }
        if($vehicleInfo !== null){
            $result->inputFlags |= 1 << PlayerAuthInputFlags::IN_CLIENT_PREDICTED_VEHICLE;
        }

        $result->inputMode = $inputMode;
        $result->playMode = $playMode;
        $result->interactionMode = $interactionMode;
        if($vrGazeDirection !== null){
            $result->vrGazeDirection = $vrGazeDirection->asVector3();
        }
        $result->interactRotation = $interactRotation;
        $result->tick = $tick;
        $result->delta = $delta;
        $result->itemInteractionData = $itemInteractionData;
        $result->itemStackRequest = $itemStackRequest;
        $result->blockActions = $blockActions;
        $result->vehicleInfo = $vehicleInfo;
        $result->analogMoveVecX = $analogMoveVecX;
        $result->analogMoveVecZ = $analogMoveVecZ;
        $result->cameraOrientation = $cameraOrientation;
        $result->rawMove = $rawMove;
        return $result;
    }

    public function getPosition() : Vector3{
        return $this->position;
    }

    public function getPitch() : float{
        return $this->pitch;
    }

    public function getYaw() : float{
        return $this->yaw;
    }

    public function getHeadYaw() : float{
        return $this->headYaw;
    }

    public function getMoveVecX() : float{
        return $this->moveVecX;
    }

    public function getMoveVecZ() : float{
        return $this->moveVecZ;
    }

    /**
     * @see PlayerAuthInputFlags
     */
    public function getInputFlags() : int{
        return $this->inputFlags;
    }

    /**
     * @see InputMode
     */
    public function getInputMode() : int{
        return $this->inputMode;
    }

    /**
     * @see PlayMode
     */
    public function getPlayMode() : int{
        return $this->playMode;
    }

    /**
     * @see InteractionMode
     */
    public function getInteractionMode() : int{
        return $this->interactionMode;
    }

    public function getVrGazeDirection() : ?Vector3{
        return $this->vrGazeDirection;
    }

    public function getInteractRotation() : Vector2{
        return $this->interactRotation;
    }

    public function getTick() : int{
        return $this->tick;
    }

    public function getDelta() : Vector3{
        return $this->delta;
    }

    public function getItemInteractionData() : ?ItemInteractionData{
        return $this->itemInteractionData;
    }

    public function getItemStackRequest() : ?ItemStackRequest{
        return $this->itemStackRequest;
    }

    /**
     * @return PlayerBlockAction[]|null
     */
    public function getBlockActions() : ?array{
        return $this->blockActions;
    }

    public function getVehicleInfo() : ?PlayerAuthInputVehicleInfo{ return $this->vehicleInfo; }

    public function getAnalogMoveVecX() : float{ return $this->analogMoveVecX; }

    public function getAnalogMoveVecZ() : float{ return $this->analogMoveVecZ; }

    public function getCameraOrientation() : Vector3{ return $this->cameraOrientation; }

    public function getRawMove() : Vector2{ return $this->rawMove; }

    public function hasFlag(int $flag) : bool{
        return ($this->inputFlags & (1 << $flag)) !== 0;
    }

	protected function decodePayload() : void{
        $this->pitch = $this->getLFloat();
		$this->yaw = $this->getLFloat();
		$this->position = $this->getVector3();
		$this->moveVecX = $this->getLFloat();
		$this->moveVecZ = $this->getLFloat();
		$this->headYaw = $this->getLFloat();
		$this->inputFlags = $this->getUnsignedVarLong();
		$this->inputMode = $this->getUnsignedVarInt();
		$this->playMode = $this->getUnsignedVarInt();

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_527){
		    $this->interactionMode = $this->getUnsignedVarInt();
		}

        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_748){
            $this->interactRotation = $this->getVector2();
        }else{
            if($this->playMode === PlayMode::VR){
                $this->vrGazeDirection = $this->getVector3();
            }
        }

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
            $this->tick = $this->getUnsignedVarLong();
            $this->delta = $this->getVector3();

            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_428){
                if($this->hasFlag(PlayerAuthInputFlags::PERFORM_ITEM_INTERACTION)){
                    $this->itemInteractionData = ItemInteractionData::read($this);
                }

                if($this->hasFlag(PlayerAuthInputFlags::PERFORM_ITEM_STACK_REQUEST)){
                    $this->itemStackRequest = ItemStackRequest::read($this);
                }

                if($this->hasFlag(PlayerAuthInputFlags::PERFORM_BLOCK_ACTIONS)){
                    $this->blockActions = [];
                    $max = $this->getVarInt();
                    for($i = 0; $i < $max; ++$i){
                        $actionType = MultiversionEnums::getPlayerActionName($this->getProtocol(), $this->getVarInt());
                        $this->blockActions[] = match(true){
                            PlayerBlockActionWithBlockInfo::isValidActionType($actionType) => PlayerBlockActionWithBlockInfo::read($this, $actionType),
                            $actionType === PlayerActionPacket::ACTION_STOP_BREAK => new PlayerBlockActionStopBreak(),
                            default => throw new InvalidArgumentException("Unexpected block action type $actionType")
                        };
                    }
                }

                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_649){
                    if($this->hasFlag(PlayerAuthInputFlags::IN_CLIENT_PREDICTED_VEHICLE)){
                        $this->vehicleInfo = PlayerAuthInputVehicleInfo::read($this);
                    }
                }

                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_575){
                    $this->analogMoveVecX = $this->getLFloat();
                    $this->analogMoveVecZ = $this->getLFloat();
                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_748){
                        $this->cameraOrientation = $this->getVector3();
                        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_766){
                            $this->rawMove = $this->getVector2();
                        }
                    }
                }
            }
        }
	}

	protected function encodePayload() : void{
        $this->putLFloat($this->pitch);
        $this->putLFloat($this->yaw);
		$this->putVector3($this->position);
        $this->putLFloat($this->moveVecX);
        $this->putLFloat($this->moveVecZ);
        $this->putLFloat($this->headYaw);
		$this->putUnsignedVarLong($this->inputFlags);
		$this->putUnsignedVarInt($this->inputMode);
		$this->putUnsignedVarInt($this->playMode);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_527){
		    $this->putUnsignedVarInt($this->interactionMode);
		}

        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_748){
            $this->putVector2($this->interactRotation);
        }else{
            if($this->playMode === PlayMode::VR){
                assert($this->vrGazeDirection !== null);
                $this->putVector3($this->vrGazeDirection);
            }
        }

		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_419){
            $this->putUnsignedVarLong($this->tick);
            $this->putVector3($this->delta);

            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_428){
                if($this->itemInteractionData !== null){
                    $this->itemInteractionData->write($this);
                }

                if($this->itemStackRequest !== null){
                    $this->itemStackRequest->write($this);
                }

                if($this->blockActions !== null){
                    $this->putVarInt(count($this->blockActions));
                    foreach($this->blockActions as $blockAction){
                        $this->putVarInt(MultiversionEnums::getPlayerActionId($this->getProtocol(), $blockAction->getActionType()));
                        $blockAction->write($this);
                    }
                }

                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_649){
                    if($this->vehicleInfo !== null){
                        $this->vehicleInfo->write($this);
                    }
                }

                if($this->getProtocol() >= ProtocolInfo::PROTOCOL_575){
                    $this->putLFloat($this->analogMoveVecX);
                    $this->putLFloat($this->analogMoveVecZ);
                    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_748){
                        $this->putVector3($this->cameraOrientation);
                        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_766){
                            $this->putVector2($this->rawMove);
                        }
                    }
                }
            }
        }
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerAuthInput($this);
	}
}
