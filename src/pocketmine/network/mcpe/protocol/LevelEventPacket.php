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

use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\multiversion\block\BlockPalette;
use pocketmine\network\mcpe\multiversion\MultiversionEnums;
use pocketmine\network\mcpe\NetworkSession;

class LevelEventPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::LEVEL_EVENT_PACKET;

	public const EVENT_SOUND_CLICK = 1000;
	public const EVENT_SOUND_CLICK_FAIL = 1001;
	public const EVENT_SOUND_SHOOT = 1002;
	public const EVENT_SOUND_DOOR = 1003;
	public const EVENT_SOUND_FIZZ = 1004;
	public const EVENT_SOUND_IGNITE = 1005;
    public const EVENT_SOUND_PLAY_RECORDING = 1006;
	public const EVENT_SOUND_GHAST = 1007;
	public const EVENT_SOUND_GHAST_SHOOT = 1008;
	public const EVENT_SOUND_BLAZE_SHOOT = 1009;
	public const EVENT_SOUND_DOOR_BUMP = 1010;

	public const EVENT_SOUND_DOOR_CRASH = 1012;

	public const EVENT_SOUND_ZOMBIE_INFECTED = 1016;
	public const EVENT_SOUND_ZOMBIE_CONVERT = 1017;
	public const EVENT_SOUND_ENDERMAN_TELEPORT = 1018;

	public const EVENT_SOUND_ANVIL_BREAK = 1020;
	public const EVENT_SOUND_ANVIL_USE = 1021;
	public const EVENT_SOUND_ANVIL_FALL = 1022;

	public const EVENT_SOUND_POP = 1030;

	public const EVENT_SOUND_PORTAL = 1032;

	public const EVENT_SOUND_ITEMFRAME_ADD_ITEM = 1040;
	public const EVENT_SOUND_ITEMFRAME_REMOVE = 1041;
	public const EVENT_SOUND_ITEMFRAME_PLACE = 1042;
	public const EVENT_SOUND_ITEMFRAME_REMOVE_ITEM = 1043;
	public const EVENT_SOUND_ITEMFRAME_ROTATE_ITEM = 1044;

	public const EVENT_SOUND_CAMERA = 1050;
	public const EVENT_SOUND_ORB = 1051;
	public const EVENT_SOUND_TOTEM = 1052;

	public const EVENT_SOUND_ARMOR_STAND_BREAK = 1060;
	public const EVENT_SOUND_ARMOR_STAND_HIT = 1061;
	public const EVENT_SOUND_ARMOR_STAND_FALL = 1062;
	public const EVENT_SOUND_ARMOR_STAND_PLACE = 1063;
    public const EVENT_SOUND_POINTED_DRIPSTONE_FALL = 1064;
	public const EVENT_SOUND_DYE_USED = 1065;
	public const EVENT_SOUND_INK_SAC_USED = 1066;

	public const EVENT_PARTICLE_SHOOT = 2000;
	public const EVENT_PARTICLE_DESTROY = 2001; //sound + particles
	public const EVENT_PARTICLE_SPLASH = 2002;
	public const EVENT_PARTICLE_EYE_DESPAWN = 2003;
	public const EVENT_PARTICLE_SPAWN = 2004;
    public const EVENT_BONE_MEAL_USE = 2005; //sound + green particles
	public const EVENT_GUARDIAN_CURSE = 2006;
    public const EVENT_PARTICLE_DEATH_SMOKE = 2007;
	public const EVENT_PARTICLE_BLOCK_FORCE_FIELD = 2008;
	public const EVENT_PARTICLE_PROJECTILE_HIT = 2009;
	public const EVENT_PARTICLE_DRAGON_EGG_TELEPORT = 2010;
	public const EVENT_PARTICLE_CROP_EATEN = 2011;
	public const EVENT_PARTICLE_CRITICAL_HIT = 2012;
	public const EVENT_PARTICLE_ENDERMAN_TELEPORT = 2013;
	public const EVENT_PARTICLE_PUNCH_BLOCK = 2014;
	public const EVENT_PARTICLE_BUBBLE = 2015;
	public const EVENT_PARTICLE_EVAPORATE = 2016;
	public const EVENT_PARTICLE_ARMOR_STAND_DESTROY = 2017;
	public const EVENT_PARTICLE_EGG_PUNCH = 2018;
	public const EVENT_PARTICLE_EGG_BREAK = 2019;
	public const EVENT_PARTICLE_ICE_EVAPORATE = 2020;
	public const EVENT_PARTICLE_DESTROY_NO_SOUND = 2021;
	public const EVENT_PARTICLE_KNOCKBACK_ROAR = 2022; //spews out tons of white particles
	public const EVENT_PARTICLE_TELEPORT_TRAIL = 2023;
	public const EVENT_PARTICLE_POINT_CLOUD = 2024;
	public const EVENT_PARTICLE_EXPLODE = 2025; //data >= 2 = huge explode seed, otherwise huge explode
	public const EVENT_PARTICLE_BLOCK_EXPLODE = 2026;
	public const EVENT_PARTICLE_VIBRATION_SIGNAL = 2027;
	public const EVENT_PARTICLE_DRIPSTONE_DRIP = 2028;
	public const EVENT_PARTICLE_FIZZ = 2029;
	public const EVENT_COPPER_WAX_ON = 2030; //sound + particles
	public const EVENT_COPPER_WAX_OFF = 2031; //sound + particles
	public const EVENT_COPPER_SCRAPE = 2032; //sound + particles
	public const EVENT_PARTICLE_ELECTRIC_SPARK = 2033; //lightning rod
	public const EVENT_PARTICLE_TURTLE_EGG_GROW = 2034;
	public const EVENT_PARTICLE_SCULK_SHRIEK = 2035;
	public const EVENT_PARTICLE_SCULK_CATALYST_BLOOM = 2036;

	public const EVENT_PARTICLE_DUST_PLUME = 2040;

	public const EVENT_START_RAIN = 3001;
	public const EVENT_START_THUNDER = 3002;
	public const EVENT_STOP_RAIN = 3003;
	public const EVENT_STOP_THUNDER = 3004;
	public const EVENT_PAUSE_GAME = 3005; //data: 1 to pause, 0 to resume
	public const EVENT_PAUSE_GAME_NO_SCREEN = 3006; //data: 1 to pause, 0 to resume - same effect as normal pause but without screen
	public const EVENT_SET_GAME_SPEED = 3007; //x coordinate of pos = scale factor (default 1.0)

	public const EVENT_REDSTONE_TRIGGER = 3500;
	public const EVENT_CAULDRON_EXPLODE = 3501;
	public const EVENT_CAULDRON_DYE_ARMOR = 3502;
	public const EVENT_CAULDRON_CLEAN_ARMOR = 3503;
	public const EVENT_CAULDRON_FILL_POTION = 3504;
	public const EVENT_CAULDRON_TAKE_POTION = 3505;
	public const EVENT_CAULDRON_FILL_WATER = 3506;
	public const EVENT_CAULDRON_TAKE_WATER = 3507;
	public const EVENT_CAULDRON_ADD_DYE = 3508;
	public const EVENT_CAULDRON_CLEAN_BANNER = 3509; //particle + sound
	public const EVENT_PARTICLE_CAULDRON_FLUSH = 3510;
	public const EVENT_PARTICLE_AGENT_SPAWN = 3511;
	public const EVENT_SOUND_CAULDRON_FILL_LAVA = 3512;
	public const EVENT_SOUND_CAULDRON_TAKE_LAVA = 3513;
	public const EVENT_SOUND_CAULDRON_FILL_POWDER_SNOW = 3514;
	public const EVENT_SOUND_CAULDRON_TAKE_POWDER_SNOW = 3515;

	public const EVENT_BLOCK_START_BREAK = 3600;
	public const EVENT_BLOCK_STOP_BREAK = 3601;
	public const EVENT_BLOCK_BREAK_SPEED = 3602;
	public const EVENT_PARTICLE_PUNCH_BLOCK_DOWN = 3603;
	public const EVENT_PARTICLE_PUNCH_BLOCK_UP = 3604;
	public const EVENT_PARTICLE_PUNCH_BLOCK_NORTH = 3605;
	public const EVENT_PARTICLE_PUNCH_BLOCK_SOUTH = 3606;
	public const EVENT_PARTICLE_PUNCH_BLOCK_WEST = 3607;
	public const EVENT_PARTICLE_PUNCH_BLOCK_EAST = 3608;
	public const EVENT_PARTICLE_SHOOT_WHITE_SMOKE = 3609;
	public const EVENT_PARTICLE_BREEZE_WIND_EXPLOSION = 3610;
	public const EVENT_PARTICLE_TRIAL_SPAWNER_DETECTION = 3611;
	public const EVENT_PARTICLE_TRIAL_SPAWNER_SPAWNING = 3612;
	public const EVENT_PARTICLE_TRIAL_SPAWNER_EJECTING = 3613;

	public const EVENT_SET_DATA = 4000;

	public const EVENT_PLAYERS_SLEEPING = 9800;
	public const EVENT_NUMBER_OF_SLEEPING_PLAYERS = 9801;

	public const EVENT_JUMP_PREVENTED = 9810;
	public const EVENT_ANIMATION_VAULT_ACTIVATE = 9811;
	public const EVENT_ANIMATION_VAULT_DEACTIVATE = 9812;
	public const EVENT_ANIMATION_VAULT_EJECT_ITEM = 9813;

	public const EVENT_ADD_PARTICLE_MASK = 0x4000;

	/** @var int */
	public $evid;
	/** @var Vector3|null */
	public $position;
	/** @var int */
	public $data;

	protected function decodePayload(){
		$this->evid = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getVarInt() : $this->getShort();
		$this->position = $this->getVector3();
		$this->data = $this->getProtocol() >= ProtocolInfo::PROTOCOL_90 ? $this->getVarInt() : $this->getInt();
	}

	protected function encodePayload(){
        if($this->evid >= self::EVENT_ADD_PARTICLE_MASK){
            $evid = self::EVENT_ADD_PARTICLE_MASK | MultiversionEnums::getParticleId($this->getProtocol(), $this->evid % self::EVENT_ADD_PARTICLE_MASK);
        }else{
            $evid = $this->evid;
        }
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
	    	$this->putVarInt($evid);
        }else{
            $this->putShort($evid);
        }
		$this->putVector3Nullable($this->position);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_220){
	    	switch($this->evid){
		    	case self::EVENT_PARTICLE_DESTROY:
			    	$id = $this->data >> Block::INTERNAL_METADATA_BITS;
			    	$meta = $this->data & Block::INTERNAL_METADATA_MASK;
			    	$palette = BlockPalette::getPalette($this->getProtocol());
			    	$runtimeId = $palette::toStaticRuntimeId($id, $meta);
			    	$this->putVarInt($runtimeId);
			    	break;
		    	case self::EVENT_PARTICLE_PUNCH_BLOCK:
			    	$id = $this->data & 0xff;
			    	$meta = ($this->data >> 8) & 0x0f;
			    	$face = $this->data >> 16;
			    	$palette = BlockPalette::getPalette($this->getProtocol());
			    	$runtimeId = $palette::toStaticRuntimeId($id, $meta) | ($face << 24);
			    	$this->putVarInt($runtimeId);
			    	break;
		    	default:
			    	$this->putVarInt($this->data);
			    	break;
	    	}
    	}else{
            if($this->evid === self::EVENT_PARTICLE_DESTROY){
                $id = $this->data >> Block::INTERNAL_METADATA_BITS;
                $meta = $this->data & Block::INTERNAL_METADATA_MASK;
                $this->data = $id + ($meta << 8);
            }
            if($this->getProtocol() >= ProtocolInfo::PROTOCOL_90){
    	        $this->putVarInt($this->data);
            }else{
                $this->putInt($this->data);
            }
    	}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleLevelEvent($this);
	}
}
