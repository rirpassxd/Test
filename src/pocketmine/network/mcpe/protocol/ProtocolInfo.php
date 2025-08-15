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

use pocketmine\network\mcpe\protocol\PacketsIds\PacketMagicNumbers;

/**
 * Version numbers and packet IDs for the current Minecraft PE protocol
 */
final class ProtocolInfo implements PacketMagicNumbers{

	/**
	 * NOTE TO DEVELOPERS
	 * Do not waste your time or ours submitting pull requests changing game and/or protocol version numbers.
	 * Pull requests changing game and/or protocol version numbers will be closed.
	 *
	 * This file is generated automatically, do not edit it manually.
	 */

	/**
	 * Actual Minecraft: PE protocol version
	 */
	public const CURRENT_PROTOCOL = ProtocolInfo::PROTOCOL_27;

	public const ACCEPTED_PROTOCOLS = [
  ProtocolInfo::PROTOCOL_27,
  ProtocolInfo::PROTOCOL_37,
  ProtocolInfo::PROTOCOL_38,
  ProtocolInfo::PROTOCOL_39,
  ProtocolInfo::PROTOCOL_41,
  ProtocolInfo::PROTOCOL_42,
  ProtocolInfo::PROTOCOL_43,
  ProtocolInfo::PROTOCOL_44,
  ProtocolInfo::PROTOCOL_45,
  ProtocolInfo::PROTOCOL_60,
  ProtocolInfo::PROTOCOL_70,
	 ProtocolInfo::PROTOCOL_81,
	 ProtocolInfo::PROTOCOL_82,
	 ProtocolInfo::PROTOCOL_83,
	 ProtocolInfo::PROTOCOL_84,
	 ProtocolInfo::PROTOCOL_90,
	 ProtocolInfo::PROTOCOL_91,
	 ProtocolInfo::PROTOCOL_92,
		ProtocolInfo::PROTOCOL_100,
		ProtocolInfo::PROTOCOL_101,
		ProtocolInfo::PROTOCOL_102,
		ProtocolInfo::PROTOCOL_105,
		ProtocolInfo::PROTOCOL_106,
		ProtocolInfo::PROTOCOL_107,
		ProtocolInfo::PROTOCOL_110,
		ProtocolInfo::PROTOCOL_111,
		ProtocolInfo::PROTOCOL_112,
		ProtocolInfo::PROTOCOL_113,
		ProtocolInfo::PROTOCOL_130,
		ProtocolInfo::PROTOCOL_131,
		ProtocolInfo::PROTOCOL_132,
		ProtocolInfo::PROTOCOL_133,
		ProtocolInfo::PROTOCOL_134,
		ProtocolInfo::PROTOCOL_135,
		ProtocolInfo::PROTOCOL_136,
		ProtocolInfo::PROTOCOL_137,
		ProtocolInfo::PROTOCOL_140,
		ProtocolInfo::PROTOCOL_141,
		ProtocolInfo::PROTOCOL_150,
		ProtocolInfo::PROTOCOL_160,
		ProtocolInfo::PROTOCOL_200,
		ProtocolInfo::PROTOCOL_201,
		ProtocolInfo::PROTOCOL_220,
		ProtocolInfo::PROTOCOL_221,
		ProtocolInfo::PROTOCOL_222,
		ProtocolInfo::PROTOCOL_223,
		ProtocolInfo::PROTOCOL_224,
		ProtocolInfo::PROTOCOL_240,
		ProtocolInfo::PROTOCOL_250,
		ProtocolInfo::PROTOCOL_260,
		ProtocolInfo::PROTOCOL_261,
		ProtocolInfo::PROTOCOL_270,
		ProtocolInfo::PROTOCOL_271,
		ProtocolInfo::PROTOCOL_273,
		ProtocolInfo::PROTOCOL_274,
		ProtocolInfo::PROTOCOL_280,
		ProtocolInfo::PROTOCOL_281,
		ProtocolInfo::PROTOCOL_282,
		ProtocolInfo::PROTOCOL_290,
		ProtocolInfo::PROTOCOL_291,
		ProtocolInfo::PROTOCOL_310,
		ProtocolInfo::PROTOCOL_311,
		ProtocolInfo::PROTOCOL_312,
		ProtocolInfo::PROTOCOL_313,
		ProtocolInfo::PROTOCOL_330,
		ProtocolInfo::PROTOCOL_331,
		ProtocolInfo::PROTOCOL_332,
		ProtocolInfo::PROTOCOL_340,
		ProtocolInfo::PROTOCOL_342,
		ProtocolInfo::PROTOCOL_350,
		ProtocolInfo::PROTOCOL_351,
		ProtocolInfo::PROTOCOL_352,
		ProtocolInfo::PROTOCOL_353,
		ProtocolInfo::PROTOCOL_354,
		ProtocolInfo::PROTOCOL_360,
		ProtocolInfo::PROTOCOL_361,
		ProtocolInfo::PROTOCOL_370,
		ProtocolInfo::PROTOCOL_371,
		ProtocolInfo::PROTOCOL_385,
		ProtocolInfo::PROTOCOL_386,
		ProtocolInfo::PROTOCOL_387,
		ProtocolInfo::PROTOCOL_388,
		ProtocolInfo::PROTOCOL_389,
		ProtocolInfo::PROTOCOL_390,
		ProtocolInfo::PROTOCOL_391,
		ProtocolInfo::PROTOCOL_392,
		ProtocolInfo::PROTOCOL_393,
		ProtocolInfo::PROTOCOL_394,
		ProtocolInfo::PROTOCOL_395,
		ProtocolInfo::PROTOCOL_396,
		ProtocolInfo::PROTOCOL_400,
		ProtocolInfo::PROTOCOL_401,
		ProtocolInfo::PROTOCOL_402,
		ProtocolInfo::PROTOCOL_403,
		ProtocolInfo::PROTOCOL_404,
		ProtocolInfo::PROTOCOL_405,
		ProtocolInfo::PROTOCOL_406,
		ProtocolInfo::PROTOCOL_407,
		ProtocolInfo::PROTOCOL_408,
		ProtocolInfo::PROTOCOL_409,
		ProtocolInfo::PROTOCOL_410,
		ProtocolInfo::PROTOCOL_411,
		ProtocolInfo::PROTOCOL_412,
		ProtocolInfo::PROTOCOL_413,
		ProtocolInfo::PROTOCOL_414,
		ProtocolInfo::PROTOCOL_415,
		ProtocolInfo::PROTOCOL_416,
		ProtocolInfo::PROTOCOL_417,
		ProtocolInfo::PROTOCOL_418,
		ProtocolInfo::PROTOCOL_419,
		ProtocolInfo::PROTOCOL_420,
		ProtocolInfo::PROTOCOL_421,
		ProtocolInfo::PROTOCOL_422,
		ProtocolInfo::PROTOCOL_423,
		ProtocolInfo::PROTOCOL_424,
		ProtocolInfo::PROTOCOL_425,
		ProtocolInfo::PROTOCOL_427,
		ProtocolInfo::PROTOCOL_428,
		ProtocolInfo::PROTOCOL_429,
		ProtocolInfo::PROTOCOL_430,
		ProtocolInfo::PROTOCOL_431,
		ProtocolInfo::PROTOCOL_440,
		ProtocolInfo::PROTOCOL_448,
		ProtocolInfo::PROTOCOL_465,
		ProtocolInfo::PROTOCOL_471,
		ProtocolInfo::PROTOCOL_475,
		ProtocolInfo::PROTOCOL_486,
		ProtocolInfo::PROTOCOL_503,
		ProtocolInfo::PROTOCOL_526,
		ProtocolInfo::PROTOCOL_527,
		ProtocolInfo::PROTOCOL_534,
		ProtocolInfo::PROTOCOL_544,
		ProtocolInfo::PROTOCOL_545,
		ProtocolInfo::PROTOCOL_553,
		ProtocolInfo::PROTOCOL_554,
		ProtocolInfo::PROTOCOL_557,
		ProtocolInfo::PROTOCOL_560,
		ProtocolInfo::PROTOCOL_567,
		ProtocolInfo::PROTOCOL_568,
		ProtocolInfo::PROTOCOL_575,
		ProtocolInfo::PROTOCOL_582,
		ProtocolInfo::PROTOCOL_589,
		ProtocolInfo::PROTOCOL_594,
		ProtocolInfo::PROTOCOL_618,
		ProtocolInfo::PROTOCOL_622,
		ProtocolInfo::PROTOCOL_630,
		ProtocolInfo::PROTOCOL_649,
		ProtocolInfo::PROTOCOL_662,
		ProtocolInfo::PROTOCOL_671,
		ProtocolInfo::PROTOCOL_685,
		ProtocolInfo::PROTOCOL_686,
		ProtocolInfo::PROTOCOL_712,
		ProtocolInfo::PROTOCOL_729,
		ProtocolInfo::PROTOCOL_748,
		ProtocolInfo::PROTOCOL_766,
		ProtocolInfo::PROTOCOL_776,
		ProtocolInfo::PROTOCOL_786,
		ProtocolInfo::PROTOCOL_800
	];

