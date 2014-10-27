<?php

namespace Melidev\System\Helpers;

class Cache{
    private static $localCache = [];

    /**
     * Ajoute une variable dans le cache
     * @param  [type]  $key        nom de la variable
     * @param  [type]  $value      valeur de la variable
     * @param  integer $duration   durée de validité
     * @param  boolean $compressed compression
     */
    public static function put($key, $value, $duration = 1, $compressed = false){
        $key = $GLOBALS['conf']['environment'].$key;
        if(self::available()){
            self::$localCache[$key]['duration'] = time() + $duration*60;
            self::$localCache[$key]['value'] = $value;
            return $GLOBALS['Memcache']->set($key, $value, $compressed, $duration*60) or die ("Echec de la sauvegarde des données sur le serveur Memcache");
        }
        return true;
    }

    /**
     * Incrémente la valeur d'une variable dans le cache
     * @param  [type]  $key   nom de la variable
     * @param  integer $value valeur de la variable
     */
    public static function increment($key, $value = 1){
        $key = $GLOBALS['conf']['environment'].$key;
        if(self::available()){
            return $GLOBALS['Memcache']->increment($key, $value) or die ("Echec de la sauvegarde des données sur le serveur Memcache");
        }
        if(array_key_exists($key, self::$localCache) && self::$localCache[$key]['duration'] >= time()){
            self::$localCache[$key]['value'] = self::$localCache[$key]['value']+$value;
            return true;
        }
        return false;
    }

    /**
     * Décrémente la valeur d'une variable dans le cache
     * @param  [type]  $key   nom de la variable
     * @param  integer $value valeur de la variable
     */
    public static function decrement($key, $value = 1){
        $key = $GLOBALS['conf']['environment'].$key;
        if(self::available()){
            return $GLOBALS['Memcache']->decrement($key, $value) or die ("Echec de la sauvegarde des données sur le serveur Memcache");
        }
        if(array_key_exists($key, self::$localCache) && self::$localCache[$key]['duration'] >= time()){
            self::$localCache[$key]['value'] = self::$localCache[$key]['value']-$value;
            return true;
        }
        return false;
    }

    /**
     * Ajoute une variable dans le cache pour toujours
     * @param  [type]  $key        nom de la variable
     * @param  [type]  $value      valeur de la variable
     * @param  boolean $compressed compression
     */
    public static function forever($key, $value, $compressed = false){
        $key = $GLOBALS['conf']['environment'].$key;
        if(self::available()){
            return $GLOBALS['Memcache']->set($key, $value, $compressed, 0) or die ("Echec de la sauvegarde des données sur le serveur Memcache");
        }
        self::$localCache[$key]['duration'] = time() + 99999999999999999;
        self::$localCache[$key]['value'] = $value;
        return true;
    }

    /**
     * Ajoute une variable dans le cache sans l'écraser si elle est déjà présente
     * @param  [type]  $key        nom de la variable
     * @param  [type]  $value      valeur de la variable
     * @param  integer $duration   durée de validité
     * @param  boolean $compressed compression
     */
    public static function add($key, $value, $duration = 1, $compressed = false){
        $key = $GLOBALS['conf']['environment'].$key;
        if(self::available()){
            if(!array_key_exists($key, self::$localCache)){
                self::$localCache[$key]['duration'] = time() + $duration*60;
                self::$localCache[$key]['value'] = $value;
            }
            return $GLOBALS['Memcache']->add($key, $value, $compressed, $duration*60) or die ("Echec de la sauvegarde des données sur le serveur Memcache");
        }
        return true;
    }

    /**
     * Indique si une variable est présente dans le cache
     * @param  [type]  $key nom de la variable
     */
    public static function has($key){
        $key = $GLOBALS['conf']['environment'].$key;
        if(self::available()){
            return $GLOBALS['Memcache']->get($key) !== false;
        }
        if(!array_key_exists($key, self::$localCache) || self::$localCache[$key]['duration'] < time()){
            return false;
        }
        return true;
    }

    /**
     * Retourne une variable du cache, ou la valeur par défaut si elle n'est pas présente
     * @param  [type] $key           nom de la variable
     * @param  [type] $defaultValue valeur par défaut
     */
    public static function get($key, $defaultValue = null){
        $key = $GLOBALS['conf']['environment'].$key;
        if(array_key_exists($key, self::$localCache)){
            return self::$localCache[$key]['value'];
        }
        if(self::available()){
            return $GLOBALS['Memcache']->get($key);
        }
        return $defaultValue;
    }

    public static function available(){
        return $GLOBALS['Memcache'] ? true : false;
    }

    public static function delete($key){
        $key = $GLOBALS['conf']['environment'].$key;
        if(self::available()){
            if(self::has($key))
                return $GLOBALS['Memcache']->delete($key) or die ("Echec du le serveur Memcache");
            else
                return false;
        }
        if(array_key_exists($key, self::$localCache)){
            unset(self::$localCache[$key]);
            return true;
        }
        return false;
    }
}

if(class_exists('Memcached', false)){
    $GLOBALS['Memcache'] = new \Memcached();

    $GLOBALS['Memcache']->setOptions(array(
            \Memcached::OPT_NO_BLOCK => true,
            \Memcached::OPT_BUFFER_WRITES => true,
            \Memcached::OPT_BINARY_PROTOCOL => true,
            \Memcached::OPT_LIBKETAMA_COMPATIBLE => true,
            \Memcached::OPT_TCP_NODELAY => true
        ));

    $GLOBALS['Memcache']->addServer($GLOBALS['conf']['memcache']['host'], $GLOBALS['conf']['memcache']['port']);

    $status = @$GLOBALS['Memcache']->getVersion();

    if(!$status[$GLOBALS['conf']['memcache']['host'].':'.$GLOBALS['conf']['memcache']['port']]){
        $GLOBALS['Memcache'] = false;
    }
}else{
    $GLOBALS['Memcache'] = false;
}

/* End of file */