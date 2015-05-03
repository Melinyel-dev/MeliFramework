<?php

/*$hook['post_system'][] = [
                            'class'    => 'ClassName',
                            'function' => 'function_name',
                            'filename' => 'file_name.php'
                            ];*/
/*$hook['post_controller_constructor'][] = [
                            'function' => 'Autolog',
                            'filename' => 'autolog.php'
                            ];*/

/*
pre_system
    Appelé très tôt durant l'exécution du système. Seules le benchmark et les hooks ont été chargés. Pas de routage ou autres appels de processus.

pre_controller
    Appelé juste avant le chargement de vos contrôleurs. Toutes les classes de base, le routage et les vérifications de sécurité ont été activées.

post_controller_constructor
    Appelé immédiatement après le chargement de vos contrôleurs, mais prioritaire sur les appels aux méthodes et before_filters.

before_rendering
    Appelé immédiatement après l'exécution complète de votre contrôleur.

post_controller
    Appelé immédiatement après l'exécution complète de votre contrôleur.

post_system
    Appelé après le rendu final de la page sur le navigateur, à la fin de l'exécution après que les données finalisées soient envoyées au serveur. 
*/

/* End of file */