<?php

function lastRequest()
{
    $request = $_SESSION['easyrecord_last_request'];

    $str = $_SESSION['easyrecord_last_request'];
    $replace = $_SESSION['easyrecord_last_bind'];
    $str = preg_replace_callback( '/\?/', function ($match) use (&$replace) {
        return array_shift($replace);
    }, $str);
    return $str;
}

function lr()
{
    debug(lastRequest());
}

function quoteIdentifiant($identifiant)
{
    $parts = explode('.', $identifiant);
    $parts = array_map('quoteIdentifiantPart', $parts);
    return implode('.', $parts);
}

function quoteIdentifiantPart($part)
{
    if ($part === '*')
        return $part;
    $quoteCharacter = '`';
    return $quoteCharacter .
           str_replace($quoteCharacter,
                       $quoteCharacter . $quoteCharacter,
                       $part
           ) . $quoteCharacter;
}

function execute($query, $bindParam)
{
    $_SESSION['easyrecord_last_request'] = $query;
    $bindParamAry = [];
    if ($bindParam->hasValues()) {
        $bindParamAry = $bindParam->get();
        array_shift($bindParamAry);
    }
    $_SESSION['easyrecord_last_bind'] = $bindParamAry;
    return $GLOBALS['Database']->query($query, $bindParam);
}