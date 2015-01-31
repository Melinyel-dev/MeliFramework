<?php

namespace System\Core;

use System\Helpers\Profiler;
use System\Helpers\Cache;

/**
* Dispatcher
* Permet de charger le controller en fonction de la requête utilisateur
**/
class Dispatcher{

	private $request;

	/**
	* Fonction principale du dispatcher
	* Charge le controller en fonction du routing
	**/
	public function __construct($startExecution){
		if($GLOBALS['conf']['maintenance'])
			$this->maintenanceMode();
		$startExecution *= 1000;
		Hook::load('pre_system');

		// Chargement de l'objet Request qui contient les paramètres d'appel
		$this->request = Request::getInstance();

		Profiler::sys_mark('router');
		// Vérification que le contrôlleur appelé est défini dans les routes et existe
		if(Router::parse()){
			Profiler::sys_mark('router');
			Hook::load('pre_controller');

			// Chargement du contrôlleur
			$controller = $this->loadController();
			//$this->loadHelper();

			// Vérification que la méthode appelée est défini dans les routes et existe
			if(!$this->methodExists($controller, $this->request->callMethod)){
				$this->error404('Le controller '.$this->request->as.' n\'a pas de méthode '.$this->request->action);
			}
			Profiler::sys_mark('ability');
			$controller->checkAbility();
			Profiler::sys_mark('ability');

			Hook::load('post_controller_constructor');

	    	$startController = microtime(true)*1000;
	    	ob_start();

			// Appel de la méthode du contrôlleur
			$ajaxReturn = call_user_func_array(array($controller,$this->request->action),$this->request->params);
			$debugController = ob_get_clean();
			$startRenderingView = microtime(true)*1000;

			if($this->request->callMethod != 'AJAX'){
				// Rendu de la vue si pas déjà fait dans le contrôlleur
				if(!$controller->viewSet())
					$controller->render($this->request->action, $GLOBALS['conf']['default_format']);
				$controller->renderDisplay();
				if($GLOBALS['conf']['environment'] != 'prod' && $controller->debugMode)
					echo $debugController;

				$controller->displayView();
			}else{
				$controller->ajax->setEnv();
				$controller->ajax->getResult($ajaxReturn);
				$controller->ajax->output();
			}
			$endRenderingView = microtime(true)*1000;
	    	$endController = microtime(true)*1000;

			Hook::load('post_controller');
		}else{
			Profiler::sys_mark('router');
			$this->error404('Le controller appelé n\'existe pas');
		}
		Hook::load('post_system');
		$endExecution = microtime(true)*1000;
		Profiler::rendering(round($endRenderingView - $startRenderingView, 4));
		Profiler::displayProfiler(round($endExecution - $startExecution, 4), round($endController - $startController, 4));
		Cache::quit();
		$controller->database->close();
	}

	/**
	* Permet de générer une page d'erreur en cas de problème au niveau du routing (page inexistante)
	**/
	private function error404($message){
		Controller::getInstance()->e404($message);
	}

	/**
	* Permet de générer une page de maintenance
	**/
	private function maintenanceMode(){
		Controller::getInstance()->eMaintenance();
	}

	/**
	* Permet de charger le controller en fonction de la requête utilisateur
	**/
	private function loadController(){

		/*if(array_key_exists($this->request->as, $this->request->routes)){
			$name = "\Apps\Controllers\\".$this->request->routes[$this->request->as]['name'];
			$file = $this->request->routes[$this->request->as]['file'];
			if(count($this->request->namespaces) > 0){
				$namespaces = $this->request->namespaces;
				$currentNamespace = ucfirst(array_pop($namespaces));
				$this->loadMainController($namespaces, $currentNamespace);
			}
			require $file;
			return $name::getInstance();
		}
		return false;*/


		if(array_key_exists($this->request->as, $this->request->routes)) {
			if(count($this->request->namespaces) > 0){
				$namespaces = $this->request->namespaces;
				$currentNamespace = ucfirst(array_pop($namespaces));
				//$this->loadMainController($namespaces, $currentNamespace);
			}

			$ns = '\Apps\Controllers\\';
			foreach ($this->request->namespaces as $value) {
				$ns .= ucfirst($value).'\\';
			}

			$name = $ns.$this->request->routes[$this->request->as]['name'];

			if(file_exists($file = $this->request->routes[$this->request->as]['file'])){
				require $file;
				return $name::getInstance();
			}
		}
		return false;
	}

	private function loadMainController($namespaces, $namespace){
		$namespaceString = null;
		$controllerString = null;
		if(count($namespaces)){
			$namespaceString = DS.implode(DS, $namespaces);
			$controllerString = array_ucfirst($namespaces);
			$controllerString = implode(DS, $controllerString);
		}
		$file = CONTROLLERS.$namespaceString.DS.strtolower($namespace).DS.$controllerString.$namespace.'Controller.php';
		if(count($namespaces) > 0){
			$currentNamespace = ucfirst(array_pop($namespaces));
			$this->loadMainController($namespaces, $currentNamespace);
		}
		require $file;
	}

	private function methodExists($controller, $method){
		foreach ($this->request->routes[$this->request->as][$method] as $key => $value) {
			if( preg_match($value, $this->request->url, $outputArray) ){
				array_shift($outputArray);
				$this->request->params = $outputArray;
				$this->request->action = strtolower($method).ucfirst($key);
				$controller->format = $this->request->ext;
				if(in_array($this->request->action , array_diff(get_class_methods($controller),get_class_methods('\System\Core\Controller')))){
					return true;
				}
			}
		}
		return false;
	}

	/**
	* Permet de charger le helper correspondant au controller s'il existe
	**/
	private function loadHelper(){
		$name = ucfirst($this->request->controller).'Helper';
		$namespace = null;
        if(!empty($this->request->namespaces)){
            $namespace = DS.implode(DS, $this->request->namespaces);
        }
		if(file_exists($file = HELPERS.$namespace.DS.$name.'.php')){
			require $file;
		}
	}
}

/* End of file */