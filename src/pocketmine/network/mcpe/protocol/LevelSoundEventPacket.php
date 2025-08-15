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

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\multiversion\block\BlockPalette;
use pocketmine\network\mcpe\multiversion\MultiversionEnums;
use pocketmine\network\mcpe\NetworkSession;
use function array_search;
use function chr;
use function ord;

class LevelSoundEventPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::LEVEL_SOUND_EVENT_PACKET;

    public const SOUND_ITEM_USE_ON = 0;
    public const SOUND_HIT = 1;
    public const SOUND_STEP = 2;
    public const SOUND_FLY = 3;
    public const SOUND_JUMP = 4;
    public const SOUND_BREAK = 5;
    public const SOUND_PLACE = 6;
    public const SOUND_HEAVY_STEP = 7;
    public const SOUND_GALLOP = 8;
    public const SOUND_FALL = 9;
    public const SOUND_AMBIENT = 10;
    public const SOUND_AMBIENT_BABY = 11;
    public const SOUND_AMBIENT_IN_WATER = 12;
    public const SOUND_BREATHE = 13;
    public const SOUND_DEATH = 14;
    public const SOUND_DEATH_IN_WATER = 15;
    public const SOUND_DEATH_TO_ZOMBIE = 16;
    public const SOUND_HURT = 17;
    public const SOUND_HURT_IN_WATER = 18;
    public const SOUND_MAD = 19;
    public const SOUND_BOOST = 20;
    public const SOUND_BOW = 21;
    public const SOUND_SQUISH_BIG = 22;
    public const SOUND_SQUISH_SMALL = 23;
    public const SOUND_FALL_BIG = 24;
    public const SOUND_FALL_SMALL = 25;
    public const SOUND_SPLASH = 26;
    public const SOUND_FIZZ = 27;
    public const SOUND_FLAP = 28;
    public const SOUND_SWIM = 29;
    public const SOUND_DRINK = 30;
    public const SOUND_EAT = 31;
    public const SOUND_TAKEOFF = 32;
    public const SOUND_SHAKE = 33;
    public const SOUND_PLOP = 34;
    public const SOUND_LAND = 35;
    public const SOUND_SADDLE = 36;
    public const SOUND_ARMOR = 37;
    public const SOUND_MOB_ARMOR_STAND_PLACE = 38;
    public const SOUND_ADD_CHEST = 39;
    public const SOUND_THROW = 40;
    public const SOUND_ATTACK = 41;
    public const SOUND_ATTACK_NODAMAGE = 42;
    public const SOUND_ATTACK_STRONG = 43;
    public const SOUND_WARN = 44;
    public const SOUND_SHEAR = 45;
    public const SOUND_MILK = 46;
    public const SOUND_THUNDER = 47;
    public const SOUND_EXPLODE = 48;
    public const SOUND_FIRE = 49;
    public const SOUND_IGNITE = 50;
    public const SOUND_FUSE = 51;
    public const SOUND_STARE = 52;
    public const SOUND_SPAWN = 53;
    public const SOUND_SHOOT = 54;
    public const SOUND_BREAK_BLOCK = 55;
    public const SOUND_LAUNCH = 56;
    public const SOUND_BLAST = 57;
    public const SOUND_LARGE_BLAST = 58;
    public const SOUND_TWINKLE = 59;
    public const SOUND_REMEDY = 60;
    public const SOUND_UNFECT = 61;
    public const SOUND_LEVELUP = 62;
    public const SOUND_BOW_HIT = 63;
    public const SOUND_BULLET_HIT = 64;
    public const SOUND_EXTINGUISH_FIRE = 65;
    public const SOUND_ITEM_FIZZ = 66;
    public const SOUND_CHEST_OPEN = 67;
    public const SOUND_CHEST_CLOSED = 68;
    public const SOUND_SHULKERBOX_OPEN = 69;
    public const SOUND_SHULKERBOX_CLOSED = 70;
    public const SOUND_ENDERCHEST_OPEN = 71;
    public const SOUND_ENDERCHEST_CLOSED = 72;
    public const SOUND_POWER_ON = 73;
    public const SOUND_POWER_OFF = 74;
    public const SOUND_ATTACH = 75;
    public const SOUND_DETACH = 76;
    public const SOUND_DENY = 77;
    public const SOUND_TRIPOD = 78;
    public const SOUND_POP = 79;
    public const SOUND_DROP_SLOT = 80;
    public const SOUND_NOTE = 81;
    public const SOUND_THORNS = 82;
    public const SOUND_PISTON_IN = 83;
    public const SOUND_PISTON_OUT = 84;
    public const SOUND_PORTAL = 85;
    public const SOUND_WATER = 86;
    public const SOUND_LAVA_POP = 87;
    public const SOUND_LAVA = 88;
    public const SOUND_BURP = 89;
    public const SOUND_BUCKET_FILL_WATER = 90;
    public const SOUND_BUCKET_FILL_LAVA = 91;
    public const SOUND_BUCKET_EMPTY_WATER = 92;
    public const SOUND_BUCKET_EMPTY_LAVA = 93;
    public const SOUND_ARMOR_EQUIP_CHAIN = 94;
    public const SOUND_ARMOR_EQUIP_DIAMOND = 95;
    public const SOUND_ARMOR_EQUIP_GENERIC = 96;
    public const SOUND_ARMOR_EQUIP_GOLD = 97;
    public const SOUND_ARMOR_EQUIP_IRON = 98;
    public const SOUND_ARMOR_EQUIP_LEATHER = 99;
    public const SOUND_ARMOR_EQUIP_ELYTRA = 100;
    public const SOUND_RECORD_13 = 101;
    public const SOUND_RECORD_CAT = 102;
    public const SOUND_RECORD_BLOCKS = 103;
    public const SOUND_RECORD_CHIRP = 104;
    public const SOUND_RECORD_FAR = 105;
    public const SOUND_RECORD_MALL = 106;
    public const SOUND_RECORD_MELLOHI = 107;
    public const SOUND_RECORD_STAL = 108;
    public const SOUND_RECORD_STRAD = 109;
    public const SOUND_RECORD_WARD = 110;
    public const SOUND_RECORD_11 = 111;
    public const SOUND_RECORD_WAIT = 112;
    public const SOUND_STOP_RECORD = 113;
    public const SOUND_FLOP = 114;
    public const SOUND_ELDERGUARDIAN_CURSE = 115;
    public const SOUND_MOB_WARNING = 116;
    public const SOUND_MOB_WARNING_BABY = 117;
    public const SOUND_TELEPORT = 118;
    public const SOUND_SHULKER_OPEN = 119;
    public const SOUND_SHULKER_CLOSE = 120;
    public const SOUND_HAGGLE = 121;
    public const SOUND_HAGGLE_YES = 122;
    public const SOUND_HAGGLE_NO = 123;
    public const SOUND_HAGGLE_IDLE = 124;
    public const SOUND_CHORUSGROW = 125;
    public const SOUND_CHORUSDEATH = 126;
    public const SOUND_GLASS = 127;
    public const SOUND_POTION_BREWED = 128;
    public const SOUND_CAST_SPELL = 129;
    public const SOUND_PREPARE_ATTACK = 130;
    public const SOUND_PREPARE_SUMMON = 131;
    public const SOUND_PREPARE_WOLOLO = 132;
    public const SOUND_FANG = 133;
    public const SOUND_CHARGE = 134;
    public const SOUND_CAMERA_TAKE_PICTURE = 135;
    public const SOUND_LEASHKNOT_PLACE = 136;
    public const SOUND_LEASHKNOT_BREAK = 137;
    public const SOUND_GROWL = 138;
    public const SOUND_WHINE = 139;
    public const SOUND_PANT = 140;
    public const SOUND_PURR = 141;
    public const SOUND_PURREOW = 142;
    public const SOUND_DEATH_MIN_VOLUME = 143;
    public const SOUND_DEATH_MID_VOLUME = 144;
    public const SOUND_IMITATE_BLAZE = 145;
    public const SOUND_IMITATE_CAVE_SPIDER = 146;
    public const SOUND_IMITATE_CREEPER = 147;
    public const SOUND_IMITATE_ELDER_GUARDIAN = 148;
    public const SOUND_IMITATE_ENDER_DRAGON = 149;
    public const SOUND_IMITATE_ENDERMAN = 150;
    public const SOUND_IMITATE_ENDERMITE = 151;
    public const SOUND_IMITATE_EVOCATION_ILLAGER = 152;
    public const SOUND_IMITATE_GHAST = 153;
    public const SOUND_IMITATE_HUSK = 154;
    public const SOUND_IMITATE_ILLUSION_ILLAGER = 155;
    public const SOUND_IMITATE_MAGMA_CUBE = 156;
    public const SOUND_IMITATE_POLAR_BEAR = 157;
    public const SOUND_IMITATE_SHULKER = 158;
    public const SOUND_IMITATE_SILVERFISH = 159;
    public const SOUND_IMITATE_SKELETON = 160;
    public const SOUND_IMITATE_SLIME = 161;
    public const SOUND_IMITATE_SPIDER = 162;
    public const SOUND_IMITATE_STRAY = 163;
    public const SOUND_IMITATE_VEX = 164;
    public const SOUND_IMITATE_VINDICATION_ILLAGER = 165;
    public const SOUND_IMITATE_WITCH = 166;
    public const SOUND_IMITATE_WITHER = 167;
    public const SOUND_IMITATE_WITHER_SKELETON = 168;
    public const SOUND_IMITATE_WOLF = 169;
    public const SOUND_IMITATE_ZOMBIE = 170;
    public const SOUND_IMITATE_ZOMBIE_PIGMAN = 171;
    public const SOUND_IMITATE_ZOMBIE_VILLAGER = 172;
    public const SOUND_BLOCK_END_PORTAL_FRAME_FILL = 173;
    public const SOUND_BLOCK_END_PORTAL_SPAWN = 174;
    public const SOUND_RANDOM_ANVIL_USE = 175;
    public const SOUND_BOTTLE_DRAGONBREATH = 176;
    public const SOUND_PORTAL_TRAVEL = 177;
    public const SOUND_ITEM_TRIDENT_HIT = 178;
    public const SOUND_ITEM_TRIDENT_RETURN = 179;
    public const SOUND_ITEM_TRIDENT_RIPTIDE_1 = 180;
    public const SOUND_ITEM_TRIDENT_RIPTIDE_2 = 181;
    public const SOUND_ITEM_TRIDENT_RIPTIDE_3 = 182;
    public const SOUND_ITEM_TRIDENT_THROW = 183;
    public const SOUND_ITEM_TRIDENT_THUNDER = 184;
    public const SOUND_ITEM_TRIDENT_HIT_GROUND = 185;
    public const SOUND_DEFAULT = 186;
    public const SOUND_BLOCK_FLETCHING_TABLE_USE = 187;
    public const SOUND_ELEMCONSTRUCT_OPEN = 188;
    public const SOUND_ICEBOMB_HIT = 189;
    public const SOUND_BALLOONPOP = 190;
    public const SOUND_LT_REACTION_ICEBOMB = 191;
    public const SOUND_LT_REACTION_BLEACH = 192;
    public const SOUND_LT_REACTION_EPASTE = 193;
    public const SOUND_LT_REACTION_EPASTE2 = 194;
    public const SOUND_LT_REACTION_GLOW_STICK = 195;
    public const SOUND_LT_REACTION_GLOW_STICK_2 = 196;
    public const SOUND_LT_REACTION_LUMINOL = 197;
    public const SOUND_LT_REACTION_SALT = 198;
    public const SOUND_LT_REACTION_FERTILIZER = 199;
    public const SOUND_LT_REACTION_FIREBALL = 200;
    public const SOUND_LT_REACTION_MGSALT = 201;
    public const SOUND_LT_REACTION_MISCFIRE = 202;
    public const SOUND_LT_REACTION_FIRE = 203;
    public const SOUND_LT_REACTION_MISCEXPLOSION = 204;
    public const SOUND_LT_REACTION_MISCMYSTICAL = 205;
    public const SOUND_LT_REACTION_MISCMYSTICAL2 = 206;
    public const SOUND_LT_REACTION_PRODUCT = 207;
    public const SOUND_SPARKLER_USE = 208;
    public const SOUND_GLOWSTICK_USE = 209;
    public const SOUND_SPARKLER_ACTIVE = 210;
    public const SOUND_CONVERT_TO_DROWNED = 211;
    public const SOUND_BUCKET_FILL_FISH = 212;
    public const SOUND_BUCKET_EMPTY_FISH = 213;
    public const SOUND_BUBBLE_UP = 214;
    public const SOUND_BUBBLE_DOWN = 215;
    public const SOUND_BUBBLE_POP = 216;
    public const SOUND_BUBBLE_UPINSIDE = 217;
    public const SOUND_BUBBLE_DOWNINSIDE = 218;
    public const SOUND_HURT_BABY = 219;
    public const SOUND_DEATH_BABY = 220;
    public const SOUND_STEP_BABY = 221;
    public const SOUND_SPAWN_BABY = 222;
    public const SOUND_BORN = 223;
    public const SOUND_BLOCK_TURTLE_EGG_BREAK = 224;
    public const SOUND_BLOCK_TURTLE_EGG_CRACK = 225;
    public const SOUND_BLOCK_TURTLE_EGG_HATCH = 226;
    public const SOUND_LAY_EGG = 227;
    public const SOUND_BLOCK_TURTLE_EGG_ATTACK = 228;
    public const SOUND_BEACON_ACTIVATE = 229;
    public const SOUND_BEACON_AMBIENT = 230;
    public const SOUND_BEACON_DEACTIVATE = 231;
    public const SOUND_BEACON_POWER = 232;
    public const SOUND_CONDUIT_ACTIVATE = 233;
    public const SOUND_CONDUIT_AMBIENT = 234;
    public const SOUND_CONDUIT_ATTACK = 235;
    public const SOUND_CONDUIT_DEACTIVATE = 236;
    public const SOUND_CONDUIT_SHORT = 237;
    public const SOUND_SWOOP = 238;
    public const SOUND_BLOCK_BAMBOO_SAPLING_PLACE = 239;
    public const SOUND_PRESNEEZE = 240;
    public const SOUND_SNEEZE = 241;
    public const SOUND_AMBIENT_TAME = 242;
    public const SOUND_SCARED = 243;
    public const SOUND_BLOCK_SCAFFOLDING_CLIMB = 244;
    public const SOUND_CROSSBOW_LOADING_START = 245;
    public const SOUND_CROSSBOW_LOADING_MIDDLE = 246;
    public const SOUND_CROSSBOW_LOADING_END = 247;
    public const SOUND_CROSSBOW_SHOOT = 248;
    public const SOUND_CROSSBOW_QUICK_CHARGE_START = 249;
    public const SOUND_CROSSBOW_QUICK_CHARGE_MIDDLE = 250;
    public const SOUND_CROSSBOW_QUICK_CHARGE_END = 251;
    public const SOUND_AMBIENT_AGGRESSIVE = 252;
    public const SOUND_AMBIENT_WORRIED = 253;
    public const SOUND_CANT_BREED = 254;
    public const SOUND_ITEM_SHIELD_BLOCK = 255;
    public const SOUND_ITEM_BOOK_PUT = 256;
    public const SOUND_BLOCK_GRINDSTONE_USE = 257;
    public const SOUND_BLOCK_BELL_HIT = 258;
    public const SOUND_BLOCK_CAMPFIRE_CRACKLE = 259;
    public const SOUND_ROAR = 260;
    public const SOUND_STUN = 261;
    public const SOUND_BLOCK_SWEET_BERRY_BUSH_HURT = 262;
    public const SOUND_BLOCK_SWEET_BERRY_BUSH_PICK = 263;
    public const SOUND_BLOCK_CARTOGRAPHY_TABLE_USE = 264;
    public const SOUND_BLOCK_STONECUTTER_USE = 265;
    public const SOUND_BLOCK_COMPOSTER_EMPTY = 266;
    public const SOUND_BLOCK_COMPOSTER_FILL = 267;
    public const SOUND_BLOCK_COMPOSTER_FILL_SUCCESS = 268;
    public const SOUND_BLOCK_COMPOSTER_READY = 269;
    public const SOUND_BLOCK_BARREL_OPEN = 270;
    public const SOUND_BLOCK_BARREL_CLOSE = 271;
    public const SOUND_RAID_HORN = 272;
    public const SOUND_BLOCK_LOOM_USE = 273;
    public const SOUND_AMBIENT_IN_RAID = 274;
    public const SOUND_UI_CARTOGRAPHY_TABLE_TAKE_RESULT = 275;
    public const SOUND_UI_STONECUTTER_TAKE_RESULT = 276;
    public const SOUND_UI_LOOM_TAKE_RESULT = 277;
    public const SOUND_BLOCK_SMOKER_SMOKE = 278;
    public const SOUND_BLOCK_BLASTFURNACE_FIRE_CRACKLE = 279;
    public const SOUND_BLOCK_SMITHING_TABLE_USE = 280;
    public const SOUND_SCREECH = 281;
    public const SOUND_SLEEP = 282;
    public const SOUND_BLOCK_FURNACE_LIT = 283;
    public const SOUND_CONVERT_MOOSHROOM = 284;
    public const SOUND_MILK_SUSPICIOUSLY = 285;
    public const SOUND_CELEBRATE = 286;
    public const SOUND_JUMP_PREVENT = 287;
    public const SOUND_AMBIENT_POLLINATE = 288;
    public const SOUND_BLOCK_BEEHIVE_DRIP = 289;
    public const SOUND_BLOCK_BEEHIVE_ENTER = 290;
    public const SOUND_BLOCK_BEEHIVE_EXIT = 291;
    public const SOUND_BLOCK_BEEHIVE_WORK = 292;
    public const SOUND_BLOCK_BEEHIVE_SHEAR = 293;
    public const SOUND_DRINK_HONEY = 294;
    public const SOUND_AMBIENT_CAVE = 295;
    public const SOUND_RETREAT = 296;
    public const SOUND_CONVERTED_TO_ZOMBIFIED = 297;
    public const SOUND_ADMIRE = 298;
    public const SOUND_STEP_LAVA = 299;
    public const SOUND_TEMPT = 300;
    public const SOUND_PANIC = 301;
    public const SOUND_ANGRY = 302;
    public const SOUND_AMBIENT_WARPED_FOREST_MOOD = 303;
    public const SOUND_AMBIENT_SOULSAND_VALLEY_MOOD = 304;
    public const SOUND_AMBIENT_NETHER_WASTES_MOOD = 305;
    public const SOUND_AMBIENT_BASALT_DELTAS_MOOD = 306;
    public const SOUND_AMBIENT_CRIMSON_FOREST_MOOD = 307;
    public const SOUND_RESPAWN_ANCHOR_CHARGE = 308;
    public const SOUND_RESPAWN_ANCHOR_DEPLETE = 309;
    public const SOUND_RESPAWN_ANCHOR_SET_SPAWN = 310;
    public const SOUND_RESPAWN_ANCHOR_AMBIENT = 311;
    public const SOUND_PARTICLE_SOUL_ESCAPE_QUIET = 312;
    public const SOUND_PARTICLE_SOUL_ESCAPE_LOUD = 313;
    public const SOUND_RECORD_PIGSTEP = 314;
    public const SOUND_LODESTONE_COMPASS_LINK_COMPASS_TO_LODESTONE = 315;
    public const SOUND_SMITHING_TABLE_USE = 316;
    public const SOUND_ARMOR_EQUIP_NETHERITE = 317;
    public const SOUND_AMBIENT_WARPED_FOREST_LOOP = 318;
    public const SOUND_AMBIENT_SOULSAND_VALLEY_LOOP = 319;
    public const SOUND_AMBIENT_NETHER_WASTES_LOOP = 320;
    public const SOUND_AMBIENT_BASALT_DELTAS_LOOP = 321;
    public const SOUND_AMBIENT_CRIMSON_FOREST_LOOP = 322;
    public const SOUND_AMBIENT_WARPED_FOREST_ADDITIONS = 323;
    public const SOUND_AMBIENT_SOULSAND_VALLEY_ADDITIONS = 324;
    public const SOUND_AMBIENT_NETHER_WASTES_ADDITIONS = 325;
    public const SOUND_AMBIENT_BASALT_DELTAS_ADDITIONS = 326;
    public const SOUND_AMBIENT_CRIMSON_FOREST_ADDITIONS = 327;
    public const SOUND_POWER_ON_SCULK_SENSOR = 328;
    public const SOUND_POWER_OFF_SCULK_SENSOR = 329;
    public const SOUND_BUCKET_FILL_POWDER_SNOW = 330;
    public const SOUND_BUCKET_EMPTY_POWDER_SNOW = 331;
    public const SOUND_CAULDRON_DRIP_WATER_POINTED_DRIPSTONE = 332;
    public const SOUND_CAULDRON_DRIP_LAVA_POINTED_DRIPSTONE = 333;
    public const SOUND_DRIP_WATER_POINTED_DRIPSTONE = 334;
    public const SOUND_DRIP_LAVA_POINTED_DRIPSTONE = 335;
    public const SOUND_PICK_BERRIES_CAVE_VINES = 336;
    public const SOUND_TILT_DOWN_BIG_DRIPLEAF = 337;
    public const SOUND_TILT_UP_BIG_DRIPLEAF = 338;
    public const SOUND_COPPER_WAX_ON = 339;
    public const SOUND_COPPER_WAX_OFF = 340;
    public const SOUND_SCRAPE = 341;
    public const SOUND_MOB_PLAYER_HURT_DROWN = 342;
    public const SOUND_MOB_PLAYER_HURT_ON_FIRE = 343;
    public const SOUND_MOB_PLAYER_HURT_FREEZE = 344;
    public const SOUND_ITEM_SPYGLASS_USE = 345;
    public const SOUND_ITEM_SPYGLASS_STOP_USING = 346;
    public const SOUND_CHIME_AMETHYST_BLOCK = 347;
    public const SOUND_AMBIENT_SCREAMER = 348;
    public const SOUND_HURT_SCREAMER = 349;
    public const SOUND_DEATH_SCREAMER = 350;
    public const SOUND_MILK_SCREAMER = 351;
    public const SOUND_JUMP_TO_BLOCK = 352;
    public const SOUND_PRE_RAM = 353;
    public const SOUND_PRE_RAM_SCREAMER = 354;
    public const SOUND_RAM_IMPACT = 355;
    public const SOUND_RAM_IMPACT_SCREAMER = 356;
    public const SOUND_SQUID_INK_SQUIRT = 357;
    public const SOUND_GLOW_SQUID_INK_SQUIRT = 358;
    public const SOUND_CONVERT_TO_STRAY = 359;
    public const SOUND_CAKE_ADD_CANDLE = 360;
    public const SOUND_EXTINGUISH_CANDLE = 361;
    public const SOUND_AMBIENT_CANDLE = 362;
    public const SOUND_BLOCK_CLICK = 363;
    public const SOUND_BLOCK_CLICK_FAIL = 364;
    public const SOUND_BLOCK_SCULK_CATALYST_BLOOM = 365;
    public const SOUND_BLOCK_SCULK_SHRIEKER_SHRIEK = 366;
    public const SOUND_NEARBY_CLOSE = 367;
    public const SOUND_NEARBY_CLOSER = 368;
    public const SOUND_NEARBY_CLOSEST = 369;
    public const SOUND_AGITATED = 370;
    public const SOUND_RECORD_OTHERSIDE = 371;
    public const SOUND_TONGUE = 372;
    public const SOUND_IRONGOLEM_CRACK = 373;
    public const SOUND_IRONGOLEM_REPAIR = 374;
    public const SOUND_LISTENING = 375;
    public const SOUND_HEARTBEAT = 376;
    public const SOUND_HORN_BREAK = 377;
    //TODO
    public const SOUND_BLOCK_SCULK_SPREAD = 379;
    public const SOUND_CHARGE_SCULK = 380;
    public const SOUND_BLOCK_SCULK_SENSOR_PLACE = 381;
    public const SOUND_BLOCK_SCULK_SHRIEKER_PLACE = 382;
    public const SOUND_HORN_CALL0 = 383;
    public const SOUND_HORN_CALL1 = 384;
    public const SOUND_HORN_CALL2 = 385;
    public const SOUND_HORN_CALL3 = 386;
    public const SOUND_HORN_CALL4 = 387;
    public const SOUND_HORN_CALL5 = 388;
    public const SOUND_HORN_CALL6 = 389;
    public const SOUND_HORN_CALL7 = 390;
    //TODO
    public const SOUND_IMITATE_WARDEN = 426;
    public const SOUND_LISTENING_ANGRY = 427;
    public const SOUND_ITEM_GIVEN = 428;
    public const SOUND_ITEM_TAKEN = 429;
    public const SOUND_DISAPPEARED = 430;
    public const SOUND_REAPPEARED = 431;
    public const SOUND_DRINK_MILK = 432;
    public const SOUND_BLOCK_FROG_SPAWN_HATCH = 433;
    public const SOUND_LAY_SPAWN = 434;
    public const SOUND_BLOCK_FROG_SPAWN_BREAK = 435;
    public const SOUND_SONIC_BOOM = 436;
    public const SOUND_SONIC_CHARGE = 437;
    public const SOUND_ITEM_THROWN = 438;
    public const SOUND_RECORD_5 = 439;
    public const SOUND_CONVERT_TO_FROG = 440;
    //TODO
    public const SOUND_BLOCK_ENCHANTING_TABLE_USE = 442;
    public const SOUND_STEP_SAND = 443;
    public const SOUND_DASH_READY = 444;
    public const SOUND_BUNDLE_DROP_CONTENTS = 445;
    public const SOUND_BUNDLE_INSERT = 446;
    public const SOUND_BUNDLE_REMOVE_ONE = 447;
    public const SOUND_PRESSURE_PLATE_CLICK_OFF = 448;
    public const SOUND_PRESSURE_PLATE_CLICK_ON = 449;
    public const SOUND_BUTTON_CLICK_OFF = 450;
    public const SOUND_BUTTON_CLICK_ON = 451;
    public const SOUND_DOOR_OPEN = 452;
    public const SOUND_DOOR_CLOSE = 453;
    public const SOUND_TRAPDOOR_OPEN = 454;
    public const SOUND_TRAPDOOR_CLOSE = 455;
    public const SOUND_FENCE_GATE_OPEN = 456;
    public const SOUND_FENCE_GATE_CLOSE = 457;
    public const SOUND_INSERT = 458;
    public const SOUND_PICKUP = 459;
    public const SOUND_INSERT_ENCHANTED = 460;
    public const SOUND_PICKUP_ENCHANTED = 461;
	public const SOUND_BRUSH = 462;
	public const SOUND_BRUSH_COMPLETED = 463;
	public const SOUND_SHATTER_POT = 464;
	public const SOUND_BREAK_POT = 465;
    public const SOUND_BLOCK_SNIFFER_EGG_CRACK = 466;
    public const SOUND_BLOCK_SNIFFER_EGG_HATCH = 467;
	public const SOUND_BLOCK_SIGN_WAXED_INTERACT_FAIL = 468;
	public const SOUND_RECORD_RELIC = 469;
	public const SOUND_NOTE_BASS = 470;
	public const SOUND_PUMPKIN_CARVE = 471;
	public const SOUND_MOB_HUSK_CONVERT_TO_ZOMBIE = 472;
	public const SOUND_MOB_PIG_DEATH = 473;
	public const SOUND_MOB_HOGLIN_CONVERTED_TO_ZOMBIFIED = 474;
	public const SOUND_AMBIENT_UNDERWATER_ENTER = 475;
	public const SOUND_AMBIENT_UNDERWATER_EXIT = 476;
	public const SOUND_BOTTLE_FILL = 477;
	public const SOUND_BOTTLE_EMPTY = 478;
	public const SOUND_CRAFTER_CRAFT = 479;
	public const SOUND_CRAFTER_FAIL = 480;
	public const SOUND_BLOCK_DECORATED_POT_INSERT = 481;
	public const SOUND_BLOCK_DECORATED_POT_INSERT_FAIL = 482;
	public const SOUND_CRAFTER_DISABLE_SLOT = 483;
    public const SOUND_TRIAL_SPAWNER_OPEN_SHUTTER = 484;
	public const SOUND_TRIAL_SPAWNER_EJECT_ITEM = 485;
	public const SOUND_TRIAL_SPAWNER_DETECT_PLAYER = 486;
	public const SOUND_TRIAL_SPAWNER_SPAWN_MOB = 487;
	public const SOUND_TRIAL_SPAWNER_CLOSE_SHUTTER = 488;
	public const SOUND_TRIAL_SPAWNER_AMBIENT = 489;
	public const SOUND_BLOCK_COPPER_BULB_TURN_ON = 490;
	public const SOUND_BLOCK_COPPER_BULB_TURN_OFF = 491;
    public const SOUND_AMBIENT_IN_AIR = 492;
	public const SOUND_BREEZE_WIND_CHARGE_BURST = 493;
	public const SOUND_IMITATE_BREEZE = 494;
	public const SOUND_MOB_ARMADILLO_BRUSH = 495;
	public const SOUND_MOB_ARMADILLO_SCUTE_DROP = 496;
	public const SOUND_ARMOR_EQUIP_WOLF = 497;
	public const SOUND_ARMOR_UNEQUIP_WOLF = 498;
	public const SOUND_REFLECT = 499;
	public const SOUND_VAULT_OPEN_SHUTTER = 500;
	public const SOUND_VAULT_CLOSE_SHUTTER = 501;
	public const SOUND_VAULT_EJECT_ITEM = 502;
	public const SOUND_VAULT_INSERT_ITEM = 503;
	public const SOUND_VAULT_INSERT_ITEM_FAIL = 504;
	public const SOUND_VAULT_AMBIENT = 505;
	public const SOUND_VAULT_ACTIVATE = 506;
	public const SOUND_VAULT_DEACTIVATE = 507;
	public const SOUND_HURT_REDUCED = 508;
	public const SOUND_WIND_CHARGE_BURST = 509;
    public const SOUND_IMITATE_BOGGED = 510;
	public const SOUND_ARMOR_CRACK_WOLF = 511;
	public const SOUND_ARMOR_BREAK_WOLF = 512;
	public const SOUND_ARMOR_REPAIR_WOLF = 513;
	public const SOUND_MACE_SMASH_AIR = 514;
	public const SOUND_MACE_SMASH_GROUND = 515;
    public const SOUND_TRIAL_SPAWNER_CHARGE_ACTIVATE = 516;
	public const SOUND_TRIAL_SPAWNER_AMBIENT_OMINOUS = 517;
	public const SOUND_OMINOUS_ITEM_SPAWNER_SPAWN_ITEM = 518;
	public const SOUND_OMINOUS_BOTTLE_END_USE = 519;
	public const SOUND_MACE_HEAVY_SMASH_GROUND = 520;
    public const SOUND_OMINOUS_ITEM_SPAWNER_SPAWN_ITEM_BEGIN = 521;

	public const SOUND_APPLY_EFFECT_BAD_OMEN = 523;
	public const SOUND_APPLY_EFFECT_RAID_OMEN = 524;
	public const SOUND_APPLY_EFFECT_TRIAL_OMEN = 525;
	public const SOUND_OMINOUS_ITEM_SPAWNER_ABOUT_TO_SPAWN_ITEM = 526;
	public const SOUND_RECORD_CREATOR = 527;
	public const SOUND_RECORD_CREATOR_MUSIC_BOX = 528;
	public const SOUND_RECORD_PRECIPICE = 529;
    public const SOUND_VAULT_REJECT_REWARDED_PLAYER = 530;
    public const SOUND_IMITATE_DROWNED = 531;
	public const SOUND_IMITATE_CREAKING = 532;
	public const SOUND_BUNDLE_INSERT_FAIL = 533;
	public const SOUND_SPONGE_ABSORB = 534;

	public const SOUND_BLOCK_CREAKING_HEART_TRAIL = 536;
	public const SOUND_CREAKING_HEART_SPAWN = 537;
	public const SOUND_ACTIVATE = 538;
	public const SOUND_DEACTIVATE = 539;
	public const SOUND_FREEZE = 540;
	public const SOUND_UNFREEZE = 541;
	public const SOUND_OPEN = 542;
	public const SOUND_OPEN_LONG = 543;
	public const SOUND_CLOSE = 544;
	public const SOUND_CLOSE_LONG = 545;
    public const SOUND_UNDEFINED = 546;

    //The following aliases are kept for backwards compatibility only
    public const SOUND_SCULK_SENSOR_POWER_ON = 328;
    public const SOUND_SCULK_SENSOR_POWER_OFF = 329;
    public const SOUND_POINTED_DRIPSTONE_CAULDRON_DRIP_WATER = 332;
    public const SOUND_POINTED_DRIPSTONE_CAULDRON_DRIP_LAVA = 333;
    public const SOUND_POINTED_DRIPSTONE_DRIP_WATER = 334;
    public const SOUND_POINTED_DRIPSTONE_DRIP_LAVA = 335;
    public const SOUND_CAVE_VINES_PICK_BERRIES = 336;
    public const SOUND_BIG_DRIPLEAF_TILT_DOWN = 337;
    public const SOUND_BIG_DRIPLEAF_TILT_UP = 338;
    public const SOUND_PLAYER_HURT_DROWN = 342;
    public const SOUND_PLAYER_HURT_ON_FIRE = 343;
    public const SOUND_PLAYER_HURT_FREEZE = 344;
    public const SOUND_USE_SPYGLASS = 345;
    public const SOUND_STOP_USING_SPYGLASS = 346;
    public const SOUND_AMETHYST_BLOCK_CHIME = 347;
    public const SOUND_SCULK_CATALYST_BLOOM = 365;
    public const SOUND_SCULK_SHRIEKER_SHRIEK = 366;
    public const SOUND_WARDEN_NEARBY_CLOSE = 367;
    public const SOUND_WARDEN_NEARBY_CLOSER = 368;
    public const SOUND_WARDEN_NEARBY_CLOSEST = 369;
    public const SOUND_WARDEN_SLIGHTLY_ANGRY = 370;

	/** @var int */
	public $sound;
	/** @var Vector3 */
	public $position;
	/** @var int */
	public $extraData = -1;
	/** @var string */
	public $entityType = ":"; //???
	/** @var bool */
	public $isBabyMob = false; //...
	/** @var bool */
	public $disableRelativeVolume = false;
	/** @var int */
	public $actorUniqueId = -1;

	protected function decodePayload(){
	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_332){
	    	$this->sound = $this->getUnsignedVarInt();
	    }else{
	        $this->sound = $this->getByte();
	    }
		$this->sound = MultiversionEnums::getLevelSoundEventName($this->getProtocol(), $this->sound);
		$this->position = $this->getVector3();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_220){
	    	$runtimeId = $this->getVarInt();
	    	if($runtimeId < 0){
	    	    $this->extraData = -1;
	    	}else{
	        	$palette = BlockPalette::getPalette($this->getProtocol());
	        	$blockData = $palette::fromStaticRuntimeId($runtimeId);
		        $this->extraData = $blockData[0];
	    	}
		}else{
		    $this->extraData = $this->getVarInt();
		}
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_310){
	    	$this->entityType = $this->getString();
		}else{
            $entityType = $this->getVarInt();
            if($this->sound === self::SOUND_THROW && $entityType === 319){
                $this->entityType = "minecraft:player";
            }else{
		        $this->entityType = (AddActorPacket::LEGACY_ID_MAP_BC[$entityType] ?? ":");
            }
		}
		$this->isBabyMob = $this->getBool();
		$this->disableRelativeVolume = $this->getBool();
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_786){
		    $this->actorUniqueId = $this->getLLong(); //WHY IS THIS NON-STANDARD?
		}
	}

	protected function encodePayload(){
	    $sound = MultiversionEnums::getLevelSoundEventId($this->getProtocol(), $this->sound);
	    if($this->getProtocol() >= ProtocolInfo::PROTOCOL_332){
	    	$this->putUnsignedVarInt($sound);
	    }else{
            $this->putByte($sound);
	    }
		$this->putVector3($this->position);
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_220){
            if ($this->sound === self::SOUND_HIT || $this->sound === self::SOUND_PLACE) {
                $palette = BlockPalette::getPalette($this->getProtocol());
                $runtimeId = $palette::toStaticRuntimeId($this->extraData);
                $this->putVarInt($runtimeId);
            } else {
                $this->putVarInt($this->extraData);
            }
		}else{
            if($this->sound === self::SOUND_NOTE){
                $this->putVarInt($this->extraData >> 8);
            } else {
                $this->putVarInt($this->extraData);
            }
		}
		if($this->getProtocol() >= ProtocolInfo::PROTOCOL_310){
	    	$this->putString($this->entityType);
		}else {
            if($this->sound === self::SOUND_NOTE){
                $entityType = $this->extraData & 0xf;
            }elseif ($this->sound === self::SOUND_THROW && $this->entityType === "minecraft:player") {
                $entityType = 319;
            } else {
                $entityType = array_search($this->entityType, AddActorPacket::LEGACY_ID_MAP_BC, true);
            }
            $this->putVarInt($entityType === false ? 1 : $entityType);
        }
        $this->putBool($this->isBabyMob);
        $this->putBool($this->disableRelativeVolume);
        if($this->getProtocol() >= ProtocolInfo::PROTOCOL_786){
            $this->putLLong($this->actorUniqueId);
        }
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleLevelSoundEvent($this);
	}
}
