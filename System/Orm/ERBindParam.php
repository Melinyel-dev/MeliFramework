<?php

namespace System\Orm;

class ERBindParam {

    public $values = [];
    public $types  = '';

    // -------------------------------------------------------------------------

    /**
     * Add a bind
     * 
     * @param string $type i=integer d=decimal s=string b=blob
     * @param string $value
     */
    public function add($type, $value) {
        $this->values[] = $value;
        $this->types .= $type;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns an array of bind parameters
     * 
     * @return array
     */
    public function get() {
        $data = [$this->types];
        foreach ($this->values as $value) {
            $data[] = $value;
        }

        return $data;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns if a value is set
     *  
     * @return boolean
     */
    public function hasValues() {
        return !empty($this->values);
    }

    // -------------------------------------------------------------------------
}

/* End of file */