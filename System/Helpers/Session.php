<?php

namespace System\Helpers;

class Session{

    public static function start() {
        session_start();
    }

    public static function destroy() {
        session_destroy();
    }

    /**
     * Ajoute une variable dans la session
     * @param  [type] $key   nom de la variable
     * @param  [type] $value valeur de la variable
     */
    public static function put($key, $value) {
        $_SESSION[$key] = $value;
        return true;
    }

    /**
     * Retourne une variable de la session ou la valeur par défaut si la variable n'existe pas
     * @param  [type] $key           nom de la variable
     * @param  [type] $defaultValue valeur par défaut
     * @return [type]                [description]
     */
    public static function get($key, $defaultValue = null) {
        if (array_key_exists($key, $_SESSION)) {
            return $_SESSION[$key];
        }
        return $defaultValue;
    }

    /**
     * Retourne toute les variables de la sessions
     */
    public static function all() {
        return $_SESSION;
    }

    /**
     * Ajout un élément dans le tableau présent ou non en session (le tableau sera créé sinon)
     * @param  [type] $key   nom du tableau
     * @param  [type] $value valeur du tableau
     */
    public static function push($key, $value) {
        if(mb_strpos($key, '.') !== false){
            $key = explode('.', $key);
            if(!array_key_exists($key[0], $_SESSION)){
                $_SESSION[$key[0]] = [];
            }
            $_SESSION[$key[0]][$key[1]] = $value;
        }else{
            if(!array_key_exists($key, $_SESSION)){
                $_SESSION[$key] = [];
            }
            $_SESSION[$key][] = $value;
        }
        return true;
    }

    /**
     * Indique si la variable est présente en session
     * @param  [type]  $key nom de la variable
     */
    public static function has($key){
        return isset($_SESSION[$key]);
    }

    /**
     * Retire une variable de la session
     * @param  [type] $key nom de la variable
     */
    public static function forget($key){
        $_SESSION[$key] = null;
        unset($_SESSION[$key]);
        return true;
    }

    /**
     * Vide la session
     */
    public static function flush(){
        $_SESSION = [];
        return true;
    }

}

/* End of file */