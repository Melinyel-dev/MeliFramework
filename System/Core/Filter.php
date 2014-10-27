<?php

namespace Melidev\System\Core;

class Filter{
    function doBeforeFilter(){
        $args = func_get_args();
        $arg = [];
        if($args){
            $arg = $args[0];
        }
        $ctrl = Controller::getInstance();
        $calledFunction = $ctrl->request->action;
        if(isset($ctrl->beforeFilter)){
            foreach ($ctrl->beforeFilter as $key) {
                if(!isset($key["name"])){
                    throw new RuntimeException('BeforeFilter: Name must be set', 1);
                }else{
                    if(method_exists($ctrl,$key["name"])){
                        if(isset($key["except"]) && isset($key["only"])){
                            throw new RuntimeException('BeforeFilter: Filter can only run either "except" or "only"', 1);
                        }else{
                            if(isset($key["except"])){
                                if(!empty($key["except"])){
                                    if(!in_array($calledFunction, $key["except"])){
                                        call_user_func_array(array($ctrl,$key["name"]), $arg);
                                    }
                                }else{
                                    call_user_func_array(array($ctrl,$key["name"]), $arg);
                                }
                            }else{
                                if(isset($key["only"])){
                                    if(!empty($key["only"])){
                                        if(in_array($calledFunction, $key["only"])){
                                            call_user_func_array(array($ctrl,$key["name"]), $arg);
                                        }
                                    }else{
                                        call_user_func_array(array($ctrl,$key["name"]), $arg);
                                    }
                                }else{
                                    call_user_func_array(array($ctrl,$key["name"]), $arg);
                                }
                            }
                        }
                    }else{
                        throw new RuntimeException('BeforeFilter: Function "'.$key["name"].'" does not exists', 1);
                    }
                }
            }
        }
    }
}

/* End of file */