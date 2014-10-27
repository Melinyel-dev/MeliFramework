<?php

namespace Melidev\System\Core;

class Router{

    private static $root = null;
    private static $availableRoutes = array();
    private static $currentDirectory = null;

    private static function arrayMerge($ary){
        $firstElem = array_shift($ary);
        if(!empty($ary)){
            return array($firstElem => Router::arrayMerge($ary));
        }
        return $firstElem;
    }

    public static function directory(){
        $args = func_get_args();
        Router::$currentDirectory .= DS.$args[0];

        $args[1]();
        Router::$currentDirectory = substr(Router::$currentDirectory, 0, strrpos(Router::$currentDirectory, DS));
    }

    public static function root(){
        $args = func_get_args();
        $namespaces = explode(DS,trim(Router::$currentDirectory,DS));
        $namespace = null;
        $rootName = $args[0];

        if(!empty($namespaces[0])){
            $namespace = implode(DS, $namespaces).DS;
        }else{
            $namespace = DS;
        }

        $ary = explode('#', $rootName);
        if($ary[1] == 'index'){
            $ary[1] = '';
        }else{
            $ary[1] = DS.$ary[1];
        }

        Router::$root[$namespace] = $ary[0].$ary[1];
    }

    public static function resources(){
        $args = func_get_args();
        $namespaces = explode(DS,trim(Router::$currentDirectory,DS));
        $namespace = null;

        if(!empty($namespaces[0])){
            $namespace = '/'.implode('/', $namespaces);
        }

        $resourceName = $args[0];
        if(isset($args[1])){
            $resourceOptions = $args[1];
        }

        $name = ucfirst($resourceName).'Controller';
        $file = CONTROLLERS.$namespace.DS.$name.'.php';
        $key = $resourceName;
        if(isset($resourceOptions)){
            if(array_key_exists('as',$resourceOptions)){
                $key = $resourceOptions['as'];
            }
        }
        $namespaceRegex = str_replace('/', '[\/]{1}', $namespace);
        $getMethodsBase = ['index' => '/\A'.$namespaceRegex.'[\/]{1}'.$key.'[\.]?([a-zA-Z0-9]*)[\/]?$/','add' => '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/add[\/]?$/','show' => '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)[\.]?([a-zA-Z0-9]*)[\/]?$/','edit' => '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/edit[\/]?$/'];
        $postMethodsBase = ['create' => '/\A'.$namespaceRegex.'[\/]{1}'.$key.'[\/]?$/'];
        $putMethodsBase = ['update' => '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)[\/]?$/'];
        $deleteMethodsBase = ['delete' => '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)[\/]?$/'];

        $getMethods = [];
        $postMethods = [];
        $putMethods = [];
        $deleteMethods = [];
        $ajaxMethods = [];
        $ajaxMethodsOptions = [];

        if(isset($resourceOptions)){
            if(array_key_exists('only',$resourceOptions)){
                $getMethodsBase = (array_intersect_key($getMethodsBase, array_flip($resourceOptions['only'])));
                $postMethodsBase = (array_intersect_key($postMethodsBase, array_flip($resourceOptions['only'])));
                $putMethodsBase = (array_intersect_key($putMethodsBase, array_flip($resourceOptions['only'])));
                $deleteMethodsBase = (array_intersect_key($deleteMethodsBase, array_flip($resourceOptions['only'])));
            }
            if(array_key_exists('except',$resourceOptions)){
                $getMethodsBase = (array_diff_key($getMethodsBase, array_flip($resourceOptions['except'])));
                $postMethodsBase = (array_diff_key($postMethodsBase, array_flip($resourceOptions['except'])));
                $putMethodsBase = (array_diff_key($putMethodsBase, array_flip($resourceOptions['except'])));
                $deleteMethodsBase = (array_diff_key($deleteMethodsBase, array_flip($resourceOptions['except'])));
            }
            if(array_key_exists('as',$resourceOptions)){
                $key = $resourceOptions['as'];
            }
            $regexParams = function($string){
                $string = '\/?'.str_replace('/', '\/', $string);
                $string = str_replace(':num', '[0-9]', $string);
                $string = str_replace(':any', '[a-zA-Z0-9\-_\+%]', $string);
                return $string;
            };
            if(array_key_exists('member',$resourceOptions)){
                if(array_key_exists('get',$resourceOptions['member'])){
                    foreach ($resourceOptions['member']['get'] as $k => $v) {
                        if(is_int($k)){
                            $getMethods[$v] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/'.$v.'[\/]?$/';
                        }else{
                            $getMethods[$k] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/'.$k.$regexParams($v[0]).'[\/]?$/';
                        }
                    }
                }
                if(array_key_exists('post',$resourceOptions['member'])){
                    foreach ($resourceOptions['member']['post'] as $k => $v) {
                        if(is_int($k)){
                            $postMethods[$v] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/'.$v.'[\/]?$/';
                        }else{
                            $postMethods[$k] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/'.$k.$regexParams($v[0]).'[\/]?$/';
                        }
                    }
                }
                if(array_key_exists('put',$resourceOptions['member'])){
                    foreach ($resourceOptions['member']['put'] as $k => $v) {
                        if(is_int($k)){
                            $putMethods[$v] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/'.$v.'[\/]?$/';
                        }else{
                            $putMethods[$k] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/'.$k.$regexParams($v[0]).'[\/]?$/';
                        }
                    }
                }
                if(array_key_exists('delete',$resourceOptions['member'])){
                    foreach ($resourceOptions['member']['delete'] as $k => $v) {
                        if(is_int($k)){
                            $deleteMethods[$v] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/'.$v.'[\/]?$/';
                        }else{
                            $deleteMethods[$k] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/'.$k.$regexParams($v[0]).'[\/]?$/';
                        }
                    }
                }
            }

            if(array_key_exists('collection',$resourceOptions)){
                if(array_key_exists('get',$resourceOptions['collection'])){
                    foreach ($resourceOptions['collection']['get'] as $k => $v) {
                        if(is_int($k)){
                            $getMethods[$v] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/'.$v.'[\/]?$/';
                        }else{
                            $getMethods[$k] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/'.$k.$regexParams($v[0]).'[\/]?$/';
                        }
                    }
                }
                if(array_key_exists('post',$resourceOptions['collection'])){
                    foreach ($resourceOptions['collection']['post'] as $k => $v) {
                        if(is_int($k)){
                            $postMethods[$v] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/'.$v.'[\/]?$/';
                        }else{
                            $postMethods[$k] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/'.$k.$regexParams($v[0]).'[\/]?$/';
                        }
                    }
                }
                if(array_key_exists('put',$resourceOptions['collection'])){
                    foreach ($resourceOptions['collection']['put'] as $k => $v) {
                        if(is_int($k)){
                            $putMethods[$v] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/'.$v.'[\/]?$/';
                        }else{
                            $putMethods[$k] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/'.$k.$regexParams($v[0]).'[\/]?$/';
                        }
                    }
                }
                if(array_key_exists('delete',$resourceOptions['collection'])){
                    foreach ($resourceOptions['collection']['delete'] as $k => $v) {
                        if(is_int($k)){
                            $deleteMethods[$v] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/'.$v.'[\/]?$/';
                        }else{
                            $deleteMethods[$k] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/'.$k.$regexParams($v[0]).'[\/]?$/';
                        }
                    }
                }
            }
            if(array_key_exists('ajax',$resourceOptions)){
                foreach ($resourceOptions['ajax'] as $k => $v) {
                    if(is_int($k)){
                        $ajaxMethods[$v] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/'.$v.'[\/]?$/';
                        $ajaxMethodsOptions[$v] = [];
                    }else{
                        $ajaxMethods[$k] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/'.$k.'[\/]?$/';
                        $ajaxMethodsOptions[$k] = $v;
                    }
                }
            }
        }
        $getMethods = array_merge($getMethods, $getMethodsBase);
        $postMethods = array_merge($postMethods, $postMethodsBase);
        $putMethods = array_merge($putMethods, $putMethodsBase);
        $deleteMethods = array_merge($deleteMethods, $deleteMethodsBase);

        $route = array(
                'name' => $name,
                'file' => $file,
                'GET' => $getMethods,
                'POST' => $postMethods,
                'PUT' => $putMethods,
                'DELETE' => $deleteMethods,
                'AJAX' => $ajaxMethods,
                'AJAX_OPTIONS' => $ajaxMethodsOptions,
                'original_key' => $resourceName
            );

        if(!empty($namespaces[0])){
            $ary = Router::arrayMerge($namespaces);
            if(!is_array($ary)){
                $ary = array($ary => '');
            }
            Router::addInRoutes($ary, $route, Router::$availableRoutes, $key);
        }else{
            Router::$availableRoutes[$key] = $route;
        }
    }

