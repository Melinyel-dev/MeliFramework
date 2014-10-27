<?php

namespace Melidev\System\Orm;

class EasyRecordException extends \Exception{

    public function __construct($message, $code = 0, Exception $previous = null) {
        switch ($code) {
            case 2:
                $message = 'MassAssignmentSecurity::Error '.$message;
                break;
            case 3:
                $message = 'ReadOnly::Error '.$message;
                break;
        }
        parent::__construct($message, 8, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

/* End of file */