    public const PROTOCOL_27 = 27; // 0.11.0.14, 0.11.0.13
    public const PROTOCOL_37 = 37; // 0.13.0.2, 0.13.0.1
    public const PROTOCOL_38 = 38; // 0.13.0.4, 0.13.0.3
    public const PROTOCOL_39 = 39; // 0.13.0.2
    public const PROTOCOL_41 = 41; // 0.14.0
    public const PROTOCOL_42 = 42; // 0.14.0.3
    public const PROTOCOL_43 = 43; // 0.14.0.5, 0.14.0.4
    public const PROTOCOL_44 = 44; // 0.14.0.6
    public const PROTOCOL_45 = 45; // 0.14.0.7
    public const PROTOCOL_60 = 60; // 0.14.2
    public const PROTOCOL_70 = 70; // 0.14.3
    public const PROTOCOL_81 = 81; // 0.15.0, 0.15.1, 0.15.2, 0.15.3
    public const PROTOCOL_82 = 82; // 0.15.4, 0.15.6, 0.15.7, 0.15.8
    public const PROTOCOL_83 = 83; // 0.15.9
    public const PROTOCOL_84 = 84; // 0.15.10
    public const PROTOCOL_90 = 90; // 0.15.90, 0.15.90.1, 0.16.0
    public const PROTOCOL_91 = 91; // 0.16.0, 0.16.1, 0.16.2, 0.17.0.1, 0.17.0.2
    public const PROTOCOL_92 = 92; // 1.0.0.0, 1.0.0.1
    public const PROTOCOL_100 = 100; // 1.0.0.2, 1.0.0.7, 1.0.0, 1.0.1, 1.0.2
	public const PROTOCOL_101 = 101; // 1.0.3.0, 1.0.3, 1.0.4.0
	public const PROTOCOL_102 = 102; // 1.0.4.1, 1.0.4
	public const PROTOCOL_105 = 105; // 1.0.5.0, 1.0.5.3, 1.0.5.11, 1.0.5, 1.0.6.0
	public const PROTOCOL_106 = 106; // 1.0.6
	public const PROTOCOL_107 = 107; // 1.0.7, 1.0.8, 1.0.9

