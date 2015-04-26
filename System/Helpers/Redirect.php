<?php

namespace System\Helpers;

use System\Core\Request;

/**
 * Redirect Class
 *
 * @author anaeria
 */

class Redirect {

    /**
     * Redirige vers une url
     * @param string url
     */

    public static function to($url) {
        header("Location: " . $url);
        exit(0);
    }


    // -------------------------------------------------------------------------

    /**
     * Redirige vers la page précédente
     */

    public static function back() {
        if (Request::getParam('location')) {
            $url_back = Request::getParam('location');
        } else {
            $url_back = $_SERVER['HTTP_REFERER'];
        }

        header("Location: " . $url_back);
        exit(0);
    }


    // -------------------------------------------------------------------------

    /**
     * Redirige vers la page précédente
     * @param string url
     */

    public static function toBack($url) {
        header('Location: ' . $url . '?location=' . urlencode($_SERVER['REQUEST_URI']));
        exit(0);
    }
}

/* End of file */