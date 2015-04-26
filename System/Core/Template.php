<?php

namespace System\Core;

/**
 * Template Class
 *
 * Gère les templates des vues
 *
 * @author anaeria
 */

class Template {

    private $views   = [];
    private $strings = [];


    /**
     * Défini les paramètres d'une région d'un template
     *
     * @param string region
     * @param string path
     * @param array templateVars
     */

    public function writeView($region, $templatePath, $templateVars = []) {
        $this->views[$region]['path'] = $templatePath;
        $this->views[$region]['vars'] = $templateVars;
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute une variable string à la région d'un template
     *
     * @param string region
     * @param string string
     */

    public function write($region, $string) {
        $this->strings[$region] = $string;
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne le résultat d'une région d'un template
     *
     * @param string region
     * @return NULL | array | string
     */

    public function render($region) {
        if (array_key_exists($region, $this->views)) {
            return $this->renderView($region);
        } elseif (array_key_exists($region, $this->strings)) {
            return $this->strings[$region];
        }
        return NULL;
    }


    // -------------------------------------------------------------------------

    /**
     * Effectue le rendu d'une région d'un template
     *
     * @param string region
     * @return string
     */

    private function renderView($region) {
        $templatePath = $this->views[$region]['path'];
        extract($this->views[$region]['vars']);

        if (strpos($templatePath, '.') === FALSE) {
            $Template_ext = '.' . $GLOBALS['conf']['default_format'];
        } else {
            $Template_ext = NULL;
        }

        if (strpos($templatePath,'/') !== FALSE) {
            $templatePath = str_replace('/', DS, $templatePath);
            $TemplateFile = VIEWS . DS . $templatePath . $Template_ext;
        } else {
            $Template_ctrl = Controller::getInstance();
            $Template_namespace = NULL;

            if (!empty($Template_ctrl->request->namespaces)) {
                $Template_namespace = DS . implode(DS, $Template_ctrl->request->namespaces);
            }

            $TemplateFile = VIEWS . $Template_namespace . DS . $Template_ctrl->request->controller . DS . $templatePath . $Template_ext;
        }

        if (!is_file($TemplateFile)) {
            throw new \RuntimeException('Le fichier ' . $TemplateFile . ' n\'existe pas');
        }

        ob_start();
        require $TemplateFile;
        return ob_get_clean();
    }
}

/* End of file */