    private static function addInRoutes($ary, $route, &$allRoutes, $key){
        reset($ary);
        $firstKey = key($ary);
        $ary2 = $ary[$firstKey];
        if(is_array($ary2)){
            if(array_key_exists($firstKey, $allRoutes)){
                return Router::addInRoutes($ary2, $route, $allRoutes[$firstKey], $key);
            }
            $allRoutes[$firstKey] = array();
            return Router::addInRoutes($ary2, $route, $allRoutes[$firstKey], $key);
        }else{
            if($ary2){
                if(array_key_exists($firstKey, $allRoutes)){
                    return Router::addInRoutes(array($ary2 => ''), $route, $allRoutes[$firstKey], $key);
                }
                $allRoutes[$firstKey] = array();
                return Router::addInRoutes(array($ary2 => ''), $route, $allRoutes[$firstKey], $key);
            }else{
                if(array_key_exists($firstKey, $allRoutes)){
                    $allRoutes[$firstKey][$key] = $route;
                }else{
                    $allRoutes[$firstKey][$key] = $route;
                }
            }
        }
    }

    /**
    * Permet de parser une url
    * @param $url Url à parser
    * @return tableau contenant les paramètres
    **/
    public static function parse(){
        $request = Request::getInstance();
        $url = $request->url;
        $url = trim($url,'/');

        if(empty($url)){
            $url = Router::$root[$url.DS];
            $request->url = '/'.$url;
        }else{
            $rootPath = str_replace('/', DS, $url).DS;
            if(array_key_exists($rootPath, Router::$root)){
                $controllerName = Router::$root[$rootPath];
                $request->url = '/'.$url.'/'.$controllerName;
                $url = $url.'/'.$controllerName;
            }
        }
        $request->namespaces = array();
        return Router::parseUrl(explode('/',$url), Router::$availableRoutes);
    }

