<?php

namespace System\Orm;

class ERTools {

    public static function quoteIdentifiant($identifiant) {
        $parts = explode('.', $identifiant);
        $parts = array_map('System\Orm\ERTools::quoteIdentifiantPart', $parts);
        return implode('.', $parts);
    }

    public static function quoteIdentifiantPart($part) {
        if ($part === '*') return $part;
        $quoteCharacter = '`';
        return $quoteCharacter .
                str_replace($quoteCharacter, $quoteCharacter . $quoteCharacter, $part
                ) . $quoteCharacter;
    }

    public static function execute($query, $bindParam) {
        $bindParamAry = [];
        if ($bindParam->hasValues()) {
            $bindParamAry = $bindParam->get();
            array_shift($bindParamAry);
        }
        return ERDB::getInstance()->query($query, $bindParam);
    }

}
