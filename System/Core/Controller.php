<?php

namespace System\Core;

use Apps\Models\Ability;
use Apps\Models\UADispatcher;

use System\Helpers\Auth;
use System\Core\Request;
use System\Helpers\NoCSRF;
use System\Helpers\Profiler;

use System\Orm\ERDB;


/**
 * Controller Class
 *
 * Controller d'application principal
 *
 * @author anaeria
 */


class Controller {

    const AJAX_ErrorCode        = '##ERROR##';
    const AJAX_SuccessCode      = '##SUCCESS##';
    const AJAX_EndSessionCode   = '##CLOSE##';

    const AJAX_Maintenance      = '##DOWN##';
    const AJAX_ConnexionRequest = '##AUTH##';

    private static $instance;                       // Instance du singleton

    private $rendered               = FALSE;        // Flag de l'état du rendu
    private $renderedView;                          // Résultat du rendu
    private $cancan;                                // Instance de CanCan, gestion des droits d'accès
    private $view;                                  // Nom de la vue courante
    private $format;                                // Format de la vue courante

    protected $outputMode           = 'html';       // Mode d'émission du résultat de la vue (html, text, json, stream, xml)
    protected $externalLibsHandeler = FALSE;        // Flag d'activation du gestionaire des ressources externes
    protected $UADispatcherEnable   = FALSE;        // Flag d'activation du dispatcher par User-Agent
    protected $userAgent;

    public $layout;                                 // Layout courant
    public $data                    = [];           // Tableau des variables accesibles à la vue
    public $Libs;                                   // Chargement du CSS/JS
    public $whoops;                                 // Objet Whoops
    public $template;                               // Template par défaut
    public $debugMode               = FALSE;        // Permet d'afficher les echos dans le controleur

    public $readableMethods         = [];           // Configuration de CanCan : méthodes ayant les droits READ
    public $createableMethods       = [];           // Configuration de CanCan : méthodes ayant les droits CREATE
    public $updateableMethods       = [];           // Configuration de CanCan : méthodes ayant les droits UPDATE
    public $destroyableMethods      = [];           // COnfiguration de CanCan : méthodes ayant les droits DESTROY

    private function __clone(){}
    private function __wakeup(){}



