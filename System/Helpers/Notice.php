<?php

namespace Melidev\System\Helpers;

class Notice{
    public static function set($value) {
        Session::put('flash_notice_orm', $value);
        return true;
    }

    public static function get() {
        return array_key_exists('flash_notice_orm', $GLOBALS) ? $GLOBALS['flash_notice_orm'] : false;
    }
}

/* End of file */