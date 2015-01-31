<?php

namespace Apps\Controllers;

use System\Helpers\Profiler;
use Apps\Models\Fortune;

use System\Orm\ERDB;

class FortunesController extends ApplicationController{

	// Exécussion du controller pour un appel en GET à /welcome/index
	public function getIndex(){

		/*$res = ERDB::getInstance()->query('UPDATE melidev.fortunes SET message = "フレームワークのベンチマーク" WHERE id=12');
		if($res) {
			echo ERDB::getInstance()->db->error;
		}*/

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