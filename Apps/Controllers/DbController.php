<?php

namespace Apps\Controllers;

use Apps\Models\World;


class DbController extends ApplicationController{

	// Exécussion du controller pour un appel en GET à /welcome/index
	public function getIndex(){
        $this->outputMode = 'json';
        $this->layout = 'none';

		$this->data['world'] = World::find(rand(1,10000))->toJSON();

        //Profiler::enable();
	}
}