<?php

namespace Drupal\gepsis\Utility;


use Symfony\Component\Mime\Encoder\Base64Encoder;

class PHP_AES_Cipher {
	private static $OPENSSL_CIPHER_NAME = "aes-128-cbc"; // Name of OpenSSL Cipher
	private static $CIPHER_KEY_LEN = 16;

	static function encryptToken($key, $iv, $data){
		if (strlen($key) < PHP_AES_Cipher::$CIPHER_KEY_LEN) {
			$key = str_pad("$key", PHP_AES_Cipher::$CIPHER_KEY_LEN, "0"); // 0 pad to len 16
		} else if (strlen($key) > PHP_AES_Cipher::$CIPHER_KEY_LEN) {
			$key = substr($key, 0, PHP_AES_Cipher::$CIPHER_KEY_LEN); // truncate to 16 bytes
		}
		
		$encodedEncryptedData = base64_encode(openssl_encrypt($data, PHP_AES_Cipher::$OPENSSL_CIPHER_NAME, $key, OPENSSL_RAW_DATA, $iv));
		$encodedIV = base64_encode($iv);
		$encryptedPayload = $encodedEncryptedData . ":" . $encodedIV;
		return $encryptedPayload;
	}

	static function decryptToken($key, $data){
		if (strlen($key) < PHP_AES_Cipher::$CIPHER_KEY_LEN) {
			$key = str_pad("$key", PHP_AES_Cipher::$CIPHER_KEY_LEN, "0"); // 0 pad to len 16
		} else if (strlen($key) > PHP_AES_Cipher::$CIPHER_KEY_LEN) {
			$key = substr($key, 0, PHP_AES_Cipher::$CIPHER_KEY_LEN); // truncate to 16 bytes
		}

		$parts = explode(':', $data); // Separate Encrypted data from iv.
		$decryptedData = openssl_decrypt(base64_decode($parts[0]), PHP_AES_Cipher::$OPENSSL_CIPHER_NAME, $key, OPENSSL_RAW_DATA, base64_decode($parts[1]));

		return $decryptedData;
	}
}

