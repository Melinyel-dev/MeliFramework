<?php

namespace Apps\Models;

use System\Core\CanCan;

class Ability extends CanCan {

    protected function initialize($user) {

        // Défini ce que les utilisateurs non connectés on le droits d'éxécuter
        $this->can('manage', 'Welcome');    // Accès total au controller Welcome

        if($user) {
            // Défini ce que les utilisateurs connectés on le droits d'éxécuter

        }
    }
}

/* End of file */