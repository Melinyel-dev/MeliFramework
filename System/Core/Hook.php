<?php

namespace System\Core;

/**
 * Hook Class
 *
 * Gère les hooks
 *
 * @author anaeria
 */


class Hook {
    protected static $enable = FALSE;
    private static $hooks    = [];


    /**
     * Active le système de jook
     */

    public static function enable() {
        self::$enable = TRUE;
    }


    // -------------------------------------------------------------------------

    /**
     * Défini le tableau des hooks
     *
     * @param array hooks
     */

    public static function set($hooks) {
        self::$hooks = $hooks;
    }


    // -------------------------------------------------------------------------

    /**
     * Exécute les hooks d'un evènement
     *
     * @param string trigger
     */

    public static function load($trigger) {
        if (self::$enable && isset(self::$hooks[$trigger])) {
            foreach (self::$hooks[$trigger] as $hook) {
                extract($hook);

                if (isset($class)) {
                    if (class_exists($class)) {
                        $hook_class = new $class();

                        if (method_exists($hook_class, $function)) {
                            call_user_func([$hook_class, $function]);
                        }
                    }
                } elseif (is_file($file = HOOKS . DS . $filename)) {
                    require $file;

                     if (function_exists($function)) {
                        $function();
                    }
                }
            }
        }
    }
}

/* End of file */