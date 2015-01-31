<?php

use System\Orm\ERDB;

function lastRequest()
{
    return ERDB::getInstance()->lastQuery();
}

function lr()
{
    debug(lastRequest());
}