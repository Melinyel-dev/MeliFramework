<?php

use \Melidev\System\Core\Dispatcher;

// Constantes de base
define('WEBROOT',dirname(__FILE__));		// Accès au dossier /webroot : Racine du virtual host
define('ROOT',dirname(WEBROOT));			// Accès à la racine du framework
define('PR_ROOT',dirname(ROOT));			// Accès à la racine du projet
define('DS',DIRECTORY_SEPARATOR);			// Séparateur de dossier (compatibilité des environnements Linux et Windows)
define('SYS',ROOT.DS.'System');				// Accès au dossier /system : Tous les prérequis du projet
define('CORE',SYS.DS.'Core');				// Accès au dossier /system/core : Classes requisent

// Inclusion du projet
require CORE.DS.'include.php';

// Initialisation du Router
new Dispatcher();


/* EOF */