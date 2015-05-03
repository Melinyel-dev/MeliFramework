<?php

namespace System\Helpers;


/**
 * Auth Class
 *
 * Gestion de l'autentification utilisateur
 *
 * @author anaeria
 */

class Auth {

    private static $facebook;


    /**
     * Connexion de l'utilisateur
     *
     * @param string login
     * @param string password
     * @return boolean
     */
    public static function attempt($login, $password) {
        $compte = $GLOBALS['conf']['auth']['class']::where($GLOBALS['conf']['auth']['login'], $login)->first();

        if ($compte && Hash::check($password, $compte->$GLOBALS['conf']['auth']['password'])) {
            if (Hash::needsRehash($password, $compte->$GLOBALS['conf']['auth']['password'])) {
                $compte->$GLOBALS['conf']['auth']['password'] = Hash::make($password);
            }
            $compte->lastConnect = ['NOW()'];
            $compte->save();
            Session::put('current_user', $compte->id);
            return TRUE;
        }
        return FALSE;
    }


    // -------------------------------------------------------------------------

    /**
     * Rafraichi l'utilisateur
     *
     * @return boolean
     */
    public static function refresh() {
        if (self::check()) {
            $compte = $GLOBALS['conf']['auth']['class']::where($GLOBALS['conf']['auth']['login'], self::user()->$GLOBALS['conf']['auth']['login'])->first();
            Session::put('current_user', $compte->id);
            return TRUE;
        }
        return FALSE;
    }


    // -------------------------------------------------------------------------

    /**
     * Vérifie si le visiteur est connecté
     *
     * @return boolean
     */
    public static function check() {
        return Session::has('current_user');
    }


    // -------------------------------------------------------------------------

    /**
     * Retour l'utilisateur actuellement connecté ou FALSE
     *
     * @return FALSE | object
     */
    public static function user() {
        if (self::check()) {
            if (!isset($GLOBALS['current_user'])) {
                $GLOBALS['current_user'] = $GLOBALS['conf']['auth']['class']::find(Session::get('current_user'));
            }
            return $GLOBALS['current_user'];
        } else {
            return FALSE;
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Déconnecte l'utilisateur
     *
     * @return boolean
     */
    public static function logout() {
        return Session::forget('current_user');
    }


    // -------------------------------------------------------------------------

    /**
     * Vérifie si les identifiants de connexion sont corrects sans connecter l'utilisateur
     *
     * @return boolean
     */
    public static function validate($login, $password) {
        $compte = $GLOBALS['conf']['auth']['class']::where($GLOBALS['conf']['auth']['login'], $login)->first();

        if (Hash::check($password, $compte->mdp_hash)) {
            return TRUE;
        }
        return FALSE;
    }

}

/* End of file */