	public const PROTOCOL_110 = 110; // 1.1.0.0, 1.1.0.1, 1.1.0.2, 1.1.0.3, 1.1.0.4, 1.1.0.5
	public const PROTOCOL_111 = 111; // 1.1.0.8
	public const PROTOCOL_112 = 112; // 1.1.0.9
	public const PROTOCOL_113 = 113; // 1.1.0, 1.1.1.0, 1.1.1.1, 1.1.1, 1.1.2, 1.1.3.0, 1.1.3.1, 1.1.3, 1.1.4, 1.1.5, 1.1.7

	public const PROTOCOL_130 = 130; // 1.2.0.2
	public const PROTOCOL_131 = 131; // 1.2.0.7
	public const PROTOCOL_132 = 132; // 1.2.0.15
	public const PROTOCOL_133 = 133; // 1.2.0.18
	public const PROTOCOL_134 = 134; // 1.2.0.20, 1.2.0.22
	public const PROTOCOL_135 = 135; // 1.2.0.24, 1.2.0.25
	public const PROTOCOL_136 = 136; // 1.2.0.31
	public const PROTOCOL_137 = 137; // 1.2.0, 1.2.1, 1.2.2, 1.2.3.3, 1.2.3, 1.2.5.0
	public const PROTOCOL_140 = 140; // 1.2.5.11
	public const PROTOCOL_141 = 141; // 1.2.5, 1.2.5.15
	public const PROTOCOL_150 = 150; // 1.2.6, 1.2.6.1
	public const PROTOCOL_160 = 160; // 1.2.7, 1.2.8, 1.2.9
	public const PROTOCOL_200 = 200; // 1.2.10.1
	public const PROTOCOL_201 = 201; // 1.2.10, 1.2.11
	public const PROTOCOL_220 = 220; // 1.2.13.5, 1.2.13.6
	public const PROTOCOL_221 = 221; // 1.2.13.8
	public const PROTOCOL_222 = 222; // 1.2.13.10
	public const PROTOCOL_223 = 223; // 1.2.3, 1.2.4, 1.2.5, 1.2.13.60
	public const PROTOCOL_224 = 224; // 1.2.13.11
	public const PROTOCOL_240 = 240; // 1.2.14.2, 1.2.14.3
	public const PROTOCOL_250 = 250; // 1.2.15.1
	public const PROTOCOL_260 = 260; // 1.2.20.1, 1.2.20.2

