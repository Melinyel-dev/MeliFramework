<?php

namespace System\Core;

use System\Helpers\ArrayHelper;
use System\Helpers\Auth;
use System\Helpers\Notice;
use System\Helpers\Redirect;

/**
 * Cancan Class
 *
 * Gère les accès des utilisateurs authentifiés aux ressources
 *
 * @author anaeria
 */

class CanCan {

    protected $authorizations  = [];

    public $readableMethods    = ['getIndex', 'getShow'];
    public $createableMethods  = ['getAdd', 'postCreate'];
    public $updateableMethods  = ['getEdit', 'putUpdate'];
    public $destroyableMethods = ['deleteDelete'];



    /**
     * Constructeur
     *
     * @param object user
     */

    public function __construct($user){
        $this->authorizations['read']    = [];
        $this->authorizations['create']  = [];
        $this->authorizations['update']  = [];
        $this->authorizations['destroy'] = [];
        $this->authorizations['manage']  = [];

        $this->initialize($user);
        return $this->check();
    }


    // -------------------------------------------------------------------------

    /**
     * Méthode d'initialisation, est ammenée à être surchargée dans le modèle Apps\Models\Ability
     *
     * @param object user
     */

    protected function initialize($user) {}


    // -------------------------------------------------------------------------

    /**
     * Affecte un nivveau de droit sur une ressource
     *
     * @param string right (read | create | update | destroy | manage)
     * @param string object : Namespace::Controller
     */

    protected function can($right, $object) {
        if (!array_key_exists($right, $this->authorizations)) {
            throw new \RuntimeException('CanCan::Ability : "' . $right . '" n\'est pas un droit valide');
        }
        $this->authorizations[$right][] = $object;
    }


    // -------------------------------------------------------------------------

    /**
     * Vérifie la capacité d'un utilisateur authentifié à accéder à une ressource
     */

    protected function check() {
        $ctrl = Controller::getInstance();

        // Initialisation des tableau de droits
        $this->readableMethods    = array_merge($this->readableMethods, $ctrl->readableMethods);
        $this->createableMethods  = array_merge($this->createableMethods, $ctrl->createableMethods);
        $this->updateableMethods  = array_merge($this->updateableMethods, $ctrl->updateableMethods);
        $this->destroyableMethods = array_merge($this->destroyableMethods, $ctrl->destroyableMethods);

        $method         = $ctrl->request->action;
        $controllerName = ucfirst($ctrl->request->controller);
        $namespace      = ArrayHelper::ucfirst($ctrl->request->namespaces);
        $namespace      = implode('::', $namespace);

        if ($namespace) {
            $namespace = $namespace . '::';
        }

        $object = $namespace . $controllerName;

        // Détermine quel niveau de droit vérifier
        $methodType = NULL;
        if (in_array($method, $this->readableMethods)) {
            $methodType = 'read';
        } elseif (in_array($method, $this->createableMethods)) {
            $methodType = 'create';
        } elseif (in_array($method, $this->updateableMethods)) {
            $methodType = 'update';
        } elseif (in_array($method, $this->destroyableMethods)) {
            $methodType = 'destroy';
        } else {
            throw new \RuntimeException('CanCan::Ability : "' . $method . '" n\'est pas catégorisée');
        }

        // Test du niveau de droit
        if (!in_array($object, $this->authorizations[$methodType]) && !in_array($object, $this->authorizations['manage']) && !in_array('all', $this->authorizations[$methodType]) && !in_array('all', $this->authorizations['manage'])) {
            if (Auth::check()) {
                Notice::set('Vous n\'êtes pas autorisé à accéder à cette page');
                Redirect::to('/');
            } else {
                Notice::set('Veuillez vous connecter afin d\'accéder à cette page');
                Redirect::toBack($GLOBALS['conf']['cancan']['login_page']);
            }
        }
    }
}

/* End of file */