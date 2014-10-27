<?php if ( ! defined('WEBROOT')) exit('No direct script access allowed');

function default_hash_salt_method(){
    return mcrypt_create_iv(22, MCRYPT_DEV_URANDOM);
}

function br2nl($string){
    return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
}

function UnderscoreToCamel($str, $capitalise_first_char = false) {
    if($capitalise_first_char) {
        $str[0] = strtoupper($str[0]);
    }
    $func = create_function('$c', 'return strtoupper($c[1]);');
    return preg_replace_callback('/_([a-z])/', $func, $str);
}

function camelToUnderscore($str) {
    $str[0] = strtolower($str[0]);
    $func = create_function('$c', 'return "_" . strtolower($c[1]);');
    return preg_replace_callback('/([A-Z])/', $func, $str);
}

function firstLetter($string){
    return $string[0];
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/* End of file */