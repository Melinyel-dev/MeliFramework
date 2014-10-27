<?php

namespace Melidev\System\Helpers;

class Input{

    private static $params = array();

    /**
     * Récupère une variable de $_REQUEST ou la valeur par défaut si la variable n'est pas présente
     * @param  String $key              nom de la variable
     * @param  boolean $html            [description]
     * @param  boolean $inner           [description]
     * @param  Mixed $defaultValue     valeur par défaut retournée si la variable n'existe pas
     * @return Mixed                    Retourne la valeur de la variable appelée ou la valeur par défaut
     */
    public static function get($key, $defaultValue = null) {
        if(array_key_exists($key, self::$params))
            return self::$params[$key];
        return $defaultValue;
    }

    public static function set($key, $value) {
        self::$params[$key] = $value;
        return true;
    }

    /**
     * Retourne toutes les variables de $_REQUEST passées en paramètres, FALSE pour les manquantes
     */
    public static function only(){
        $args = func_get_args();
        $returnParams = [];
        foreach ($args as $key) {
            if(array_key_exists($key, self::$params)){
                $returnParams[$key] = self::$params[$key];
            }else{
                $returnParams[$key] = false;
            }
        }
        return $returnParams;
    }

    /**
     * Retourne toutes les variables de $_REQUEST sauf celles passées en paramètres
     */
    public static function except(){
        $args = func_get_args();
        $returnParams = [];
        foreach (self::$params as $key => $param) {
            if(!in_array($key, $args)){
                $returnParams[$key] = $param;
            }
        }
        return $returnParams;
    }

    /**
     * Retourne toutes les variables de $_REQUEST
     */
    public static function all(){
        return self::$params;
    }

    /**
     * Indique si une variable est présente dans $_REQUEST
     * @param  [type]  $key nom de la variable
     */
    public static function has($key){
        if(array_key_exists($key, self::$params)){
            return true;
        }
        return false;
    }

    public static function setParams($array){
        self::$params = $array;
    }
}

/* End of file */