	public const PROTOCOL_261 = 261; // 1.4.0, 1.4.1, 1.4.2, 1.4.3, 1.4.4

	public const PROTOCOL_270 = 270; // 1.5.0.0
	public const PROTOCOL_271 = 271; // 1.5.0.1, 1.5.0.4
	public const PROTOCOL_273 = 273; // 1.5.0.7
	public const PROTOCOL_274 = 274; // 1.5.0.10, 1.5.0, 1.5.1, 1.5.2, 1.5.3

	public const PROTOCOL_280 = 280; // 1.6.0.1
	public const PROTOCOL_281 = 281; // 1.6.0.5, 1.6.0.6
	public const PROTOCOL_282 = 282; // 1.6.0.8, 1.6.0.30, 1.6.0, 1.6.1, 1.6.2

	public const PROTOCOL_290 = 290; // 1.7.0.2, 1.7.0.3
	public const PROTOCOL_291 = 291; // 1.7.0.5, 1.7.0.7, 1.7.0.9, 1.7.0, 1.7.1

	public const PROTOCOL_310 = 310; // 1.8.0.4, 1.8.0.8
	public const PROTOCOL_311 = 311; // 1.8.0.9, 1.8.0.10
	public const PROTOCOL_312 = 312; // 1.8.0.11, 1.8.0.13, 1.8.0.14
	public const PROTOCOL_313 = 313; // 1.8.0, 1.8.1

	public const PROTOCOL_330 = 330; // 1.9.0.0
	public const PROTOCOL_331 = 331; // 1.9.0.2
	public const PROTOCOL_332 = 332; // 1.9.0.3, 1.9.0.5, 1.9.0

	public const PROTOCOL_340 = 340; // 1.10.0.3, 1.10.0.4, 1.10.0, 1.10.1
	public const PROTOCOL_342 = 342; // 1.10.0

