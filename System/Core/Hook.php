<?php

namespace System\Core;

class Hook {
    protected static $enable = false;
    private static $hooks = [];

    public static function enable() {
        self::$enable = true;
    }

    public static function set($hooks) {
        self::$hooks = $hooks;
    }

    public static function load($trigger) {
        if(self::$enable) {
            if(isset(self::$hooks[$trigger])) {
                foreach (self::$hooks[$trigger] as $hook) {
                    extract($hook);
                    if(is_file($file = HOOKS.DS.$filename)){
                        require $file;
                        if(isset($class)){
                            if(class_exists($class)){
                                $hook_class = new $class();
                                if(method_exists($hook_class, $function)){
                                    call_user_func([$hook_class, $function]);
                                }
                            }
                        }else{
                            if(function_exists($function)){
                              $function();
                            }
                        }
                    }
                }
            }
        }
    }
}

/* End of file */