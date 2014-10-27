<?php

// Bibliothèque de lecture du format YAML
require 'Spyc.php';


/*
 *   Chargement de la configuration
 */

// Configuration générale
$GLOBALS['conf'] = Spyc::YAMLLoad(ROOT.DS.'Config'.DS.'config.yml');

// Application de l'environnement d'éxécution
if(getenv('APPLICATION_ENV') || !$GLOBALS['conf']['environment'])
    $GLOBALS['conf']['environment'] = getenv('APPLICATION_ENV');

// Configuration de la base de données
$GLOBALS['databaseCfg'] = Spyc::YAMLLoad(ROOT.DS.'Config'.DS.'database.yml')[$GLOBALS['conf']['environment']];

// Application de la gestion des erreurs
switch ($GLOBALS['conf']['environment']){
    // Les modes 'dev' et 'testing' affichent les erreurs
    case 'dev':
    case 'testing':
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        break;
    // Le mode production masque les erreurs
    case 'prod':
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
    if(strpos($class, 'Whoops') === 0)
        return;

    $file = PR_ROOT.DS.str_replace("\\", DS, $class).'.php';
    if(is_file($file)) {
        require $file;
    } else {
        echo '<pre>';
        debug_print_backtrace();
        var_dump($file);
        echo '</pre>';
        die;
    }
});

session_start();


// Chargement du noyau
require 'Config.loader.php';
require 'ORB.php';
require 'Whoops.php';
require ORM.DS.'functions.php';





/* EOF */