<?php

namespace Apps\Controllers;

use System\Helpers\Profiler;


class PlaintextController extends ApplicationController {

    $this->outputMode = 'text';
    $this->layout = 'none';

    public function getIndex() {
        //Profiler::enable();
    }
}