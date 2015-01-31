<?php

namespace System\Orm;

class ERBindParam {

    public $values = [];
    public $types  = '';

    public function add($type, $value) {
        $this->values[] = $value;
        $this->types .= $type;
    }

    public function get() {
        foreach ($this->values as $index => $value) {
            $uniq                      = uniqid(mt_rand());
            ${'var_' . $uniq . $index} = $value;
            $values[]                  = &${'var_' . $uniq . $index};
        }
        return array_merge(array($this->types), $values);
    }
    
    public function hasValues() {
        return !empty($this->values);
    }

}

/* End of file */