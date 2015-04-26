<?php

namespace Orb\Helpers;

/**
 * Date
 *
 * @author sugatasei
 */
class Date {


    /**
     * Test si une année est bisextille
     *
     * @param int y
     * @return boolean
     */

    public static function isLeapYear($y) {
        return $y % 400 == 0 || ($y % 100 != 0 && $y % 4 == 0);
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne le nombre de jour dans un mois
     *
     * @param int m
     * @param int y
     * @return boolean
     */

    public static function daysInMonth($m, $y) {
        return $m === 2 ? 28 + (int) self::isLeapYear($y) : 31 - ($m - 1) % 7 % 2;
    }

}

/* End of line */