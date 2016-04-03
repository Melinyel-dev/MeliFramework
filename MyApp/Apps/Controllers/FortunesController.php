<?php

namespace Apps\Controllers;

use Apps\Models\Fortune;


class FortunesController extends ApplicationController {
    public function getIndex() {
        $datas = Fortune::all();

        $fortune = new Fortune();
        $fortune->id = 0;
        $fortune->message = 'Additional fortune added at request time.';

        $datas[] = $fortune;

        $fortunes = [];
        foreach ($datas as $data) {
            $fortunes[$data->id] = $data->message;
        }

        asort($fortunes);

        $this->data['fortunes'] = $fortunes;

        //Profiler::enable();
    }
}