<?php

namespace System\Helpers;

class ArrayHelper {
	public static function ucfirst($array){
	    array_walk($array, function(&$elem) {
	    	$elem = ucfirst($elem);
	    });
	    return $array;
	}

	public static function ucfirst_elem(&$elem){
	    $elem = ucfirst($elem);
	}
}

/* End of file */