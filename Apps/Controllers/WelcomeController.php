<?php

namespace Apps\Controllers;

use System\Helpers\Profiler;

class WelcomeController extends ApplicationController{

    public $authorize;

    public $readableMethods =   ['getJson'];
    public $createableMethods = [];
    public $updateableMethods = []; 
    public $destroyableMethods = [];

	// Exécussion du controller pour un appel en GET à /welcome/index
	public function getIndex(){

		//$demo = Demo::find(1);

		//$this->data['demo'] = $demo;

        Profiler::enable();
	}

	// Exécussion du controller pour un appel en GET à /welcome/custom
	public function getJson() {
		$this->layout = 'none';
	}
}

/* End of file */