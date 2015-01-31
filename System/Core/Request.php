<?php

namespace System\Core;

use System\Helpers\Input;

class Request{


	public $url; 	// URL appellé par l'utilisateur
	public $page = 1;
	public $callMethod = 'GET';
	public $namespaces = [];
	public $ext;
	public $as;
	public $controller;
	public $action;
	public $params = [];
	public $routes = [];

	private static $instance;

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

	protected function __construct(){
		$this->url = isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'/';
		$interPos = mb_strpos($this->url, '?');
		if($interPos !== false){
			$this->url = substr($this->url, 0, $interPos);
		}
		$this->defineCallMethod();
		Input::setParams(array_merge($_GET, $_POST));
	}

	private function defineCallMethod(){
        $method = 'GET';
        if(array_key_exists('REQUEST_METHOD', $_SERVER))
          $method = $_SERVER['REQUEST_METHOD'];

        if(!in_array($method, ['GET', 'POST', 'DELETE', 'PUT', 'AJAX']))
            $method = 'GET';

		$this->callMethod = $method;
		return $this;
	}
}

/* End of file */