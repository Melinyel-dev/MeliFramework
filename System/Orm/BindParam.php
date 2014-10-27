<?php

namespace Melidev\System\Orm;

class BindParam{

    public $values = array();
    public $types  = '';

    public function add($type, $value){
        $this->values[] = $value;
        $this->types .= $type;
    }

    public function get(){
        foreach ($this->values as $index => $value){
            $uniq = uniqid(mt_rand());
            ${'var_'.$uniq.$index} = $value;
            $values[] = &${'var_'.$uniq.$index};
        }
        return array_merge(array($this->types), $values);
    }

    public function hasValues(){
        return count($this->values) > 0;
    }
}

/* End of file */