<?php

/**
 *  Constantes du noyau
 */

define('FRAMEWORK', 'MeliFramework');
define('FRAMEWORK_VERSION', '1.0a');



/**
 *  Définitions des constantes des chemins d'accès système
 */

// Répertoire de configuration
define('CFG', ROOT . DS . 'Config');

// Répertoire de l'application
define('APP', ROOT . DS . 'Apps');



// Répertoire des librairies systèmes
define('LIBRARIES', SYS . DS . 'Libs');

// Répertoire de l'ORM
define('ORM', SYS . DS . 'Orm');



// Répertoire des controllers
define('CONTROLLERS', APP . DS . 'Controllers');

// Répertoire des helpers
define('HELPERS', APP . DS . 'Helpers');

// Répertoire des hooks
define('HOOKS', APP . DS . 'Hooks');

// Répertoire des modèles
define('MODELS', APP . DS . 'Models');

// Répertoire des vues
define('VIEWS', APP . DS . 'Views');



// Répertoire des ressources dynamiques
define('DYN', WEBROOT . DS . 'public');

// Répertoire du cache des ressources dynamiques
define('CACHE_DIR', DYN . DS . 'cache');

// Répertoire des ressources CSS
define('CSS_DIR', WEBROOT . DS . 'css');

// Répertoire des ressources JavaScript
define('JS_DIR', WEBROOT . DS . 'js');


/* End of file */