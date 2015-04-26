<?php

namespace System\Core;


/**
 * Router Class
 *
 * Gère le routing des URLs
 *
 * @author anaeria
 */

class Router {

    private static $availableRoutes  = [];
    private static $currentDirectory = NULL;
    private static $root             = NULL;



    /**
     * Fusionne un tableau
     *
     * @param array ary
     */

    private static function arrayMerge($ary) {
        $firstElem = array_shift($ary);
        if (!empty($ary)) {
            return [$firstElem => Router::arrayMerge($ary)];
        }
        return $firstElem;
    }


    // -------------------------------------------------------------------------

    /**
     * Exécute le routing d'un sous-dossier
     *
     * @param string
     * @param callable
     */

    public static function directory() {
        $args = func_get_args();
        Router::$currentDirectory .= DS . $args[0];

        $args[1]();
        Router::$currentDirectory = substr(Router::$currentDirectory, 0, strrpos(Router::$currentDirectory, DS));
    }


    // -------------------------------------------------------------------------

    /**
     * Défini la route racine
     *
     * @param string
     */

    public static function root() {
        $args       = func_get_args();
        $namespaces = explode(DS, trim(Router::$currentDirectory, DS));
        $namespace  = NULL;
        $rootName   = $args[0];

        if (!empty($namespaces[0])) {
            $namespace = implode(DS, $namespaces).DS;
        } else {
            $namespace = DS;
        }

        $ary = explode('#', $rootName);
        if ($ary[1] == 'index') {
            $ary[1] = '';
        } else {
            $ary[1] = DS . $ary[1];
        }

        Router::$root[$namespace] = $ary[0] . $ary[1];
    }


    // -------------------------------------------------------------------------

    /**
     * Défini le routing d'un controller
     *
     * @param string
     * @param array
     */

