<?php
use core\MiddlewareConfiguration;
use core\Resource;
use core\Response;

require_once 'micron/Micron.php';

class Test implements Resource
{
    public function listen(Route $router) : void {
        $router->get('/{value}', function(Request $request){
            Response::instance()->success('Hello from micron, here the value : '.$request->URIparams['value']." and the query param : {$request->queryParams['qp1']}");
        }, middlewareSettings: MiddlewareConfiguration::getConfiguration(tokenControl: false), allowedQueryParams: ["qp1", "qp2" => "string"]);
    }
}
