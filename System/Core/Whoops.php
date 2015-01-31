<?php
namespace System\Core;

class Whoops
{

    private $whoops;
    private $errorPage;
    private $format = null;

    public function __construct(){
        $this->whoops = new \System\Libs\Whoops\Run();
        $this->errorPage = new \System\Libs\Whoops\Handler\PrettyPageHandler();

        $this->errorPage->setPageTitle("It's broken!"); // Set the page's title
        $this->errorPage->setEditor("sublime");         // Set the editor used for the "Open" link

        $this->whoops->pushHandler($this->errorPage);
        $this->whoops->register();
    }

    public function format($format){

        if($format == 'json'){
            $this->whoops->pushHandler(new \System\Libs\Whoops\Handler\JsonResponseHandler());
        }
        $this->whoops->register();
    }
}

/* End of file */