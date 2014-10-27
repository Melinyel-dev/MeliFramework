<?php

namespace Melidev\System\Core;

use Melidev\System\Helpers\ArrayHelper;
use Melidev\System\Helpers\Auth;
use Melidev\System\Helpers\Notice;
use Melidev\System\Helpers\Redirect;

class CanCan {

    protected $authorizations = [];

    public $readableMethods =   ['getIndex', 'getShow'];
    public $createableMethods = ['getAdd', 'postCreate'];
    public $updateableMethods = ['getEdit', 'putUpdate'];
    public $destroyableMethods = ['deleteDelete'];

    public function __construct($user){
        $this->authorizations['read'] = [];
        $this->authorizations['create'] = [];
        $this->authorizations['update'] = [];
        $this->authorizations['destroy'] = [];
        $this->authorizations['manage'] = [];

        $this->initialize($user);
        return $this->check();
    }

    protected function initialize($user) {}

    protected function can($right, $object) {
        if (!array_key_exists($right, $this->authorizations)) {
            throw new RuntimeException('CanCan::Ability : "'.$right.'" n\'est pas un droit valide');
        }
        $this->authorizations[$right][] = $object;
    }

    protected function check() {
        $ctrl = Controller::getInstance();

        $this->readableMethods =   array_merge($this->readableMethods, $ctrl->readableMethods);
        $this->createableMethods = array_merge($this->createableMethods, $ctrl->createableMethods);
        $this->updateableMethods = array_merge($this->updateableMethods, $ctrl->updateableMethods);
        $this->destroyableMethods = array_merge($this->destroyableMethods, $ctrl->destroyableMethods);


        $method = $ctrl->request->action;
        $controllerName = ucfirst($ctrl->request->controller);
        $namespace = ArrayHelper::ucfirst($ctrl->request->namespaces);
        $namespace = implode('::', $namespace);
        if($namespace){
            $namespace = $namespace.'::';
        }
        $object = $namespace.$controllerName;
        
        $methodType = null;
        if(in_array($method, $this->readableMethods)){
            $methodType = 'read';
        }elseif (in_array($method, $this->createableMethods)) {
            $methodType = 'create';
        }elseif (in_array($method, $this->updateableMethods)) {
            $methodType = 'update';
        }elseif (in_array($method, $this->destroyableMethods)) {
            $methodType = 'destroy';
        }else{
            throw new RuntimeException('CanCan::Ability : "'.$method.'" n\'est pas catégorisée');
        }

        if (!in_array($object, $this->authorizations[$methodType]) && !in_array($object, $this->authorizations['manage']) && !in_array('all', $this->authorizations[$methodType]) && !in_array('all', $this->authorizations['manage'])) {
            if(Auth::check()){
                Notice::set('Vous n\'êtes pas autorisé à accéder à cette page');
                Redirect::to('/');
            }else{
                Notice::set('Veuillez vous connecter afin d\'accéder à cette page');
                Redirect::toBack($GLOBALS['conf']['cancan']['login_page']);
            }
        }
    }
}

/* End of file */