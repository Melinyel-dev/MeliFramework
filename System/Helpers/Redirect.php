<?php

namespace Melidev\System\Helpers;

class Redirect{

    /**
     * Redirige vers une url
     * @param  [type] $url url
     */

    public static function to($url){
        header("Location: ".$url);
        exit(0);
    }

    /**
     * Redirige vers la page précédente
     * @param  [type] $url url
     */

    public static function back(){
        if(Input::get('location')){
            $url_back = Input::get('location');
        }else{
            $url_back = $_SERVER['HTTP_REFERER'];
        }
        header("Location: ".$url_back);
        exit(0);
    }

    /**
     * Redirige vers la page précédente
     * @param  [type] $url url
     */

    public static function toBack($url){
        header('Location: '.$url.'?location='.urlencode($_SERVER['REQUEST_URI']));
        exit(0);
    }
}

/* End of file */