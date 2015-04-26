<?php

namespace Apps\Controllers;

use Apps\Models\World;


class DbController extends ApplicationController {

    $this->outputMode = 'json';
    $this->layout = 'none';

    public function getIndex() {
        $this->data['world'] = World::find(mt_rand(1, 10000))->toJSON();

        //Profiler::enable();
    }
}