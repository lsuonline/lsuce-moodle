<?php

/**
 * Class for doing AES Rijndael encryption/decryption using CBC cipher mode
 *
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!function_exists( 'openssl_encrypt' )) {
    if(!function_exists( 'mcrypt_encrypt' )) {
        die('OpenSSL or mcrypt is required for BioSig');
    }
}

class AESEncryption {

    private $securekey, $iv;

    function __construct($passphrase, $salt, $iv, $keysize) {
        //setup the key and vector for encryption/decryption
        $devkeylength = ($keysize / 8) + 2;
        $devkey = $this->createSecureKey($passphrase, $salt, 2, $devkeylength);
        $key_hex = bin2hex(substr($devkey, 0, 16));
        $key_bin = pack('H*', $key_hex);
        $this->securekey = $key_bin;
        $this->iv = $iv;
    }

    public function encrypt($input) {
        if (function_exists( 'openssl_encrypt' )) {
                //OpenSSL adds padding -- no need to disable via OPENSSL_ZERO_PADDING to force padData()
            return base64_encode(openssl_encrypt($input, 'AES-128-CBC', $this->securekey, OPENSSL_RAW_DATA, $this->iv));
        } else {
            return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->securekey, $this->padData($input), MCRYPT_MODE_CBC, $this->iv));
        }
    }

    public function decrypt($input) {
        if (function_exists( 'openssl_encrypt' )) {
            return trim(openssl_decrypt(base64_decode($input), 'AES-128-CBC', $this->securekey, OPENSSL_RAW_DATA, $this->iv));
        } else {
            return $this->unpadData(trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->securekey, base64_decode($input), MCRYPT_MODE_CBC, $this->iv)));
        }

    }

    //
    // Add PKCS7 padding
    //
    private function padData($data) {
        $block_size = (function_exists( 'openssl_encrypt' )) ? 128 : mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $len = strlen($data);
        $padding = $block_size - ($len % $block_size);
        $data .= str_repeat(chr($padding), $padding);

        return $data;
    }

    //
    // Strip PKCS7 padding
    //
    private function unpadData($data) {
        $pattern = substr($data, -1);
        $length = ord($pattern);
        $padding = str_repeat($pattern, $length);
        $pattern_pos = strlen($data) - $length;

        if (substr($data, $pattern_pos) == $padding) {
            return substr($data, 0, $pattern_pos);
        }

        return $data;
    }

    //
    // Create the secret key from the passphrase and salt value
    //
    private function createSecureKey($pass, $salt, $count, $dklen) {
        $t = $pass . $salt;
        for ($i=0; $i < $count; $i++) {
            $t = sha1($t, true);
        }
        $t = substr($t, 0, $dklen-1);

        return $t;
    }
}
?>