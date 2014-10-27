<?php

function load_hooks($trigger){
    if($GLOBALS['conf']['enable_hooks']){
        foreach ($GLOBALS['hooks'][$trigger] as $hook) {
            extract($hook);
            if(file_exists($file = HOOKS.DS.$filename)){
                require_once $file;
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

/* End of file */