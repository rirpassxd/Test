<?php

declare(strict_types=1);

namespace pocketmine\utils;

enum OS : string{
	case WINDOWS = 'win';
	case IOS = 'ios';
	case MACOS = 'mac';
	case ANDROID = 'android';
	case LINUX = 'linux';
	case BSD = 'bsd';
	case UNKNOWN = 'other';
}