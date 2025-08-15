<?php

declare(strict_types=1);

namespace pocketmine\utils;

abstract class Terminal{
	public static $FORMAT_BOLD = "";
	public static $FORMAT_OBFUSCATED = "";
	public static $FORMAT_ITALIC = "";
	public static $FORMAT_UNDERLINE = "";
	public static $FORMAT_STRIKETHROUGH = "";

	public static $FORMAT_RESET = "";

	public static $COLOR_BLACK = "";
	public static $COLOR_DARK_BLUE = "";
	public static $COLOR_DARK_GREEN = "";
	public static $COLOR_DARK_AQUA = "";
	public static $COLOR_DARK_RED = "";
	public static $COLOR_PURPLE = "";
	public static $COLOR_GOLD = "";
	public static $COLOR_GRAY = "";
	public static $COLOR_DARK_GRAY = "";
	public static $COLOR_BLUE = "";
	public static $COLOR_GREEN = "";
	public static $COLOR_AQUA = "";
	public static $COLOR_RED = "";
	public static $COLOR_LIGHT_PURPLE = "";
	public static $COLOR_YELLOW = "";
	public static $COLOR_WHITE = "";

	private static $formattingCodes = null;

	public static function hasFormattingCodes(){
		if(self::$formattingCodes === null){
			$opts = getopt("", ["enable-ansi", "disable-ansi"]);
			if(isset($opts["disable-ansi"])){
				self::$formattingCodes = false;
			}else{
				$stdout = fopen("php://stdout", "w");
				self::$formattingCodes = (isset($opts["enable-ansi"]) or ( //user explicitly told us to enable ANSI
					stream_isatty($stdout) and //STDOUT isn't being piped
					(
						getenv('TERM') !== false or //Console says it supports colours
						(function_exists('sapi_windows_vt100_support') and sapi_windows_vt100_support($stdout)) //we're on windows and have vt100 support
					)
				));
				fclose($stdout);
			}
		}

		return self::$formattingCodes;
	}

	/**
	 * Returns a string with colorized ANSI Escape codes for the current terminal
	 * Note that this is platform-dependent and might produce different results depending on the terminal type and/or OS.
	 */
	public static function toANSI(string $string) : string{
		$newString = "";
		foreach(TextFormat::tokenize($string) as $token){
			$newString .= match($token){
				TextFormat::BOLD => Terminal::$FORMAT_BOLD,
				TextFormat::OBFUSCATED => Terminal::$FORMAT_OBFUSCATED,
				TextFormat::ITALIC => Terminal::$FORMAT_ITALIC,
				TextFormat::UNDERLINE => Terminal::$FORMAT_UNDERLINE,
				TextFormat::STRIKETHROUGH => Terminal::$FORMAT_STRIKETHROUGH,
				TextFormat::RESET => Terminal::$FORMAT_RESET,
				TextFormat::BLACK => Terminal::$COLOR_BLACK,
				TextFormat::DARK_BLUE => Terminal::$COLOR_DARK_BLUE,
				TextFormat::DARK_GREEN => Terminal::$COLOR_DARK_GREEN,
				TextFormat::DARK_AQUA => Terminal::$COLOR_DARK_AQUA,
				TextFormat::DARK_RED => Terminal::$COLOR_DARK_RED,
				TextFormat::DARK_PURPLE => Terminal::$COLOR_PURPLE,
				TextFormat::GOLD => Terminal::$COLOR_GOLD,
				TextFormat::GRAY => Terminal::$COLOR_GRAY,
				TextFormat::DARK_GRAY => Terminal::$COLOR_DARK_GRAY,
				TextFormat::BLUE => Terminal::$COLOR_BLUE,
				TextFormat::GREEN => Terminal::$COLOR_GREEN,
				TextFormat::AQUA => Terminal::$COLOR_AQUA,
				TextFormat::RED => Terminal::$COLOR_RED,
				TextFormat::LIGHT_PURPLE => Terminal::$COLOR_LIGHT_PURPLE,
				TextFormat::YELLOW => Terminal::$COLOR_YELLOW,
				TextFormat::WHITE => Terminal::$COLOR_WHITE,
				default => $token,
			};
		}

		return $newString;
	}

