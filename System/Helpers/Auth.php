<?php

namespace Melidev\System\Helpers;

class Auth{

    private static $facebook;
    /**
     * Connexion de l'utilisateur
     * @param  [type] $login
     * @param  [type] $password
     */
    public static function attempt($login, $password){
        $loginField = strtoupper($GLOBALS['conf']['auth']['login']);
        $compte = $GLOBALS['conf']['auth']['class']::query()->where($loginField, $login)->first();

        if($compte && Hash::check($password, $compte->pass)){
            $compte->setExpr('lastConnect', 'NOW()');
            $compte->save();
            Session::put('current_user', $compte->id);
            return true;
        }
        return false;
    }

    /**
     * Rafraichi l'utilisateur
     * @param  [type] $login
     * @param  [type] $password
     */
    public static function refresh(){
        if(self::check()){
            $loginField = strtoupper($GLOBALS['conf']['auth']['login']);
            $compte = $GLOBALS['conf']['auth']['class']::query()->where($loginField, self::user()->$GLOBALS['conf']['auth']['login'])->first();
            Session::put('current_user', $compte->id);
            return true;
        }
        return false;
    }

    /**
     * Vérifie si le visiteur est connecté
     */
    public static function check(){
        return Session::has('current_user');
    }


    /**
     * Retour l'utilisateur actuellement connecté ou null
     */
    public static function user(){
        if(self::check())
            return $GLOBALS['conf']['auth']['class']::query()->find(Session::get('current_user'));
        else
            return false;
    }

    /**
     * Déconnecte l'utilisateur
     */
    public static function logout(){
        return Session::forget('current_user');
    }

    /**
     * Vérifie si les identifiants de connexion sont corrects sans connecter l'utilisateur
     */
    public static function validate($login, $password){
        $loginField = strtoupper($GLOBALS['conf']['auth']['login']);
        $compte = $GLOBALS['conf']['auth']['class']::query()->where($loginField, $login)->first();

        if(Hash::check($password, $compte->mdp_hash)){
            return true;
        }
        return false;
    }

}

/* End of file */