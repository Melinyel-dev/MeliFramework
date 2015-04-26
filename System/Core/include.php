<?php

use System\Core\Hook;

use System\Helpers\Session;


/*
 *   Chargement de la configuration
 */

// Configuration générale
$GLOBALS['conf'] = [];

require ROOT . DS . 'Config' . DS . 'config.php';

$GLOBALS['conf'] = $config;



// Application de l'environnement d'éxécution
if(getenv('APPLICATION_ENV') || !$GLOBALS['conf']['environment']) {
    $GLOBALS['conf']['environment'] = getenv('APPLICATION_ENV');
}



// Configuration de la base de données
$GLOBALS['databaseCfg'] = [];

require ROOT . DS . 'Config' . DS . 'database.php';

$GLOBALS['databaseCfg'] = $config[$GLOBALS['conf']['environment']];



// Application de la gestion des erreurs
switch ($GLOBALS['conf']['environment']) {
    // Les modes 'dev' et 'testing' affichent les erreurs
    case 'dev':
    case 'testing':
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        break;
    // Le mode production masque les erreurs
    case 'prod':
        /*error_reporting(E_ALL);
        ini_set('display_errors', 1);*/
        error_reporting(0);
        ini_set('display_errors', 0);
        break;
    default:
        exit('The application environment is not set correctly.');
}


/*
 *   Inclusion des ressources
 */

// Chargement des constantes
require 'Constants.php';


// Auto-loader
spl_autoload_register(function($class) {
    $file = PR_ROOT . DS . str_replace(['Apps', "\\"], [$GLOBALS['conf']['app_name'] . DS . 'Apps', DS], $class) . '.php';

    if (is_file($file)) {
        require $file;
    } else {
        header('HTTP/1.1 500 Internal Server Error');

        if ($GLOBALS['conf']['environment'] == 'prod') {
            echo 'Oops... Something went really wrong. Please try again later !';
        } else {
            echo '<em>Init Error</em> : Unable to load class file <strong>' . $class . '</strong><br />';
            echo '<em>File location</em> : <strong>' . $file . '</strong><br /><em>Trace</em> :<br />';
            echo '<pre>';
            debug_print_backtrace();
            echo '</pre>';
        }
        die;
    }
});


// Initialisation de la session utilisateur
Session::start();


// Chargement de la configuration du noyau
require CFG . DS . 'constants.php';
require CFG . DS . 'globals.php';
require CFG . DS . 'routes.php';
require CFG . DS . 'template.php';

// Initialisation des templates
$GLOBALS['Template'] = $template;

if ($GLOBALS['conf']['enable_hooks']) {
    Hook::enable();

    // Initialisation des hooks
    require CFG.DS.'hooks.php';

    $allHooks['pre_system']                  = [];
    $allHooks['pre_controller']              = [];
    $allHooks['post_controller_constructor'] = [];
    $allHooks['post_controller']             = [];
    $allHooks['post_system']                 = [];
    $allHooks['before_rendering']            = [];

    if (isset($hook)) {
        $allHooks = array_merge_recursive($hook,$allHooks);
    }

    Hook::set($allHooks);
}


// Connexion à la base de données
$database = \System\Orm\ERDB::connect('default', $GLOBALS['databaseCfg']['host'], $GLOBALS['databaseCfg']['login'], $GLOBALS['databaseCfg']['password'], $GLOBALS['databaseCfg']['database'], $GLOBALS['databaseCfg']['port'], $GLOBALS['databaseCfg']['socket']);


// Connecion au serveur Memcache si activé
if(isset($GLOBALS['conf']['memcache'])) {
    \System\Orm\ERCache::getInstance()->connect($GLOBALS['conf']['memcache']['host'], $GLOBALS['conf']['memcache']['port']);
}

/* End of file */