    private static function parseUrl($ary, $allRoutes){
        $request = Request::getInstance();
        $firstElem = array_shift($ary);

        $dotpos = mb_strpos($firstElem, '.');
        if($dotpos){
            $ext = substr($firstElem, $dotpos+1);
            $extDot = substr($firstElem, $dotpos);
            $firstElem = str_replace($extDot, '', $firstElem);
        }else{
            $ext = $GLOBALS['conf']['default_format'];
        }
        if(array_key_exists($firstElem, $allRoutes)){
            if(array_key_exists('original_key', $allRoutes[$firstElem])){
                if(isset($ary[0])){
                    $dotpos = mb_strpos($ary[0], '.');
                    if($dotpos){
                        $ext = substr($ary[0], $dotpos+1);
                    }
                }
                $request->ext = $ext;
                $request->as = $firstElem;
                $request->controller = $allRoutes[$firstElem]['original_key'];
                $request->action = isset($ary[0]) ? $ary[0] : 'index';
                $request->routes = $allRoutes;
                return true;
            }
            $request->namespaces[] = $firstElem;
            return Router::parseUrl($ary, $allRoutes[$firstElem]);
        }else{
            if(array_key_exists(trim($request->url,'/').DS, Router::$root)){
                $params = explode('/',trim($request->url,'/'));
                $params[] = Router::$root[trim($request->url,'/').DS];
                $request->url = $request->url.DS.Router::$root[trim($request->url,'/').DS];
                $request->namespaces = array();
                return Router::parseUrl($params, Router::$availableRoutes);
            }
            return false;
        }
    }
}

/* End of file */