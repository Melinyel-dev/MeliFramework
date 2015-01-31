<?php

defined('REP_BASE') || define('REP_BASE', (getenv('APPLICATION_REP_BASE') ? getenv('APPLICATION_REP_BASE') : '/home/prod/'));

define('BASE_URL',dirname(dirname($_SERVER['SCRIPT_NAME'])));
define('APP',ROOT.DS.'Apps');
define('CFG',ROOT.DS.'Config');

define('CORE_APP', APP.DS.'Core');
define('CONTROLLERS', APP.DS.'Controllers');
define('HELPERS', APP.DS.'Helpers');
define('HOOKS', APP.DS.'Hooks');
define('LIBRARIES', SYS.DS.'Libs');
define('ORM', SYS.DS.'Orm');
define('MODELS', APP.DS.'Models');
define('MAPPING', MODELS.DS.'Mapping');
define('VIEWS', APP.DS.'Views');
define('DYN',WEBROOT.DS.'public');
define('CACHE_DIR',DYN.DS.'cache');
define('CSS_DIR',WEBROOT.DS.'css');
define('JS_DIR',WEBROOT.DS.'js');

define('SITE_ID', 0);

/*  EOF  */