	public const PROTOCOL_350 = 350; // 1.11.0.1
	public const PROTOCOL_351 = 351; // 1.11.0.3
	public const PROTOCOL_352 = 352; // 1.11.0.4
	public const PROTOCOL_353 = 353; // 1.11.0.5
	public const PROTOCOL_354 = 354; // 1.11.0.7, 1.11.0.8, 1.11.0.9, 1.11.0.10, 1.11.0, 1.11.1, 1.11.2, 1.11.3, 1.11.4

	public const PROTOCOL_360 = 360; // 1.12.0.2
	public const PROTOCOL_361 = 361; // 1.12.0.3, 1.12.0.4, 1.12.0.6, 1.12.0.9, 1.12.0.10, 1.12.0.11, 1.12.0.12, 1.12.0.13, 1.12.0.14

	public const PROTOCOL_370 = 370; // 1.13.0.1, 1.13.0.2
	public const PROTOCOL_371 = 371; // 1.13.0.4, 1.13.0.5, 1.13.0.6
	public const PROTOCOL_385 = 385; // 1.13.0.7, 1.13.0.9, 1.13.0.10
	public const PROTOCOL_386 = 386; // 1.13.0.12
	public const PROTOCOL_387 = 387; // 1.13.0.15
	public const PROTOCOL_388 = 388; // 1.13.0.16, 1.13.0.17, 1.13.0, 1.13.1, 1.13.2, 1.13.3
	public const PROTOCOL_389 = 389; // 1.13.0.18, 1.14.0.2, 1.14.0.3, 1.14.0.4, 1.14.0.6, 1.14.0.50, 1.14.0.51, 1.14.0.52, 1.14.0, 1.14.0.12, 1.14.1.2, 1.14.1.3, 1.14.1, 1.14.2.50, 1.14.2.51, 1.14.25.1, 1.14.20, 1.14.30.51, 1.14.30, 1.14.41

    public const PROTOCOL_390 = 390; // 1.14.0.1, 1.14.60

	public const PROTOCOL_391 = 391; // 1.15.0.8, 1.15.0.9, 1.15.0.11
	public const PROTOCOL_392 = 392; // 1.15.0.51
	public const PROTOCOL_393 = 393; // 1.15.0.53
	public const PROTOCOL_394 = 394; // 1.15.0.54
	public const PROTOCOL_395 = 395; // 1.15.0.55
	public const PROTOCOL_396 = 396; // 1.15.0.56

    public const PROTOCOL_400 = 400; // 1.16.0.51
	public const PROTOCOL_401 = 401; // 1.16.0.53, 1.16.0.58, 1.16.0.59
	public const PROTOCOL_402 = 402; // 1.16.0.55
	public const PROTOCOL_403 = 403; // 1.16.0.57
	public const PROTOCOL_404 = 404; // 1.16.0.60
	public const PROTOCOL_405 = 405; // 1.16.0.61
	public const PROTOCOL_406 = 406; // 1.16.0.63
	public const PROTOCOL_407 = 407; // 1.16.0.64
	public const PROTOCOL_408 = 408; // 1.16.20.53, 1.16.20.54, 1.16.20
	public const PROTOCOL_409 = 409; // 1.16.100.50
    public const PROTOCOL_410 = 410; // 1.16.100.51
    public const PROTOCOL_411 = 411; // 1.16.100.52
	public const PROTOCOL_412 = 412; // 1.16.100.53
	public const PROTOCOL_413 = 413; // 1.16.100.54
	public const PROTOCOL_414 = 414; // 1.16.100.55
	public const PROTOCOL_415 = 415; // 1.16.100.56
	public const PROTOCOL_416 = 416; // 1.16.100.57
	public const PROTOCOL_417 = 417; // 1.16.100.58
	public const PROTOCOL_418 = 418; // 1.16.100.59
	public const PROTOCOL_419 = 419; // 1.16.200.51
	public const PROTOCOL_420 = 420; // 1.16.200.52
	public const PROTOCOL_421 = 421; // 1.16.100.60, 1.16.100, 1.16.101
	public const PROTOCOL_422 = 422; // 1.16.200.56
	public const PROTOCOL_423 = 423; // 1.16.210.50, 1.16.210.51
	public const PROTOCOL_424 = 424; // 1.16.210.53
	public const PROTOCOL_425 = 425; // 1.16.210.54, 1.16.210.55
	public const PROTOCOL_427 = 427; // 1.16.210.56, 1.16.210.57
	public const PROTOCOL_428 = 428; // 1.16.210.58, 1.16.210.59, 1.16.210.60, 1.16.210.61, 1.16.210
	public const PROTOCOL_429 = 429; // 1.16.220.50
	public const PROTOCOL_430 = 430; // 1.16.220.51
	public const PROTOCOL_431 = 431; // 1.16.220.52, 1.16.220, 1.16.221

