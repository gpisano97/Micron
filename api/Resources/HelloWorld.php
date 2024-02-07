<?php
use core\Media\FilesManager;
use core\MiddlewareConfiguration;
use core\Resource;
use core\Response;

require_once 'micron/Micron.php';

class HelloWorld implements Resource
{
    public function listen(Route $router) : void {
        $router->get('/', function(Request $request){
            Response::instance()->provideFile($_SERVER['DOCUMENT_ROOT']."/views/main.html", false);
        }, middlewareSettings: MiddlewareConfiguration::getConfiguration(tokenControl: false));
    }
}
