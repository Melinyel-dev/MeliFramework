<?php

namespace System\Orm;


/**
 * ERTools Class
 *
 * @author anaeria
 */

class ERTools {

    /**
     * Protège l'identifiant d'un champ
     *
     * @param string identifiant
     * @return string
     */

    public static function quoteIdentifiant($identifiant) {
        $parts = explode('.', $identifiant);
        $parts = array_map('System\Orm\ERTools::quoteIdentifiantPart', $parts);
        return implode('.', $parts);
    }


    // -------------------------------------------------------------------------

    /**
     * Protège l'identifiant d'un champ
     *
     * @param string part
     * @return string
     */

    public static function quoteIdentifiantPart($part) {
        if ($part === '*') {
            return $part;
        }

        $quoteCharacter = '`';
        return $quoteCharacter . str_replace($quoteCharacter, $quoteCharacter . $quoteCharacter, $part) . $quoteCharacter;
    }


    // -------------------------------------------------------------------------

    /**
     * Exécute une requête à la base de données
     *
     * @param string query
     * @param object bindParam
     * @param object
     */

    public static function execute($query, $bindParam) {
        $bindParamAry = [];
        if ($bindParam->hasValues()) {
            $bindParamAry = $bindParam->get();
            array_shift($bindParamAry);
        }
        return ERDB::getInstance()->query($query, $bindParam);
    }
}
