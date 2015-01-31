<?php

namespace System\Core;

use System\Helpers\Auth;
use System\Helpers\Input;
use System\Helpers\NoCSRF;

use System\Orm\ERDB;

use Apps\Models\Ability;

class Controller{

	private static $instance;		// Utile à get_instance()

	private $rendered = false;		// Si le rendu a été fait ou pas ?
	private $renderedView;			// Résultat du rendu
	private $cancan;				// Permet de définir les règles d'accès
	private $view = null;
	private $format = null;

	protected $outputMode = 'html';

	public $request;  				// Objet Request
	public $layout;  				// Layout à utiliser pour rendre la vue
	public $data = [];				// Variables à passer à la vue
	public $load;					// Permet de charger des helpers/libraries
	public $InitData;				// Chargement du CSS/JS
	public $whoops;					// Objet Whoops
	public $database;				// Objet Database
	public $template;				// Template par défaut
	public $twig;					// Objet Twig
	public $ajax = null;			// Objet Ajax
	public $debugMode = false;		// Permet d'afficher les echos dans le controleur
	public $AJAX_JSVars;		// Permet d'afficher les echos dans le controleur

	public $readableMethods = [];		// CanCan
    public $createableMethods = [];	// CanCan
    public $updateableMethods = [];	// CanCan
    public $destroyableMethods = [];	// CanCan

