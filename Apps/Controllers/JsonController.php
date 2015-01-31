<?php

namespace Apps\Controllers;

use System\Helpers\Profiler;


class JsonController extends ApplicationController{

	// Exécussion du controller pour un appel en GET à /welcome/index
	public function getIndex(){
        $this->outputMode = 'json';
        $this->layout = 'none';

        $map = new \stdClass();
        $map->message = "Hello, World !";

        $this->data['output'] = json_encode($map);

        //Profiler::enable();
	}
}