    public static function resources(){
        $args           = func_get_args();
        $namespaces     = explode(DS, trim(Router::$currentDirectory, DS));
        $namespacesFile = $namespaces;
        $namespace      = NULL;
        $namespaceFile  = NULL;

        if (!empty($namespaces[0])) {
            array_walk($namespacesFile, function(&$array, $key) {
                $array = ucfirst($array);
            });
            $namespace = '/'.implode('/', $namespaces);
            $namespaceFile = '/'.implode('/', $namespacesFile);
        }

        $resourceName = $args[0];
        if (isset($args[1])) {
            $resourceOptions = $args[1];
        }

        $name = ucfirst($resourceName) . 'Controller';
        $file = CONTROLLERS . $namespaceFile . DS . $name . '.php';
        $key  = $resourceName;

        if (isset($resourceOptions) && array_key_exists('as',$resourceOptions)) {
            $key = $resourceOptions['as'];
        }

        $namespaceRegex    = str_replace('/', '[\/]{1}', $namespace);
        $getMethodsBase    = ['index'  => '/\A' . $namespaceRegex . '[\/]{1}' . $key . '[\.]?([a-zA-Z0-9]*)[\/]?$/',
                              'add'    => '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/add[\/]?$/',
                              'show'   => '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)[\.]?([a-zA-Z0-9]*)[\/]?$/',
                              'edit'   => '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/edit[\/]?$/'];
        $postMethodsBase   = ['create' => '/\A' . $namespaceRegex . '[\/]{1}' . $key . '[\/]?$/'];
        $putMethodsBase    = ['update' => '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)[\/]?$/'];
        $deleteMethodsBase = ['delete' => '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)[\/]?$/'];

        $getMethods         = [];
        $postMethods        = [];
        $putMethods         = [];
        $deleteMethods      = [];
        $ajaxMethods        = [];
        $ajaxMethodsOptions = [];

        if (isset($resourceOptions)) {
            if (array_key_exists('only',$resourceOptions)) {
                $getMethodsBase    = (array_intersect_key($getMethodsBase, array_flip($resourceOptions['only'])));
                $postMethodsBase   = (array_intersect_key($postMethodsBase, array_flip($resourceOptions['only'])));
                $putMethodsBase    = (array_intersect_key($putMethodsBase, array_flip($resourceOptions['only'])));
                $deleteMethodsBase = (array_intersect_key($deleteMethodsBase, array_flip($resourceOptions['only'])));
            }
            if (array_key_exists('except',$resourceOptions)) {
                $getMethodsBase    = (array_diff_key($getMethodsBase, array_flip($resourceOptions['except'])));
                $postMethodsBase   = (array_diff_key($postMethodsBase, array_flip($resourceOptions['except'])));
                $putMethodsBase    = (array_diff_key($putMethodsBase, array_flip($resourceOptions['except'])));
                $deleteMethodsBase = (array_diff_key($deleteMethodsBase, array_flip($resourceOptions['except'])));
            }
            if (array_key_exists('as',$resourceOptions)) {
                $key = $resourceOptions['as'];
            }

            $regexParams = function($string){
                $string = '\/?'.str_replace('/', '\/', $string);
                $string = str_replace(':num', '[0-9]', $string);
                $string = str_replace(':any', '[a-zA-Z0-9\-_\+%]', $string);
                return $string;
            };

            if (array_key_exists('member',$resourceOptions)) {
                if (array_key_exists('get',$resourceOptions['member'])) {
                    foreach ($resourceOptions['member']['get'] as $k => $v) {
                        if (is_int($k)) {
                            $getMethods[$v] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/' . $v . '[\/]?$/';
                        } else {
                            $getMethods[$k] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/' . $k . $regexParams($v[0]) . '[\/]?$/';
                        }
                    }
                }
                if (array_key_exists('post',$resourceOptions['member'])) {
                    foreach ($resourceOptions['member']['post'] as $k => $v) {
                        if(is_int($k)) {
                            $postMethods[$v] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/' . $v . '[\/]?$/';
                        } else {
                            $postMethods[$k] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/' . $k . $regexParams($v[0]) . '[\/]?$/';
                        }
                    }
                }
                if (array_key_exists('put',$resourceOptions['member'])) {
                    foreach ($resourceOptions['member']['put'] as $k => $v) {
                        if(is_int($k)) {
                            $putMethods[$v] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/' . $v . '[\/]?$/';
                        } else {
                            $putMethods[$k] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/' . $k . $regexParams($v[0]) . '[\/]?$/';
                        }
                    }
                }
                if (array_key_exists('delete',$resourceOptions['member'])) {
                    foreach ($resourceOptions['member']['delete'] as $k => $v) {
                        if (is_int($k)) {
                            $deleteMethods[$v] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/' . $v . '[\/]?$/';
                        } else {
                            $deleteMethods[$k] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/([0-9]*)[\-]?([a-zA-Z0-9\-_\+%]*)\/' . $k . $regexParams($v[0]) . '[\/]?$/';
                        }
                    }
                }
            }

            if (array_key_exists('collection',$resourceOptions)) {
                if (array_key_exists('get',$resourceOptions['collection'])) {
                    foreach ($resourceOptions['collection']['get'] as $k => $v) {
                        if (is_int($k)) {
                            $getMethods[$v] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/' . $v . '[\/]?$/';
                        } else{
                            $getMethods[$k] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/' . $k . $regexParams($v[0]) . '[\/]?$/';
                        }
                    }
                }
                if (array_key_exists('post',$resourceOptions['collection'])) {
                    foreach ($resourceOptions['collection']['post'] as $k => $v) {
                        if (is_int($k)) {
                            $postMethods[$v] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/' . $v . '[\/]?$/';
                        } else {
                            $postMethods[$k] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/' . $k . $regexParams($v[0]) . '[\/]?$/';
                        }
                    }
                }
                if (array_key_exists('put',$resourceOptions['collection'])) {
                    foreach ($resourceOptions['collection']['put'] as $k => $v) {
                        if (is_int($k)) {
                            $putMethods[$v] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/' . $v . '[\/]?$/';
                        } else {
                            $putMethods[$k] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/' . $k . $regexParams($v[0]) . '[\/]?$/';
                        }
                    }
                }
                if (array_key_exists('delete',$resourceOptions['collection'])) {
                    foreach ($resourceOptions['collection']['delete'] as $k => $v) {
                        if (is_int($k)) {
                            $deleteMethods[$v] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/' . $v . '[\/]?$/';
                        } else {
                            $deleteMethods[$k] = '/\A' . $namespaceRegex . '[\/]{1}' . $key . '\/' . $k . $regexParams($v[0]) . '[\/]?$/';
                        }
                    }
                }
            }
            if (array_key_exists('ajax',$resourceOptions)) {
                foreach ($resourceOptions['ajax'] as $k => $v) {
                    if (is_int($k)) {
                        $ajaxMethods[$v] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/'.$v.'[\/]?$/';
                        $ajaxMethodsOptions[$v] = [];
                    } else {
                        $ajaxMethods[$k] = '/\A'.$namespaceRegex.'[\/]{1}'.$key.'\/'.$k.'[\/]?$/';
                        $ajaxMethodsOptions[$k] = $v;
                    }
                }
            }
        }

        $getMethods    = array_merge($getMethods, $getMethodsBase);
        $postMethods   = array_merge($postMethods, $postMethodsBase);
        $putMethods    = array_merge($putMethods, $putMethodsBase);
        $deleteMethods = array_merge($deleteMethods, $deleteMethodsBase);

        $route = [
                'name'         => $name,
                'file'         => $file,
                'GET'          => $getMethods,
                'POST'         => $postMethods,
                'PUT'          => $putMethods,
                'DELETE'       => $deleteMethods,
                'AJAX'         => $ajaxMethods,
                'AJAX_OPTIONS' => $ajaxMethodsOptions,
                'original_key' => $resourceName
            ];

        if (!empty($namespaces[0])) {
            $ary = Router::arrayMerge($namespaces);
            if (!is_array($ary)) {
                $ary = [$ary => ''];
            }
            Router::addInRoutes($ary, $route, Router::$availableRoutes, $key);
        } else {
            Router::$availableRoutes[$key] = $route;
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute une cascade de routes dans une ressource
     *
     * @param array ary
     * @param string route
     * @param array allRoutes
     * @param string key
     */

    private static function addInRoutes($ary, $route, &$allRoutes, $key) {
        reset($ary);
        $firstKey = key($ary);
        $ary2 = $ary[$firstKey];

        if (is_array($ary2)) {
            if (array_key_exists($firstKey, $allRoutes)) {
                return Router::addInRoutes($ary2, $route, $allRoutes[$firstKey], $key);
            }

            $allRoutes[$firstKey] = [];
            return Router::addInRoutes($ary2, $route, $allRoutes[$firstKey], $key);
        } else {
            if ($ary2) {
                if (array_key_exists($firstKey, $allRoutes)) {
                    return Router::addInRoutes([$ary2 => ''], $route, $allRoutes[$firstKey], $key);
                }
                $allRoutes[$firstKey] = [];
                return Router::addInRoutes([$ary2 => ''], $route, $allRoutes[$firstKey], $key);
            } else {
                if (array_key_exists($firstKey, $allRoutes)) {
                    $allRoutes[$firstKey][$key] = $route;
                } else {
                    $allRoutes[$firstKey][$key] = $route;
                }
            }
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Permet de parser l'URL
     *
     * @return boolean
     */

    public static function parse() {
        $url = Request::getUrl();
        $url = trim($url, '/');

        if (empty($url)) {
            $url = Router::$root[$url . DS];
            Request::setUrl('/' . $url);
        } else {
            $rootPath = str_replace('/', DS, $url) . DS;

            if (array_key_exists($rootPath, Router::$root)) {
                $controllerName = Router::$root[$rootPath];
                Request::setUrl('/' . $url . '/' . $controllerName);
                $url .= '/' . $controllerName;
            }
        }
        Request::setNamespaces([]);
        return Router::parseUrl(explode('/',$url), Router::$availableRoutes);
    }


    // -------------------------------------------------------------------------

    /**
     * Parseur d'URL
     *
     * @param array ary
     * @param array allRoutes
     * @return boolean
     */

    private static function parseUrl($ary, $allRoutes) {
        $firstElem = array_shift($ary);

        $dotpos = mb_strpos($firstElem, '.');
        if ($dotpos) {
            $ext       = substr($firstElem, $dotpos + 1);
            $extDot    = substr($firstElem, $dotpos);
            $firstElem = str_replace($extDot, '', $firstElem);
        } else {
            $ext = $GLOBALS['conf']['default_format'];
        }

        if (array_key_exists($firstElem, $allRoutes)) {
            if (array_key_exists('original_key', $allRoutes[$firstElem])) {
                if (isset($ary[0])) {
                    $dotpos = mb_strpos($ary[0], '.');

                    if ($dotpos) {
                        $ext = substr($ary[0], $dotpos+1);
                    }
                }

                Request::setExt($ext);
                Request::setAs($firstElem);
                Request::setController($allRoutes[$firstElem]['original_key']);
                Request::setAction(isset($ary[0]) ? $ary[0] : 'index');
                Request::setRoutes($allRoutes);

                return TRUE;
            }
            Request::addNamespace($firstElem);
            return Router::parseUrl($ary, $allRoutes[$firstElem]);
        } else {
            if (array_key_exists(trim(Request::getUrl(), '/') . DS, Router::$root)) {
                $params = explode('/',trim(Request::getUrl(), '/'));
                $params[] = Router::$root[trim(Request::getUrl(), '/') . DS];

                Request::setUrl(Request::getUrl() . DS . Router::$root[trim(Request::getUrl(), '/') . DS]);
                Request::setNamespaces([]);

                return Router::parseUrl($params, Router::$availableRoutes);
            }
            return FALSE;
        }
    }
}

/* End of file */