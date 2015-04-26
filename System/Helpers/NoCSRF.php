<?php

namespace System\Helpers;


/**
 * NoCSRF Class
 *
 * @author anaeria
 */

class NoCSRF {

    protected static $doOriginCheck = TRUE;

    /**
     * Check CSRF tokens match between session and $origin.
     * Make sure you generated a token in the form before checking it.
     *
     * @param String $key The session and $origin key where to find the token.
     * @param String $token The token sent by client
     * @param Integer $timespan (Facultative) Makes the token expire after $timespan seconds. (NULL = never)
     * @param Boolean $multiple (Facultative) Makes the token reusable and not one-time. (Useful for ajax-heavy requests).
     *
     * @return Boolean Returns FALSE if a CSRF attack is detected, TRUE otherwise.
     */
    public static function check($key, $token, $timespan = 600, $multiple = TRUE) {
        if (!Session::has('csrf_'.$key)) {
            throw new Exception('Missing CSRF session token.');
        }

        if (!isset($token)) {
            throw new Exception('Missing CSRF form token.');
        }

        // Get valid token from session
        $hash = Session::get('csrf_'.$key);

        // Free up session token for one-time CSRF token usage.
        if (!$multiple) {
            Session::forget('csrf_'.$key);
        }

        // Origin checks
        if (self::$doOriginCheck && sha1($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']) != substr(Hash::decode($hash), 16, 40)) {
            throw new Exception('Form origin does not match token origin.');
        }

        // Check if session token matches form token
        if ($token != $hash) {
            throw new Exception('Invalid CSRF token.');
        }

        // Check for token expiration
        if ($timespan != NULL && is_int($timespan)) {
            $rand_add     = substr(Hash::decode($hash), 0, 5);
            $rand_mult    = substr(Hash::decode($hash), 15, 1);
            $encoded_time = substr(Hash::decode($hash), 5, 10);
            $decoded_time = (($encoded_time + ($rand_add*2)) / $rand_mult) - $rand_add;

            if(intval($decoded_time + $timespan < time())) {
                throw new Exception('CSRF token has expired.');
            }
        }

        return TRUE;
    }


    // -------------------------------------------------------------------------

    /**
     * Mutateur du controle de l'origine
     */

    public static function disableOriginCheck(){
        self::$doOriginCheck = FALSE;
    }

    // -------------------------------------------------------------------------

    /**
     * Génère un token de validation
     *
     * @param string key
     * @return string
     */

    public static function generate($key){
        $extra           = self::$doOriginCheck ? sha1($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']) : '';
        $random_multiple = mt_rand(2,5);
        $random_add      = mt_rand(10001, 99999);
        $timed           = time();
        $time            = (($timed + $random_add) * $random_multiple) - ($random_add * 2);
        $token           = Hash::encode($random_add . $time . $random_multiple . $extra . openssl_random_pseudo_bytes(64));

        // store the one-time token in session
        Session::put('csrf_'.$key, $token);

        return $token;
    }

}

/* End of file */