	public const PROTOCOL_440 = 440; // 1.17.0.54, 1.17.0.56, 1.17.0.58, 1.17.0, 1.17.1, 1.17.2
	public const PROTOCOL_448 = 448; // 1.17.10.22, 1.17.10.23, 1.17.10, 1.17.11
	public const PROTOCOL_465 = 465; // 1.17.30.25
	public const PROTOCOL_471 = 471; // 1.17.40.06

	public const PROTOCOL_475 = 475; // 1.18.0.27
	public const PROTOCOL_486 = 486; // 1.18.10.28
	public const PROTOCOL_503 = 503; // 1.18.30.32

    public const PROTOCOL_526 = 526; // 1.19.0.34
    public const PROTOCOL_527 = 527; // 1.19.0, 1.19.2
    public const PROTOCOL_534 = 534; // 1.19.10.24
    public const PROTOCOL_544 = 544; // 1.19.20.2
    public const PROTOCOL_545 = 545; // 1.19.21.1
	public const PROTOCOL_553 = 553; // 1.19.30.25
	public const PROTOCOL_554 = 554; // 1.19.30, 1.19.31
	public const PROTOCOL_557 = 557; // 1.19.40
	public const PROTOCOL_560 = 560; // 1.19.50.23
	public const PROTOCOL_567 = 567; // 1.19.60.26
	public const PROTOCOL_568 = 568; // 1.19.63
	public const PROTOCOL_575 = 575; // 1.19.70
	public const PROTOCOL_582 = 582; // 1.19.80

	public const PROTOCOL_589 = 589; // 1.20.0
	public const PROTOCOL_594 = 594; // 1.20.10
	public const PROTOCOL_618 = 618; // 1.20.30
	public const PROTOCOL_622 = 622; // 1.20.40
	public const PROTOCOL_630 = 630; // 1.20.50
	public const PROTOCOL_649 = 649; // 1.20.60
	public const PROTOCOL_662 = 662; // 1.20.70
	public const PROTOCOL_671 = 671; // 1.20.80

	public const PROTOCOL_685 = 685; // 1.21.0
	public const PROTOCOL_686 = 686; // 1.21.2
	public const PROTOCOL_712 = 712; // 1.21.20
	public const PROTOCOL_729 = 729; // 1.21.30
	public const PROTOCOL_748 = 748; // 1.21.40
	public const PROTOCOL_766 = 766; // 1.21.50
	public const PROTOCOL_776 = 776; // 1.21.60
	public const PROTOCOL_786 = 786; // 1.21.70
	public const PROTOCOL_800 = 800; // 1.21.80
    /**
	 * Current Minecraft PE version reported by the server. This is usually the earliest currently supported version.
	 */
	public const MINECRAFT_VERSION = '0.14.0 - 1.21.84';
	/**
	 * Version number sent to clients in ping responses.
	 */
	public const MINECRAFT_VERSION_NETWORK = '1.21.80';

}