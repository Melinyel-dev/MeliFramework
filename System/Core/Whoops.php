<?php

class Whoops
{

    private $whoops;
    private $errorPage;
    private $format = null;

    public function __construct(){
        $this->whoops = new Whoops\Run();
        $this->errorPage = new Whoops\Handler\PrettyPageHandler();

        $this->errorPage->setPageTitle("It's broken!"); // Set the page's title
        $this->errorPage->setEditor("sublime");         // Set the editor used for the "Open" link

        $this->whoops->pushHandler($this->errorPage);
        $this->whoops->register();
    }

    public function format($format){

        if($format == 'json'){
            $this->whoops->pushHandler(new Whoops\Handler\JsonResponseHandler());
        }
        $this->whoops->register();
    }
}

function WhoopsAutoload($class){
    if(strpos($class, 'Whoops') !== 0)
        return;

    if(file_exists($file = LIBRARIES.DS.str_replace('\\', DS, $class).'.php'))
        require $file;
}

spl_autoload_register('WhoopsAutoload');

/* End of file */