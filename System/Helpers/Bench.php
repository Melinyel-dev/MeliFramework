<?php

namespace Orb\Date;

class Bench {

    private static $marks = [];

    public static function mark($label) {
        self::$marks[] = [$label, microtime(TRUE)];
    }

    public static function times($unit = 1) {

        $times = [];
        $prev  = NULL;
        $first = NULL;
        foreach (self::$marks as $mark) {

            $label = $mark[0];
            $time  = $mark[1] * $unit;

            $firstTime = $first === NULL;

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
