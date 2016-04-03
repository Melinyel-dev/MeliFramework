<?php

namespace Apps\Controllers;

class JsonController extends ApplicationController {

    $this->outputMode = 'json';
    $this->layout = 'none';

    public function getIndex() {

        $map = new \stdClass();
        $map->message = "Hello, World !";

        $this->data['output'] = json_encode($map);

        //Profiler::enable();
    }
}