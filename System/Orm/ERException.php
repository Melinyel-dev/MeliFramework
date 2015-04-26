<?php

namespace System\Orm;

use Exception;

/**
 * ERException Class
 *
 * @author anaeria
 */

class ERException extends Exception {

    /**
     * Constructeur
     *
     * @param string message
     * @param int code
     * @param object previoius
     */

    public function __construct($message, $code = 0, Exception $previous = NULL) {
        switch ($code) {
            case 2:
                $message = 'MassAssignmentSecurity::Error '.$message;
                break;
            case 3:
                $message = 'ReadOnly::Error '.$message;
                break;
        }
        parent::__construct($message, 0, $previous);
    }


    // -------------------------------------------------------------------------

    /**
     * Affiche une exeption
     *
     * @return string
     */

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

/* End of file */