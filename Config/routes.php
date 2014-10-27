<?php

use \Melidev\System\Core\Router;

/*
	TODO : Le système de routes n'incorpore pas encore les droits et le rewriting
	Une refonte majeur du router est plannifiée
*/

// Controller et vue à exécutée pour la racine (http://mon-projet.com/)
Router::root('welcome#index');

// Définition des accès à l'URL : /welcome/
Router::resources('welcome', [
    'only' => ['index'],			// Seul l'index est disponible sur les appels REST
    'collection' => [
        'get' => ['custom']			// Une méthode personnalisée est définie en appel GET (/welcome/custom/)
    ]
]);


/* End of file */