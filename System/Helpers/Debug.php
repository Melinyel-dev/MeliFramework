<?php

namespace System\Helpers;

/**
 * Debug class
 *
 * @author anaeria
 */

class Debug {


    /**
     * Affiche un débuggage complet de la variable si l'environnement n'est pas en production
     *
     * @param mixed var
     */

    public static function full($var){
        if ($GLOBALS['conf']['environment'] != "prod") {
            $debug = debug_backtrace();
            echo '<p>&nbsp;</p><p><a href="#" onclick="$(this).parent().next(\'ol\').slideToggle(); return false;"><strong>' . $debug[0]['file'] . ' </strong> l.' . $debug[0]['line'] . '</a></p>';
            echo '<ol style="display:none;">';

            foreach ($debug as $k => $v) {
                if ($k>0) {
                    if (array_key_exists('file', $v) && array_key_exists('line', $v)) {
                        echo '<li><strong>' . $v['file'] . ' </strong> l.' . $v['line'] . '</li>';
                    }
                }
            }

            echo '</ol>';
            echo '<pre>';
            print_r($var);
            echo '</pre>';
        }
    }

    /**
     * Retourne un débuggage simple de la variable si l'environnement n'est pas en production
     *
     * @param mixed var
     * @return string
     */

    public static function simple($var){
        if ($GLOBALS['conf']['environment'] != "prod") {
            ob_start();
            print_r($var);

            $logs = ob_get_clean();
            $return = '<pre>' . $logs . '</pre>';

            return $return;
        }

        return '';
    }
}
/* End of file */