<?php
require_once 'config.php';

class Encryption {

    /**
     * Encrypt data
     * @param string $data Data to encrypt
     * @return string Encrypted data
     */
    public static function encrypt($data) {
        $cipher = "AES-256-CBC";
        $key = hash('sha256', ENCRYPTION_KEY, true);
        $iv = ENCRYPTION_IV;

        $encrypted = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
    }

    /**
     * Decrypt data
     * @param string $data Encrypted data
     * @return string Decrypted data
     */
    public static function decrypt($data) {
        $cipher = "AES-256-CBC";
        $key = hash('sha256', ENCRYPTION_KEY, true);
        $iv = ENCRYPTION_IV;

        $data = strtr($data, '-_', '+/');
        $data = base64_decode($data . str_repeat('=', 3 - (3 + strlen($data)) % 4));

        $decrypted = openssl_decrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted;
    }

    /**
     * Encrypt URL with ID and timestamp
     * @param int $id QR code ID
     * @return string Encrypted URL parameter
     */
    public static function encryptUrl($id) {
        $data = "id=" . $id . "&t=" . time();
        return self::encrypt($data);
    }

    /**
     * Decrypt URL data
     * @param string $encrypted Encrypted URL parameter
     * @return array Decrypted data as array
     */
    public static function decryptUrl($encrypted) {
        $decrypted = self::decrypt($encrypted);
        parse_str($decrypted, $params);
        return $params;
    }
}
?>