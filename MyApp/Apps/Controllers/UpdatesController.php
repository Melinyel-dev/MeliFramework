<?php

namespace Apps\Controllers;

use System\Core\Request;
use Apps\Models\World;

class UpdatesController extends ApplicationController {

    $this->outputMode = 'json';
    $this->layout = 'none';

    public function getIndex() {

        $queries = Request::getParam('queries');
        if (!$queries || !is_numeric($queries) || $queries < 1) {
            $queries = 1;
        } elseif ($queries > 500) {
            $queries = 500;
        }

        $tab = [];

        for ($n=0; $n<$queries; $n++) {
            $world = World::find(mt_rand(1, 10000));
            $world->randomNumber = mt_rand(1, 1000000);
            $world->save();
            $tab[] = $world->toRow();
        }

        $this->data['output'] = json_encode($tab);
    }
}