    /**
     * Gestion du singleton
     *
     * @return object singleton
     */
    public static function getInstance() {
        if (NULL === self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
    }


    // -------------------------------------------------------------------------

    /**
    * Constructeur
    **/
    protected function __construct() {
        $this->whoops = new Whoops();
        $this->template = new Template();

        $this->database = ERDB::getInstance();

        if (Request::getMethod() == 'AJAX') {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                $token  = array_key_exists('HTTP_X_CSRF_TOKEN', $_SERVER) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : FALSE;
                $routes = Request::getRoutes();
                $as     = Request::getAs();
                $action = Request::getAction();

                if (count($routes[$as]['AJAX_OPTIONS'][$action])) {
                    if ($token === FALSE) {
                        $this->e500('HTTP_X_CSRF_TOKEN Missing');
                    }

                    if (array_key_exists('csrf_key', $routes[$as]['AJAX_OPTIONS'][$action])) {
                        $keyToken = $routes[$as]['AJAX_OPTIONS'][$action]['csrf_key'];
                        $expire   = array_key_exists('csrf_expire', $routes[$as]['AJAX_OPTIONS'][$action]) ? $routes[$as]['AJAX_OPTIONS'][$action]['csrf_expire'] : 600;
                        $multiple = array_key_exists('csrf_multiple', $routes[$as]['AJAX_OPTIONS'][$action]) ? $routes[$as]['AJAX_OPTIONS'][$action]['csrf_multiple'] : TRUE;

                        NoCSRF::check($keyToken, $token, $expire, $multiple);
                    }
                }
            } else {
                $this->e500('HTTP_X_REQUESTED_WITH Missing');
            }
        }
        $this->data['page'] = Request::getParam('page', 1);

        if ($GLOBALS['conf']['user_agent_dispatcher']) {
            Profiler::sys_mark('UserAgent Detect');
            $this->UADispatcherEnable = TRUE;
            $this->userAgent = UADispatcher::getInstance();
            Profiler::sys_mark('UserAgent Detect');
        }

        $this->externalLibsHandeler = $GLOBALS['conf']['external_libs_handler'];

        if ($this->outputMode == 'html' && $this->externalLibsHandeler) {
            $this->Libs                         = [];
            $this->Libs['CSS']                  = [];
            $this->Libs['JS_Header_Scripts']    = [];
            $this->Libs['JS_Async_Scripts']     = [];
            $this->Libs['JS_Bottom_Scripts']    = [];
            $this->Libs['JS_Vars']              = [];
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Défini la vue à rendre et son format
     *
     * @param string view
     * @param string format
     */

    public function render($view, $format = NULL){ 
        $this->view = $view;
        $this->format = $format;
    }


    // -------------------------------------------------------------------------

    /**
     * Muttateur de debugMode
     */

    public function setDebug($bool = TRUE) {
        $this->debugMode = $bool;
    }

    // -------------------------------------------------------------------------

    /**
     * Test si la vue est définie
     *
     * @return boolean
     */

    public function isViewSet() {
        return $this->view !== NULL;
    }


    // -------------------------------------------------------------------------

    /**
    * Permet de rendre une vue
    *
    * @param $ControllerRenderView Fichier Ã  rendre (chemin depuis view ou nom de la vue)
    **/
    public function renderDisplay() {
        if ($this->rendered) {
            return FALSE;
        }

        $view   = $this->view;
        $format = $this->format;

        if (!$this->layout) {
            $this->layout = $GLOBALS['Template']['default_layout'];
        }

        if ($this->UADispatcherEnable) {
            $originalView = $view;
            $originalLayout = $this->layout;
            Profiler::sys_mark('UserAgent Dispatch');
            $suffix = $this->userAgent->suffix();
            $view .= $suffix;
            $this->layout .= $suffix;
            Profiler::sys_mark('UserAgent Dispatch');
        }

        if (!array_key_exists($this->layout, $GLOBALS['Template']) && $this->layout != 'none') {
            $this->e500('Template : template (' . $this->layout . ') is not defined !');
        }

        Hook::load('before_rendering');

        $GLOBALS['ControllerName'] = Request::getController();

        if(!$format){
            $format = $GLOBALS['conf']['default_format'];
        }
        $ext = '.' . $format;

        if (mb_strpos($view,'/') !== FALSE) {
            $file   = str_replace('/', DS, VIEWS . DS . $view . $ext);
            $folder = substr($file, 0, strrpos($file, DS));
            $view   = explode('/', $view);
            $view   = array_pop($view) . $ext;
        } else {
            $namespace = NULL;
            if (!empty(Request::getNamespaces())) {
                $namespace = DS . implode('/', Request::getNamespaces());
            }
            $folder = VIEWS . $namespace . DS . Request::getController();
            $file   = VIEWS . $namespace . DS . Request::getController() . DS . $view . $ext;
            $view   = $view . $ext;
        }

        if (is_file($file)) {
            if($this->layout != 'none') {
                $this->renderExecute($file);
            } else {
                $this->renderNone($file);
            }
        } elseif($this->UADispatcherEnable) {
            $this->UADispatcherEnable = FALSE;
            $this->renderDisplay();
        } else {
            $this->e500('Le fichier ' . $view . ' n\'existe pas.');
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Affiche le résultat de la vue
     */

    public function displayView() {
        if ($this->outputMode == 'html') {
            header('Content-Type: text/html; charset=UTF-8');
        } elseif ($this->outputMode == 'json') {
            header('Content-Type: application/json; charset=UTF-8');
        } elseif ($this->outputMode == 'text') {
            header('Content-Type: text/plain; charset=UTF-8');
        } elseif ($this->outputMode == 'stream') {
            header('Content-type: application/octet-stream');
        } elseif ($this->outputMode == 'xml') {
            header('Content-Type: application/xml; charset=utf-8');
        }

        echo $this->renderedView;
    }


    // -------------------------------------------------------------------------

    /**
    * Permet de gérer les erreurs 404
    *
    * @param string message
    **/
    public function e404($message = NULL){
        header("HTTP/1.0 404 Not Found");

        if ($GLOBALS['conf']['environment'] == "prod") {
            $this->data = [];
            $this->template->write('title', 'Page introuvable');
            $this->render('errors/404');
            $this->renderDisplay();
            $this->displayView();
        } else {
            throw new \RuntimeException($message);
        }
        die();
    }


    // -------------------------------------------------------------------------

    /**
    * Permet de gérer les erreurs 500
    *
    * @param string message
    **/
    public function e500($message = NULL){
        header("HTTP/1.0 500 Internal Server Error");

        if ($GLOBALS['conf']['environment'] == "prod") {
            $this->data = [];
            $this->template->write('title', 'Erreur interne du serveur');
            $this->render('errors/500');
            $this->renderDisplay();
            $this->displayView();
        } else {
            throw new \RuntimeException($message);
        }
        die();
    }


    // -------------------------------------------------------------------------

    /**
    * Permet de gérer les maintenances
    **/
    public function eMaintenance() {
        header("HTTP/1.0 503 Service Unavailable");
        $this->data = [];
        $this->render('errors/maintenance');
        $this->renderDisplay();
        $this->displayView();
        die();
    }


    // -------------------------------------------------------------------------

    /**
     * Vérifie l'accès aux ressources avec le modèle Ability
     */

    public function checkAbility() {
        if ($GLOBALS['conf']['cancan']['enabled'] && property_exists($this, 'authorize')) {
            $this->cancan = new Ability(Auth::user());
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Ajoute des librairies de ressources externes à charger
     *
     * @param string elem (CSS | JS_Header_Scripts | JS_Async_Scripts | JS_Bottom_Scripts | JS_Vars)
     * @param string|array string
     */

    public function setLibs($elem, $params) {
        if (is_array($params)) {
            $this->Libs[$elem] = array_merge($this->Libs[$elem], $params);
        } else {
            $this->Libs[$elem][] =  $params;
        }
    }


    // -------------------------------------------------------------------------

    /**
     * Effectue le rendu avec template et gestion des librairies externes du fichier donné
     *
     * @param string ControllerRenderExecuteFile
     */

    protected function renderExecute($ControllerRenderExecuteFile) {
        $tabRegions = [];
        foreach ($GLOBALS['Template'][$this->layout]['regions'] as $templateRegion) {
            $tabRegions[$templateRegion] = $this->template->render($templateRegion);
        }

        if ($this->outputMode == 'html' && $this->externalLibsHandeler) {
            $this->data['Libs'] = $this->buildLibs();
        }

        $this->renderedView = $this->controllerRenderExecute($ControllerRenderExecuteFile, $this->data, $this->layout, $tabRegions);
        $this->rendered = TRUE;
    }


    // -------------------------------------------------------------------------

    /**
     * Effectue le rendu sans template du fichier donné
     *
     * @param string ControllerRenderExecuteFile
     */

    protected function renderNone($ControllerRenderExecuteFile) {
        $this->renderedView = $this->controllerRenderNone($ControllerRenderExecuteFile, $this->data);
        $this->rendered = TRUE;
    }


    // -------------------------------------------------------------------------

    /**
     * Contruit les tableaux de ressources pour la gestion des librairies externes
     *
     ** @return array
     */

    protected function buildLibs() {

        $libs = [];

        // CSS
        $css = '';
        foreach ($this->Libs['CSS'] as $lib) {
            $css .= '<link href="/css/' . $lib . '.css" rel="stylesheet" type="text/css">';
        }
        $libs['CSS'] = $css;


        // JS
        $js = '';
        foreach ($this->Libs['JS_Header_Scripts'] as $lib) {
            if(mb_strpos($lib, 'http') !== 0) {
                $js .= '<script src="/js/' . $lib . '.js"></script>';
            } else {
                $js .= '<script src="' . $lib . '"></script>';
            }
        }
        $libs['JS_Header_Scripts'] = $js;

        $js = '';
        foreach ($this->Libs['JS_Bottom_Scripts'] as $lib) {
            if(mb_strpos($lib, 'http') !== 0) {
                $js .= '<script src="/js/' . $lib . '.js"></script>';
            } else {
                $js .= '<script src="' . $lib . '"></script>';
            }
        }
        $libs['JS_Bottom_Scripts'] = $js;


        // JS Async
        $js = '';
        foreach ($this->Libs['JS_Async_Scripts'] as $lib) {
            $js .= 'add(\'' . $lib . '\');';
        }
        $libs['JS_Async_Scripts'] = $js;

        // JS Vars

        $this->Libs['JS_Vars']['AJAX_ScriptSelf']       = Request::getUrl();

        $this->Libs['JS_Vars']['AJAX_ErrorCode']        = self::AJAX_ErrorCode;
        $this->Libs['JS_Vars']['AJAX_SuccessCode']      = self::AJAX_SuccessCode;
        $this->Libs['JS_Vars']['AJAX_EndSessionCode']   = self::AJAX_EndSessionCode;

        $this->Libs['JS_Vars']['AJAX_Maintenance']      = self::AJAX_Maintenance;
        $this->Libs['JS_Vars']['AJAX_ConnexionRequest'] = self::AJAX_ConnexionRequest;

        if (!empty($this->Libs['JS_Vars'])) {
            $vars = '<script type="text/javascript">';
            foreach ($this->Libs['JS_Vars'] as $entry => $value) {
                $vars .= 'var ' . $entry . ' = ' . (is_numeric($value) ? $value : "'" . $value . "'") . ";\n";
            }
            $vars .= '</script>';
            $libs['JS_Vars'] = $vars;
        } else {
            $libs['JS_Vars'] = '';
        }

        return $libs;
    }


    // -------------------------------------------------------------------------

    /**
     * Execution un rendu avec template
     *
     * @param string controllerRenderExecuteFile
     * @param array controllerRenderExecuteData
     * @param string controllerRenderExecuteLayout
     * @param array controllerRenderExecuteRegions
     * @return string
     */

    protected function controllerRenderExecute($controllerRenderExecuteFile, $controllerRenderExecuteData, $controllerRenderExecuteLayout, $controllerRenderExecuteRegions){
        extract($controllerRenderExecuteData);
        ob_start();

        require $controllerRenderExecuteFile;
        ${$GLOBALS['Template'][$controllerRenderExecuteLayout]['main_region']} = ob_get_clean();
        extract($controllerRenderExecuteRegions);

        ob_start();
        require VIEWS . DS . 'layouts' . DS . $controllerRenderExecuteLayout . '.php';

        return ob_get_clean();
    }


    // -------------------------------------------------------------------------

    /**
     * Execution un rendu sans template
     *
     * @param string controllerRenderExecuteFile
     * @param array controllerRenderExecuteData
     * @return string
     */

    protected function controllerRenderNone($controllerRenderExecuteFile, $controllerRenderExecuteData){
        extract($controllerRenderExecuteData);
        ob_start();

        require $controllerRenderExecuteFile;
        return ob_get_clean();
    }

}


/* End of file */