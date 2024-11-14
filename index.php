<?php
use core\DataHelper\DataHelper;
use core\Media\FilesManager;
use core\Response;
use core\MiddlewareConfiguration;

require_once "micron/Micron.php";
require_once "api/Resources/HelloWorld.php"; //don't forget to require you resources!


$route = new Route();
//in order to make the application "private"
/* $route->isPublished = false;
$route->notPulishedCallback = function(){
    Response::instance()->success("API not published yet.");
};
//key for guarantee access to endpoints. Must be added at the end of the request URI as uri param.
$route->accessPassphraseKeyIfNotPublished = "pw";
//value for guarantee access to endpoints
$route->accessPassphraseIfNotPublished = "OK"; */


$route->enableCORS();


//if you want to manually call all the listeners
/* $helloWorldResource = new HelloWorld();
$helloWorldResource->listen($route); */

//if you want to manually register your resources
/* $route->registerResources([
    "HelloWorld"
]); */

$route->static("public");

//if you want to start the resource autolisten. This will run all your resources.
//visit http://{your_host} to see the micron landing page, this page is provided by the HelloWorld Resource
$route->start();

$route->notFound("404.php");