    private function __clone(){}
    private function __wakeup(){}

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
    }

	/**
	* Constructeur
	**/
	protected function __construct(){
		$this->request = Request::getInstance();
		$this->whoops = new Whoops();
		$this->template = new Template();

		$this->load = new Loader();

		$this->initializeData();

		$this->database = ERDB::getInstance();

		if($this->request->callMethod == 'AJAX'){
			if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
				$token = array_key_exists('HTTP_X_CSRF_TOKEN', $_SERVER) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : false;
				if (count($this->request->routes[$this->request->as]['AJAX_OPTIONS'][$this->request->action])) {
					if ($token === false)
						$this->e500('HTTP_X_CSRF_TOKEN Missing');

					if(array_key_exists('csrf_key', $this->request->routes[$this->request->as]['AJAX_OPTIONS'][$this->request->action])){
						$keyToken = $this->request->routes[$this->request->as]['AJAX_OPTIONS'][$this->request->action]['csrf_key'];
						$expire = array_key_exists('csrf_expire', $this->request->routes[$this->request->as]['AJAX_OPTIONS'][$this->request->action]) ? $this->request->routes[$this->request->as]['AJAX_OPTIONS'][$this->request->action]['csrf_expire'] : 600;
						$multiple = array_key_exists('csrf_multiple', $this->request->routes[$this->request->as]['AJAX_OPTIONS'][$this->request->action]) ? $this->request->routes[$this->request->as]['AJAX_OPTIONS'][$this->request->action]['csrf_multiple'] : true;

						NoCSRF::check($keyToken, $token, $expire, $multiple);
					}
				}
				$this->ajax = new AJAX($token);
			}else{
				$this->e500('HTTP_X_REQUESTED_WITH Missing');
			}
		}
		$this->data['page'] = Input::get('page', 1);
	}

	public function render($view, $format = null){
		$this->view = $view;
		$this->format = $format;
	}

	public function viewSet(){
		return $this->view !== null;
	}

	/**
	* Permet de rendre une vue
	* @param $ControllerRenderView Fichier à rendre (chemin depuis view ou nom de la vue)
	**/
	public function renderDisplay(){
		if($this->rendered)
			return false;

		$view = $this->view;
		$format = $this->format;

		if(!$this->layout) {
			$this->layout = $GLOBALS['Template']['default_layout'];
		}
		if(!array_key_exists($this->layout, $GLOBALS['Template']) && $this->layout != 'none')
			$this->e500('Template : template ('.$this->layout.') is not defined !');

		Hook::load('before_rendering');

		$GLOBALS['ControllerName'] = $this->request->controller;

		if(!$format){
			$format = $GLOBALS['conf']['default_format'];
		}
		$ext = '.'.$format;

		if(mb_strpos($view,'/')!==false){
			$file = str_replace('/', DS, VIEWS.DS.$view.$ext);
			$folder = substr($file, 0, strrpos($file, DS));
			$view = explode('/', $view);
			$view = array_pop($view).$ext;
		}else{
			$namespace = null;
		    if(!empty($this->request->namespaces)){
	            $namespace = DS.implode('/', $this->request->namespaces);
	        }
        	$folder = VIEWS.$namespace.DS.$this->request->controller;
			$file = VIEWS.$namespace.DS.$this->request->controller.DS.$view.$ext;
			$view = $view.$ext;
		}
		if(file_exists($file)) {
			if($this->layout != 'none')
				$this->renderExecute($file);
			else
				$this->renderNone($file);
		}else{
			$this->e500('Le fichier '.$view.' n\'existe pas.');
		}
	}

	public function displayView(){
		if($this->outputMode == 'html') {
			header('Content-Type: text/html; charset=UTF-8');
		} elseif ($this->outputMode == 'json') {
			header('Content-Type: application/json; charset=UTF-8');
		} elseif($this->outputMode == 'text') {
			header('Content-Type: text/plain; charset=UTF-8');
		}

		echo $this->renderedView;
	}

	public function debug($bool = true){
		$this->debugMode = $bool;
	}
	/**
	* Permet de gérer les erreurs 404
	**/
	public function e404($message = null){
		header("HTTP/1.0 404 Not Found");
		if($GLOBALS['conf']['environment'] == "prod"){
			$this->data = [];
			$this->initializeData();
			$this->template->write('title', 'Page introuvable');
			$this->render('errors/404');
			$this->renderDisplay();
			$this->displayView();
		}else{
			throw new \RuntimeException($message);
		}
		die();
	}

	/**
	* Permet de gérer les erreurs 500
	**/
	public function e500($message = null){
		header("HTTP/1.0 500 Internal Server Error");
		if($GLOBALS['conf']['environment'] == "prod"){
			$this->data = [];
			$this->initializeData();
			$this->template->write('title', 'Erreur interne du serveur');
			$this->render('errors/500');
			$this->renderDisplay();
			$this->displayView();
		}else{
			throw new \RuntimeException($message);
		}
		die();
	}

	/**
	* Permet de gérer les maintenance
	**/
	public function eMaintenance(){
		header("HTTP/1.0 500 Internal Server Error");
		$this->data = [];
		$this->initializeData();
		$this->render('errors/maintenance');
		$this->renderDisplay();
		$this->displayView();
		die();
	}

	private function initializeData(){
		$this->InitData                   = array();
		$this->InitData['Styles']         = array();
		$this->InitData['Scripts']        = array();
		$this->InitData['AJAX_Vars']      = array();
		$this->InitData['ScriptsUrl']     = array();
	}

	public function checkAbility(){
		if(property_exists($this, 'authorize') && $GLOBALS['conf']['cancan']['enabled']) {
			$this->cancan = new Ability(Auth::user());
		}
	}

	public function InitData($elem, $params){
		if($elem == 'AJAX_Init'){
			$this->InitData['AJAX_Init'] = $params;
		}else{
			if(is_array($params))
				$this->InitData[$elem] = array_merge($this->InitData[$elem], $params);
			else
				$this->InitData[$elem][] =  $params;
		}
	}

	protected function renderExecute($ControllerRenderExecuteFile){
		$tabRegions = [];
		foreach ($GLOBALS['Template'][$this->layout]['regions'] as $templateRegion) {
			$tabRegions[$templateRegion] = $this->template->render($templateRegion);
		}
		$this->renderedView = controllerRenderExecute($ControllerRenderExecuteFile, $this->data, $this->layout, $tabRegions);
		$this->rendered = true;
	}

	protected function renderNone($ControllerRenderExecuteFile){
		$this->renderedView = controllerRenderNone($ControllerRenderExecuteFile, $this->data);
		$this->rendered = true;
	}

}

function controllerRenderExecute($controllerRenderExecuteFile, $controllerRenderExecuteData, $controllerRenderExecuteLayout, $controllerRenderExecuteRegions){
	extract($controllerRenderExecuteData);
	ob_start();
	require $controllerRenderExecuteFile;
	${$GLOBALS['Template'][$controllerRenderExecuteLayout]['main_region']} = ob_get_clean();
	extract($controllerRenderExecuteRegions);
	ob_start();
	require VIEWS.DS.'layouts'.DS.$controllerRenderExecuteLayout.'.php';
	return ob_get_clean();
}

function controllerRenderNone($controllerRenderExecuteFile, $controllerRenderExecuteData){
	extract($controllerRenderExecuteData);
	ob_start();
	require $controllerRenderExecuteFile;
	return ob_get_clean();
}

function render_fragment($path, $params = []){
	$ctrl = Controller::getInstance();
	$ctrl->load->fragment($path, $params);
}

/* End of file */