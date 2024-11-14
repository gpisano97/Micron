<?php
use core\Attributes\ResourceName;
use core\DataHelper\UriParam;
use core\DataHelper\UriParamType;
use core\MiddlewareConfiguration;
use core\Resource;
use core\Response;

require_once 'micron/Micron.php';



#[ResourceName("")]
class HelloWorld implements Resource
{
    public function listen(Route $router) : void {
        $router->get('/', function(Request $request){
            Response::instance()->provideFile($_SERVER['DOCUMENT_ROOT']."/views/main.html", false);
        }, middlewareSettings: MiddlewareConfiguration::getConfiguration(tokenControl: false));

        $param = new UriParam("param", UriParamType::number);
        $param2 = new UriParam("param2", UriParamType::number); 
        $param3 = new UriParam("param3", UriParamType::number);

        $router->get("$param/$param2/$param3", function(Request $request) use($param, $param2) {
            $responseString = "Param value : ".$request->getUriParamValue($param);
            $responseString .= ", param2 value : ".$request->URIparams[$param2->paramName];
            $responseString .= ", param3 value : ".$request->URIparams["param3"];
            Response::instance()->success($responseString);
        }, middlewareSettings: MiddlewareConfiguration::getConfiguration(tokenControl: false));
    }
}
