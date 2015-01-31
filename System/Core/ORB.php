<?php

function load_hooks($trigger){
    if($GLOBALS['conf']['enable_hooks']){
        foreach ($GLOBALS['hooks'][$trigger] as $hook) {
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

/* End of file */