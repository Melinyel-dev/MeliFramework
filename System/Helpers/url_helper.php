<?php if ( ! defined('WEBROOT')) exit('No direct script access allowed');

function base_url($url = null) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    if($url){
        $url = '/'.trim($url, '/');
    }
    return $protocol.$_SERVER['HTTP_HOST'].$url;
}

function current_url() {
    $ctrl = Controller::getInstance();
    return base_url(uri_string());
}

function uri_string() {
    $request = Request::getInstance();
    return $request->url;
}

/* End of file */