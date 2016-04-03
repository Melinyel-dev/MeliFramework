<?php

namespace System\Core;

use System\Helpers\Profiler;

/**
 * Dispatcher Class
 *
 * Permet de charger le controller en fonction de la requête utilisateur
 *
 * @author anaeria
 **/
class Dispatcher {

    /**
    * Fonction principale du dispatcher
    * Charge le controller en fonction du routing
    *
    * @param float startExcution
    **/
    public function __construct($startExecution){
        if ($GLOBALS['conf']['maintenance']) {
            $this->maintenanceMode();
        }

        $startExecution *= 1000;
        Hook::load('pre_system');

        Profiler::sys_mark('Route parsing');

        // Vérification que le contrôlleur appelé est défini dans les routes et existe
        if (Router::parse()) {
            Profiler::sys_mark('Route parsing');
            Hook::load('pre_controller');

            // Chargement du contrôlleur
            $startController = microtime(true) * 1000;
            $controller = $this->loadController();

            // Vérification que la méthode appelée est défini dans les routes et existe
            if(!$this->methodExists($controller, Request::getMethod())) {
                $this->error404('Le controller '.Request::getAs().' n\'a pas de méthode '.Request::getAction());
            }

            if (extension_loaded ('newrelic')) {
                newrelic_name_transaction(implode('/', Request::getNamespaces()) . '/' . Request::getAs() . '::' . Request::getAction() . ($GLOBALS['conf']['environment'] == 'prod' ? '' : ' - '.$GLOBALS['conf']['environment']));
            }

            Hook::load('pre_ability');

            Profiler::sys_mark('Ability check');
            $controller->checkAbility();
            Profiler::sys_mark('Ability check');

            Hook::load('post_controller_constructor');

            ob_start();

            // Appel de la méthode du contrôlleur
            $ajaxReturn = call_user_func_array(array($controller, Request::getAction()), Request::getParams());
            $controllerOutput = ob_get_clean();
            $startRenderingView = microtime(true) * 1000;

            if (Request::getMethod() != 'AJAX') {
                // Rendu de la vue si pas déjà fait dans le contrôlleur
                if (!$controller->isViewSet()) {
                    $controller->render(Request::getAction(), $GLOBALS['conf']['default_format']);
                }

                $controller->renderDisplay();

                if($GLOBALS['conf']['environment'] != 'prod' && $controller->debugMode) {
                    echo $debugController;
                }

                $controller->displayView();
            } else {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode($ajaxReturn);
            }

            $endRenderingView = microtime(true) * 1000;
            $endController = microtime(true) * 1000;

            Hook::load('post_controller');
        } else {
            Profiler::sys_mark('router');
            $this->error404('Le controller appelé n\'existe pas');
        }

        Hook::load('post_system');
        $endExecution = microtime(true) * 1000;
        Profiler::rendering(round($endRenderingView - $startRenderingView, 4));
        Profiler::displayProfiler(round($endExecution - $startExecution, 4), round($endController - $startController, 4));
    }


    // -------------------------------------------------------------------------

    /**
    * Permet de générer une page d'erreur en cas de problème au niveau du routing (page inexistante)
    *
    * @param string message
    **/
    private function error404($message){
        Controller::getInstance()->e404($message);
    }


    // -------------------------------------------------------------------------

    /**
    * Permet de générer une page de maintenance
    **/
    private function maintenanceMode(){
        Controller::getInstance()->eMaintenance();
    }


    // -------------------------------------------------------------------------

    /**
    * Permet de charger le controller en fonction de la requête utilisateur
    *
    * @return boolean
    **/
    private function loadController(){
        if (array_key_exists(Request::getAs(), Request::getRoutes())) {
            if (count(Request::getNamespaces()) > 0){
                $namespaces = Request::getNamespaces();
                $currentNamespace = ucfirst(array_pop($namespaces));
            }

            $ns = '\Apps\Controllers\\';
            foreach (Request::getNamespaces() as $value) {
                $ns .= ucfirst($value) . '\\';
            }

            $name = $ns . Request::getRoutes()[Request::getAs()]['name'];

            if (is_file($file = Request::getRoutes()[Request::getAs()]['file'])) {
                require $file;
                return $name::getInstance();
            }
        }
        return false;
    }


    // -------------------------------------------------------------------------

    /**
    * Permet de charger charger le controller principal
    *
    * @param array namespaces
    * @param string namespace
    **/

    private function loadMainController($namespaces, $namespace) {
        $namespaceString = null;
        $controllerString = null;

        if (count($namespaces)) {
            $namespaceString = DS . implode(DS, $namespaces);
            $controllerString = array_ucfirst($namespaces);
            $controllerString = implode(DS, $controllerString);
        }

        $file = CONTROLLERS . $namespaceString . DS . strtolower($namespace) . DS . $controllerString . $namespace . 'Controller.php';

        if (count($namespaces) > 0) {
            $currentNamespace = ucfirst(array_pop($namespaces));
            $this->loadMainController($namespaces, $currentNamespace);
        }

        require $file;
    }


    // -------------------------------------------------------------------------

    /**
    * Permet de vérifier l'existance d'une méthode au sein d'un controller
    *
    * @return boolean
    **/

    private function methodExists($controller, $method) {
        foreach (Request::getRoutes()[Request::getAs()][$method] as $key => $value) {
            if (preg_match($value, Request::getUrl(), $outputArray)) {
                array_shift($outputArray);
                $a = 0;
                foreach ($outputArray as $param) {
                    if($param) {
                        Request::setParam('arg_' . $a, $param);
                        $a++;
                    }
                }
                Request::forgetParam('q_prm');
                Request::setAction(strtolower($method).ucfirst($key));
                $controller->format = Request::getExt();

                if (in_array(Request::getAction() , array_diff(get_class_methods($controller), get_class_methods('\System\Core\Controller')))) {
                    return true;
                }
            }
        }
        return false;
    }
}

/* End of file */