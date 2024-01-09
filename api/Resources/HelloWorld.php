<?php
use core\MiddlewareConfiguration;
use core\Resource;
use core\Response;

require_once 'micron/Micron.php';

class HelloWorld implements Resource
{
    public function listen(Route $router) : void {
        $router->get('/', function(Request $request){
            Response::instance()->success("Hello from Micron, are you ready to build awesome API ?");
        }, middlewareSettings: MiddlewareConfiguration::getConfiguration(tokenControl: false));
    }
}
