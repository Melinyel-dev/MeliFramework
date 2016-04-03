<?php

namespace System\Helpers;


/**
 * ArrayHelper Class
 *
 * Helper pour manipuler les tableaux
 *
 * @author sugatasei
 */

class Bench {

    private static $marks = [];


    /**
     * Ajoute un marqueur de temps
     *
     * @param string label
     */

    public static function mark($label) {
        self::$marks[] = [$label, microtime(true)];
    }


    // -------------------------------------------------------------------------

    /**
     * Retourne un tableau de tous les marqueurs de temps
     *
     * @param int unit
     * @return array
     */

    public static function times($unit = 1) {

        $times = [];
        $prev  = null;
        $first = null;
        foreach (self::$marks as $mark) {

            $label = $mark[0];
            $time  = $mark[1] * $unit;

            $firstTime = $first === null;

            $times[$label] = [
                'time' => $firstTime ? 0 : $time - $first,
                'prev' => $firstTime ? 0 : $time - $prev
            ];

            if ($firstTime) {
                $first = $time;
            }

            $prev = $time;
        }

        return $times;
    }
}
