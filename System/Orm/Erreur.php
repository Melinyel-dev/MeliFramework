<?php
namespace Melidev\System\Orm;

class Erreur {

    public $attribute;
    public $rule;
    public $message;

    public function __construct($attribute,$rule,$message){
        $this->attribute = $attribute;
        $this->rule      = $rule;
        $this->message   = $message;
    }

    public function __toString(){
        return $this->attribute;
    }
}

/* End of file */