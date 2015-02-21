<?php

namespace System\Helpers;

class Hash{

    protected static $_mcrypt_cipher;
    protected static $_mcrypt_mode;
    protected static $encryption_key = '';
    protected static $_hash_method = '';
    protected static $_hash_type = 'sha1';



    public static function set_hash_method($hash_method){
        self::$_hash_method = $hash_method;
    }

    public static function make($password){
        if(self::$_hash_method == ''){
            self::$_hash_method = $GLOBALS['conf']['hash']['salt_method'];
        }

        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $GLOBALS['conf']['hash']['cost'], 'salt' => call_user_func('System\Helpers\Hash::' . self::$_hash_method)]);
    }

    public static function check($password, $hash){
        return password_verify($password, $hash);
    }

    public static function needsRehash($password, $hash) {
        if(self::$_hash_method == ''){
            self::$_hash_method = $GLOBALS['conf']['hash']['salt_method'];
        }

        return password_needs_rehash($password, PASSWORD_BCRYPT, ['cost' => $GLOBALS['conf']['hash']['cost'], 'salt' => call_user_func('System\Helpers\Hash::' . self::$_hash_method)]);
    }

    public static function benchmark_cost($time_target = 0.5){

        $cost = 9;
        do {
            $cost++;
            $start = microtime(true);
            password_hash("Secret_Password", PASSWORD_BCRYPT, ['cost' => $cost, 'salt' => call_user_func('System\Helpers\Hash::' . self::$_hash_method)]);
            $end = microtime(true);
        } while (($end - $start) < $time_target);

        return $cost;
    }

    public static function encode($string, $key = ''){
        return base64_encode(self::mcrypt_encode($string, self::get_key($key)));
    }

    public static function decode($string, $key = ''){
        if (preg_match('/[^a-zA-Z0-9\/\+=]/', $string)){
            return FALSE;
        }

        return self::mcrypt_decode(base64_decode($string), self::get_key($key));
    }

    public static function set_hash($hash){
        self::$_hash_type = $hash;
    }


    private static function hash_str($str){
        return (self::$_hash_type === 'sha1') ? sha1($str) : md5($str);
    }

    public static function set_key($key = ''){
        self::$encryption_key = $key;
    }

    public static function set_mode($mode){
        self::$_mcrypt_mode = $mode;
    }

    private static function get_key($key = ''){
        if ($key == ''){
            if (self::$encryption_key != ''){
                return self::$encryption_key;
            }
            return self::$encryption_key = $GLOBALS['conf']['encryption_key'];
        }
        return md5($key);
    }

    private static function mcrypt_encode($data, $key){
        $init_size = mcrypt_get_iv_size(self::_get_cipher(), self::_get_mode());
        $init_vect = mcrypt_create_iv($init_size, MCRYPT_RAND);
        return self::_add_cipher_noise($init_vect.mcrypt_encrypt(self::_get_cipher(), $key, $data, self::_get_mode(), $init_vect), $key);
    }

    private static function mcrypt_decode($data, $key){
        $data = self::_remove_cipher_noise($data, $key);
        $init_size = mcrypt_get_iv_size(self::_get_cipher(), self::_get_mode());

        if ($init_size > strlen($data)){
            return FALSE;
        }

        $init_vect = substr($data, 0, $init_size);
        $data = substr($data, $init_size);
        return rtrim(mcrypt_decrypt(self::_get_cipher(), $key, $data, self::_get_mode(), $init_vect), "\0");
    }

    private static function _get_cipher(){
        if (self::$_mcrypt_cipher == ''){
            return self::$_mcrypt_cipher = MCRYPT_RIJNDAEL_256;
        }
        return self::$_mcrypt_cipher;
    }

    private static function _get_mode(){
        if (self::$_mcrypt_mode == ''){
            return self::$_mcrypt_mode = MCRYPT_MODE_CBC;
        }
        return self::$_mcrypt_mode;
    }

    private static function set_cipher($cipher){
        self::$_mcrypt_cipher = $cipher;
    }

    private static function _add_cipher_noise($data, $key){
        $key = self::hash_str($key);
        $str = '';

        for ($i = 0, $j = 0, $ld = strlen($data), $lk = strlen($key); $i < $ld; ++$i, ++$j){
            if ($j >= $lk){
                $j = 0;
            }
            $str .= chr((ord($data[$i]) + ord($key[$j])) % 256);
        }

        return $str;
    }

    private static function _remove_cipher_noise($data, $key){
        $key = self::hash_str($key);
        $str = '';

        for ($i = 0, $j = 0, $ld = strlen($data), $lk = strlen($key); $i < $ld; ++$i, ++$j){
            if ($j >= $lk){
                $j = 0;
            }

            $temp = ord($data[$i]) - ord($key[$j]);

            if ($temp < 0){
                $temp += 256;
            }

            $str .= chr($temp);
        }
        return $str;
    }

    private static function default_hash_salt_method(){
        return mcrypt_create_iv(22, MCRYPT_DEV_URANDOM);
    }
}

/* End of file */