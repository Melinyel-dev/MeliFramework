<?php

namespace Orb\Helpers;

/**
 * Date
 *
 * @author mathieu
 */
class Date {

    public static function isLeapYear($y) {
        return $y % 400 == 0 || ($y % 100 != 0 && $y % 4 == 0);
    }

    public static function daysInMonth($m, $y) {
        return $m === 2 ? 28 + (int) self::isLeapYear($y) : 31 - ($m - 1) % 7 % 2;
    }

}

/* End of line */