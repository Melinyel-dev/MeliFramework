<?php

namespace System\Helpers;


/**
 * ArrayHelper Class
 *
 * Helper pour manipuler les tableaux
 *
 * @author sugatasei
 */

class ArrayHelper {

    /**
     * Applique la fonciton ucfirst à toutes les entrées du tableau
     *
     * @param array array
     */

    public static function ucfirst($array) {
        array_walk($array, function(&$elem) {
            $elem = ucfirst($elem);
        });

        return $array;
    }


    // -------------------------------------------------------------------------

    /**
     * Modifie elem avec ucfirst
     *
     * @param string elem
     */

    public static function ucfirst_elem(&$elem) {
        $elem = ucfirst($elem);
    }
}

/* End of file */