	protected static function getFallbackEscapeCodes(){
		self::$FORMAT_BOLD = "\x1b[1m";
		self::$FORMAT_OBFUSCATED = "";
		self::$FORMAT_ITALIC = "\x1b[3m";
		self::$FORMAT_UNDERLINE = "\x1b[4m";
		self::$FORMAT_STRIKETHROUGH = "\x1b[9m";

		self::$FORMAT_RESET = "\x1b[m";

		self::$COLOR_BLACK = "\x1b[38;5;16m";
		self::$COLOR_DARK_BLUE = "\x1b[38;5;19m";
		self::$COLOR_DARK_GREEN = "\x1b[38;5;34m";
		self::$COLOR_DARK_AQUA = "\x1b[38;5;37m";
		self::$COLOR_DARK_RED = "\x1b[38;5;124m";
		self::$COLOR_PURPLE = "\x1b[38;5;127m";
		self::$COLOR_GOLD = "\x1b[38;5;214m";
		self::$COLOR_GRAY = "\x1b[38;5;145m";
		self::$COLOR_DARK_GRAY = "\x1b[38;5;59m";
		self::$COLOR_BLUE = "\x1b[38;5;63m";
		self::$COLOR_GREEN = "\x1b[38;5;83m";
		self::$COLOR_AQUA = "\x1b[38;5;87m";
		self::$COLOR_RED = "\x1b[38;5;203m";
		self::$COLOR_LIGHT_PURPLE = "\x1b[38;5;207m";
		self::$COLOR_YELLOW = "\x1b[38;5;227m";
		self::$COLOR_WHITE = "\x1b[38;5;231m";
	}

	protected static function getEscapeCodes(){
		self::$FORMAT_BOLD = `tput bold`;
		self::$FORMAT_OBFUSCATED = `tput smacs`;
		self::$FORMAT_ITALIC = `tput sitm`;
		self::$FORMAT_UNDERLINE = `tput smul`;
		self::$FORMAT_STRIKETHROUGH = "\x1b[9m"; //`tput `;

		self::$FORMAT_RESET = `tput sgr0`;

		$colors = (int) `tput colors`;
		if($colors > 8){
			self::$COLOR_BLACK = $colors >= 256 ? `tput setaf 16` : `tput setaf 0`;
			self::$COLOR_DARK_BLUE = $colors >= 256 ? `tput setaf 19` : `tput setaf 4`;
			self::$COLOR_DARK_GREEN = $colors >= 256 ? `tput setaf 34` : `tput setaf 2`;
			self::$COLOR_DARK_AQUA = $colors >= 256 ? `tput setaf 37` : `tput setaf 6`;
			self::$COLOR_DARK_RED = $colors >= 256 ? `tput setaf 124` : `tput setaf 1`;
			self::$COLOR_PURPLE = $colors >= 256 ? `tput setaf 127` : `tput setaf 5`;
			self::$COLOR_GOLD = $colors >= 256 ? `tput setaf 214` : `tput setaf 3`;
			self::$COLOR_GRAY = $colors >= 256 ? `tput setaf 145` : `tput setaf 7`;
			self::$COLOR_DARK_GRAY = $colors >= 256 ? `tput setaf 59` : `tput setaf 8`;
			self::$COLOR_BLUE = $colors >= 256 ? `tput setaf 63` : `tput setaf 12`;
			self::$COLOR_GREEN = $colors >= 256 ? `tput setaf 83` : `tput setaf 10`;
			self::$COLOR_AQUA = $colors >= 256 ? `tput setaf 87` : `tput setaf 14`;
			self::$COLOR_RED = $colors >= 256 ? `tput setaf 203` : `tput setaf 9`;
			self::$COLOR_LIGHT_PURPLE = $colors >= 256 ? `tput setaf 207` : `tput setaf 13`;
			self::$COLOR_YELLOW = $colors >= 256 ? `tput setaf 227` : `tput setaf 11`;
			self::$COLOR_WHITE = $colors >= 256 ? `tput setaf 231` : `tput setaf 15`;
		}else{
			self::$COLOR_BLACK = self::$COLOR_DARK_GRAY = `tput setaf 0`;
			self::$COLOR_RED = self::$COLOR_DARK_RED = `tput setaf 1`;
			self::$COLOR_GREEN = self::$COLOR_DARK_GREEN = `tput setaf 2`;
			self::$COLOR_YELLOW = self::$COLOR_GOLD = `tput setaf 3`;
			self::$COLOR_BLUE = self::$COLOR_DARK_BLUE = `tput setaf 4`;
			self::$COLOR_LIGHT_PURPLE = self::$COLOR_PURPLE = `tput setaf 5`;
			self::$COLOR_AQUA = self::$COLOR_DARK_AQUA = `tput setaf 6`;
			self::$COLOR_GRAY = self::$COLOR_WHITE = `tput setaf 7`;
		}
	}

	public static function isInit() : bool{
		return self::$formattingCodes !== null;
	}

	public static function init(){
		if(!self::hasFormattingCodes()){
			return;
		}

		return match (OS::from(Utils::getOS())) {
			OS::LINUX, OS::MACOS, OS::BSD => self::getEscapeCodes(),
			OS::WINDOWS, OS::ANDROID => self::getFallbackEscapeCodes()
		};
	}

	/**
	 * Emits a string containing Minecraft colour codes to the console formatted with native colours, followed by a
	 * newline character.
	 */
	public static function writeLine(string $line) : void{
		echo self::toANSI($line) . self::$FORMAT_RESET . PHP_EOL;
	}

}