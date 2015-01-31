<?php

namespace Apps\Controllers;

use System\Helpers\Profiler;


class PlaintextController extends ApplicationController{

	// Exécussion du controller pour un appel en GET à /welcome/index
	public function getIndex(){
        $this->outputMode = 'text';
        $this->layout = 'none';

        //Profiler::enable();
	}
}