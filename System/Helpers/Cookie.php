<?php

namespace System\Helpers;

/**
 * Cookie class
 *
 * @author sugatasei
 */
class Cookie {

    /**
     * Default configuration for cookies
     *
     * @var array
     */
    private static $config = [
        'prefix' => '',
        'domain' => '',
        'path'   => '/',
        'secure' => false
    ];

    // -------------------------------------------------------------------------

    /**
     * Set the default configuration
     *
     * @param array $conf
     */
    public static function setConf(array $conf) {
        self::$config = array_merge(self::$config, $conf);
    }

    // -------------------------------------------------------------------------

    /**
     * Returns a default configuration
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public static function getConf($key = null, $default = null) {
        if ($key === null) {
            return self::$config;
        }

        return isset(self::$config[$key]) ? self::$config[$key] : $default;
    }

    // -------------------------------------------------------------------------

    /**
     * Fetch an item from the COOKIE array
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($name, $default = null, array $confCookie = []) {
        $config = array_merge(self::$config, $confCookie);
        $key    = $config['prefix'] . $name;
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
    }

    // -------------------------------------------------------------------------

    /**
     * Create a new cookie
     *
     * @param string $name
     * @param mixed $value
     * @param int|null $expire
     * @param array $confCookie
     */
    public static function set($name, $value, $expire = null, array $confCookie = []) {

        $config = array_merge(self::$config, $confCookie);

        $key = $config['prefix'] . $name;

        $now = time();

        // Delete cookie
        if (!is_numeric($expire)) {
            if (isset($_COOKIE[$key])) {
                unset($_COOKIE[$key]);
            }
            $expire = $now - 86500;
        }
        // Delay
        elseif ($expire < $now) {
            $expire = $expire > 0 ? $now + $expire : 0;
        }

        $domain = $config['domain'];
        $path   = $config['path'];
        $secure = (bool) $config['secure'];

        return setcookie($key, $value, $expire, $path, $domain, $secure);
    }

    // -------------------------------------------------------------------------

    /**
     * Create a new cookie for ever
     *
     * @param string $name
     * @param string $value
     * @param array $confCookie
     */
    public static function forever($name, $value, array $confCookie = []) {
        return self::set($name, $value, 365 * 24 * 3600, $confCookie);
    }

    // -------------------------------------------------------------------------

    /**
     * Delete a cookie
     *
     * This method is a shortcut to the setCookie
     *
     * @param type $name
     * @param array $confCookie
     */
    public static function delete($name, array $confCookie = []) {
        return self::set($name, false, null, $confCookie);
    }

    // -------------------------------------------------------------------------
}

/* End of file */