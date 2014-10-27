<?php

namespace Melidev\System\Core;

class Template
{
    private $views = [];
    private $strings = [];

    public function writeView($region, $templatePath, $Template_vars = []){
        $this->views[$region]['path'] = $templatePath;
        $this->views[$region]['vars'] = $Template_vars;
    }

    public function write($region, $string){
        $this->strings[$region] = $string;
    }

    public function render($region){
        if(array_key_exists($region, $this->views)){
            return $this->renderView($region);
        }elseif (array_key_exists($region, $this->strings)) {
            return $this->strings[$region];
        }
        return null;
    }

    private function renderView($region){
        $templatePath = $this->views[$region]['path'];
        extract($this->views[$region]['vars']);

        if (strpos($templatePath, '.') === false) {
            $Template_ext = '.'.$GLOBALS['conf']['default_format'];
        }else{
            $Template_ext = null;
        }

        if(strpos($templatePath,'/') !== false){
            $templatePath = str_replace('/', DS, $templatePath);
            $TemplateFile = VIEWS.DS.$templatePath.$Template_ext;
        }else{
            $Template_ctrl = Controller::getInstance();
            $Template_namespace = null;
            if(!empty($Template_ctrl->request->namespaces)){
                $Template_namespace = DS.implode(DS, $Template_ctrl->request->namespaces);
            }
            $TemplateFile = VIEWS.$Template_namespace.DS.$Template_ctrl->request->controller.DS.$templatePath.$Template_ext;
        }
        if(!file_exists($TemplateFile)){
            throw new RuntimeException('Le fichier '.$TemplateFile.' n\'existe pas');
        }
        ob_start();
        require $TemplateFile;
        return ob_get_clean();
    }
}

/* End of file */