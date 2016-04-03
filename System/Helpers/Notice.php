<?php

namespace System\Helpers;

/**
 * Notice Class
 *
 * @author anaeria
 */

class Notice {

    /**
     * Ajoute une notice
     *
     * @param string value
     */

    public static function set($value) {
        Session::put('flash_notice_orm', $value);
    }


    // -------------------------------------------------------------------------

    /**
     * Récupère les notices
     *
     * @return string
     */

    public static function get() {
        return array_key_exists('flash_notice_orm', $GLOBALS) ? $GLOBALS['flash_notice_orm'] : false;
    }
}

/* End of file */