<?php

namespace System\Core;

class Loader{

    public function helper($helper){
        if(!in_array($helper, $GLOBALS['enabled_helpers'])){
            if(file_exists($file = HELPERS.DS.ucfirst($helper).'Helper.php')){
                require $file;
                $GLOBALS['enabled_helpers'][] = $helper;
            }
        }
    }

    public function library($library){
        if(!in_array($library, $GLOBALS['enabled_libraries'])){
            if (file_exists($file = LIBRARIES.DS.ucfirst($library).'.php')) {
                require $file;
                $GLOBALS['enabled_libraries'][] = $library;
                $className = ucfirst($library);
                $ctrl = Controller::getInstance();
                $ctrl->$library = new $className();
            }
        }
    }

    public function fragment($path, $params = []){
        $rescuePath = null;
        $ignore = false;
        $vars = [];
        if(array_key_exists('rescue_path', $params)){
            $rescuePath = $params['rescue_path'];
        }
        if(array_key_exists('ignore', $params)){
            $ignore = $params['ignore'];
        }
        if(array_key_exists('vars', $params)){
            $vars = $params['vars'];
        }

        $file = $this->getFile($path);

        if(!file_exists($file)){
            if($rescuePath !== null){
                $file = $this->getFile($rescuePath);
            }
            if(!file_exists($file)){
                if(!$ignore){
                    throw new \RuntimeException('Le fichier '.$file.' n\'existe pas');
                }else{
                    return null;
                }
            }
        }
        $ctrl = Controller::getInstance();
        if(empty($vars)){
            extract($ctrl->data);
        }else{
            extract($vars);
        }
        $tabRegions = [];
        foreach ($GLOBALS['Template'][$ctrl->layout]['regions'] as $templateRegion) {
            $tabRegions[$templateRegion] = $ctrl->template->render($templateRegion);
        }
        extract($tabRegions);
        require $file;
    }

    private function getFile($path){
        if (strpos($path, '.') === false) {
            $ext = '.'.$GLOBALS['conf']['default_format'];
        }else{
            $ext = null;
        }

        if(strpos($path,'@') !== false){
            $file = VIEWS.DS.'layouts'.DS.str_replace('@', '', str_replace('/', DS, $path)).$ext;
        }elseif(strpos($path,'/') !== false){
            $path = str_replace('/', DS, $path);
            $file = VIEWS.DS.$path.$ext;
        }else{
            $ctrl = Controller::getInstance();
            $namespace = null;
            if(!empty($ctrl->request->namespaces)){
                $namespace = DS.implode(DS, $ctrl->request->namespaces);
            }
            $file = VIEWS.$namespace.DS.$ctrl->request->controller.DS.$path.$ext;
        }
        return $file;
    }
}

/* End of file */