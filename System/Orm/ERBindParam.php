<?php

namespace System\Orm;


/**
 * ERBindParam Class
 *
 * Gère le typage des variables dans les requêtes SQL
 *
 * @author anaeria
 */

class ERBindParam {

    public $types  = '';
    public $values = [];


    /**
     * Ajoute une valeur aux paramètres de requête
     *
     * @param char type
     * @param string value
     */

    public function add($type, $value) {
        $this->values[] = $value;
        $this->types .= $type;
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne les variables stockées
     *
     * @return array
     */

    public function get() {
        foreach ($this->values as $index => $value) {
            $uniq                      = uniqid(mt_rand());
            ${'var_' . $uniq . $index} = $value;
            $values[]                  = &${'var_' . $uniq . $index};
        }
        return array_merge(array($this->types), $values);
    }


    // -------------------------------------------------------------------------

    /**
     * Test si des variables sont présentes
     *
     * @return noolean
     */

    public function hasValues() {
        return !empty($this->values);
    }

}

/* End of file */