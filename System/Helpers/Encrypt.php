<?php

namespace System\Helpers;

/**
 * Encryption Class
 *
 * Provides two-way keyed encoding using Mcrypt
 *
 * @author sugatasei
 */
class Encrypt {

    /**
     * Encryption key
     *
     * @var string
     */
    private $key;

    /**
     * Mcrypt Cipher
     *
     * @var string
     */
    private $cipher;

    /**
     * Mcrypt Mode
     *
     * @var string
     */
    private $mode;

    // -------------------------------------------------------------------------

    /**
     * Set the encryption key
     *
     * @param string $key
     * @return \Orb\Security\Encrypt
     */
    public function setKey($key) {

        if ($key) {
            $this->key = $key;
        }

        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Set the Mcrypt Cipher
     *
     * @param string $cipher
     * @return \Orb\Security\Encrypt
     */
    public function setCipher($cipher) {
        $this->cipher = $cipher;
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Set the Mcrypt Mode
     *
     * @param string $mode
     * @return \Orb\Security\Encrypt
     */
    public function setMode($mode) {
        $this->mode = $mode;
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns an encoded string
     *
     * @param string $string
     * @param string $key
     * @return string
     */
    public function encode($string, $key = null) {
        $key = $this->_getKey($key);
        $enc = $this->_mcrypt_encode($string, $key);

        return base64_encode($enc);
    }

    // -------------------------------------------------------------------------

    /**
     * Returns a decoded string
     *
     * @param string $string
     * @param string $key
     * @return string|false
     */
    public function decode($string, $key = '') {
        $key = $this->_getKey($key);

        if (preg_match('/[^a-zA-Z0-9\/\+=]/', $string)) {
            return false;
        }

        $dec = base64_decode($string);

        return $this->_mcrypt_decode($dec, $key);
    }

    // -------------------------------------------------------------------------

    /**
     * Returns the encryption key
     *
     * Returns it as MD5 in order to have an exact-length 128 bit key.
     * Mcrypt is sensitive to keys that are not the correct length
     *
     * @param string $key
     * @return string
     * @throws \RuntimeException
     */
    private function _getKey($key = null) {

        if ($key == null) {

            if (!isset($this->key)) {
                throw new \RuntimeException('An encryption key is required');
            }

            $key = $this->key;
        }

        return md5($key);
    }

    // -------------------------------------------------------------------------

    /**
     * Returns the Mcrypt Cipher
     *
     * @return string
     */
    private function _getCipher() {

        if (!isset($this->cipher)) {
            $this->cipher = MCRYPT_RIJNDAEL_256;
        }

        return $this->cipher;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns the Mcrypt Mode
     *
     * @return string
     */
    private function _getMode() {

        if (!isset($this->mode)) {
            $this->mode = MCRYPT_MODE_CBC;
        }

        return $this->mode;
    }

    // -------------------------------------------------------------------------

    /**
     * Encrypt using Mcrypt
     *
     * @param string $data
     * @param string $key
     */
    private function _mcrypt_encode($data, $key) {
        $cipher   = $this->_getCipher();
        $mode     = $this->_getMode();
        $initSize = mcrypt_get_iv_size($cipher, $mode);
        $initVect = mcrypt_create_iv($initSize, MCRYPT_RAND);
        $initEnc  = mcrypt_encrypt($cipher, $key, $data, $mode, $initVect);

        return $this->_addNoise($initVect . $initEnc, $key);
    }

    // -------------------------------------------------------------------------

    /**
     * Add noise to the IV + encrypted data
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    private function _addNoise($data, $key) {
        $keyhash = sha1($key);
        $keylen  = strlen($keyhash);
        $str     = '';

        for ($i = 0, $j = 0, $len = strlen($data); $i < $len; ++$i, ++$j) {
            if ($j >= $keylen) {
                $j = 0;
            }

            $str .= chr((ord($data[$i]) + ord($keyhash[$j])) % 256);
        }

        return $str;
    }

    // -------------------------------------------------------------------------

    /**
     * Decrypt using Mcrypt
     *
     * @param type $data
     * @param type $key
     */
    private function _mcrypt_decode($data, $key) {
        $data   = $this->_removeNoise($data, $key);
        $cipher = $this->_getCipher();
        $mode   = $this->_getMode();

        $initSize = mcrypt_get_iv_size($cipher, $mode);

        if ($initSize > strlen($data)) {
            return false;
        }

        $initVect = substr($data, 0, $initSize);
        $data     = substr($data, $initSize);
        $initDec  = mcrypt_decrypt($cipher, $key, $data, $mode, $initVect);

        return rtrim($initDec, "\0");
    }

    // -------------------------------------------------------------------------

    /**
     * Removes permuted noise from the IV + encrypted data
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    private function _removeNoise($data, $key) {
        $keyhash = sha1($key);
        $keylen  = strlen($keyhash);
        $str     = '';

        for ($i = 0, $j = 0, $len = strlen($data); $i < $len; ++$i, ++$j) {
            if ($j >= $keylen) {
                $j = 0;
            }

            $temp = ord($data[$i]) - ord($keyhash[$j]);

            if ($temp < 0) {
                $temp = $temp + 256;
            }

            $str .= chr($temp);
        }

        return $str;
    }

    // -------------------------------------------------------------------------
}

/* End of file */