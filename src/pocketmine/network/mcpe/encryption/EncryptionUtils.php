<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\encryption;

use GMP;
use InvalidArgumentException;
use JsonException;
use pocketmine\network\mcpe\JwtUtils;
use function base64_encode;
use function bin2hex;
use function gmp_init;
use function gmp_strval;
use function hex2bin;
use function openssl_digest;
use function openssl_error_string;
use function openssl_pkey_derive;
use function str_pad;

final class EncryptionUtils{

	private function __construct(){
		//NOOP
	}

	public static function generateSharedSecret($localPriv, $remotePub) : GMP{
		$hexSecret = openssl_pkey_derive($remotePub, $localPriv, 48);
		if($hexSecret === false){
			throw new InvalidArgumentException("Failed to derive shared secret: " . openssl_error_string());
		}
		return gmp_init(bin2hex($hexSecret), 16);
	}

	public static function generateKey(GMP $secret, string $salt) : string{
		return openssl_digest($salt . hex2bin(str_pad(gmp_strval($secret, 16), 96, "0", STR_PAD_LEFT)), 'sha256', true);
	}

	/**
	 * @throws JsonException
	 */
	public static function generateServerHandshakeJwt(string $derPublicKey, $serverPriv, string $salt) : string{
		return JwtUtils::create(
			[
				"x5u" => base64_encode($derPublicKey),
				"alg" => "ES384"
			],
			[
				"salt" => base64_encode($salt)
			],
			$serverPriv
		);
	}
}