<?php

namespace Melidev\Apps\Controllers;

use Melidev\System\Helpers\Profiler;

use Melidev\Apps\Models\Demo;

class WelcomeController extends ApplicationController{

    public $authorize;

    public $readableMethods =   [];
    public $createableMethods = [];
    public $updateableMethods = []; 
    public $destroyableMethods = [];

	// Exécussion du controller pour un appel en GET à /welcome/index
	public function getIndex(){

		$demo = Demo::find(1);

		$this->data['demo'] = $demo;

        Profiler::enable();
	}

	// Exécussion du controller pour un appel en GET à /welcome/custom
	public function getCustom() {
		Profiler::enable();
	}
}

/* End of file */