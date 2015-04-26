<?php

namespace Apps\Controllers;

use System\Helpers\Profiler;
use System\Core\Request;
use Apps\Models\World;


class QueriesController extends ApplicationController {

    $this->outputMode = 'json';
    $this->layout = 'none';

    public function getIndex(){

        $queries = Request::getParam('queries');
        if (!$queries || !is_int($queries) || $queries < 1) {
            $queries = 1;
        } elseif ($queries > 500) {
            $queries = 500;
        }

        $tab = [];

        for ($n=0; $n<$queries; $n++) {
            $tab[] = World::find(mt_rand(1, 10000))->toRow();
        }

        $this->data['output'] = json_encode($tab);

        //Profiler::enable();
    }
}