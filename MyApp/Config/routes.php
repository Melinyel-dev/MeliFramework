<?php

use \System\Core\Router;

// Controller et vue à exécutée pour la racine (http://mon-projet.com/)
Router::root('welcome#index');

// Définition des accès à l'URL : /welcome/
Router::resources('welcome', [
    'only' => ['index']			// Seul l'index est disponible sur les appels REST
]);

Router::resources('json', [
    'only' => ['index']
]);

Router::resources('db', [
    'only' => ['index']
]);

Router::resources('queries', [
    'only' => ['index']
]);

Router::resources('fortunes', [
    'only' => ['index']
]);

Router::resources('updates', [
    'only' => ['index']
]);

Router::resources('plaintext', [
    'only' => ['index']
]);

/* End of file */