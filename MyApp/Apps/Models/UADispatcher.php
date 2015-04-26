<?php

namespace Apps\Models;

use System\Core\Controller;
use System\Core\UserAgent;

class UADispatcher extends UserAgent {

    public function suffix() {
    	$suffix = '';

    	if($this->isMobile()) {
    		$suffix .= 'Mobile';
    	} elseif($this->isRobot()) {
    		$suffix .= 'Robot';
    	}

    	return $suffix;
    }
}

/* End of file */