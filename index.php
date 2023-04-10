<?php
use core\Response;
use core\Users;
use core\MiddlewareConfiguration;
require_once "api/auth/authExample.php";
require_once "api/example/example.php";
require_once "micron/Micron.php";


$route = new Route();



$route->enableCORS();

try {


    
    //USERS MANAGEMENT -> don't use this, is still experimental.

    $userManagement = new Users();
    //$userManagement->listen($route);

    $route->get("users", function(Request $request) use ($userManagement){
        $userManagement->read($request);
    }, middlewareSettings : MiddlewareConfiguration::getConfiguration(tokenControl : false));

    $route->post("users", function(Request $request) use($userManagement){
        $userManagement->create($request);
    }, middlewareSettings : MiddlewareConfiguration::getConfiguration(tokenControl : false));

    $route->delete("users/{user_id:numeric}", function(Request $request) use ($userManagement){
        $userManagement->delete($request);
    }, middlewareSettings : MiddlewareConfiguration::getConfiguration(tokenControl : false));
    
    //GET
    $route->get("example/adjacency", function () {
        exampleAdjacency();
    });
    
    $route->get("example", function(){
        example();
    });
    
    $route->get("home", function(){
        $response = new Response();
        $response->provideFile('test.html', "inline");
    }, middlewareSettings : ["TOKEN_CONTROL" => false]);
    
    $route->get("example/{param_example:string}", function(Request $request){
        example($request->URIparams);
    });


    $route->get("example/request/{param:string}", function(Request $request){
        exampleRequestObject($request);
    }, allowedQueryParams : ["qparam" => "string"], middlewareSettings : new MiddlewareConfiguration(tokenControl : false));


    $route->get("example/request/{param:numeric}", function(Request $request){
        exampleRequestObject($request);
    }, allowedQueryParams : ["qparam" => "string"], middlewareSettings : MiddlewareConfiguration::getConfiguration(tokenControl : false));

    
    $route->get("example/databaseclass/table", function(Request $request){
        readListWithTableFeature($request);
    });

    $route->get("example/databaseclass/table/{id:numeric}", function(Request $request){
        readWithTableFeature($request);
    }, middlewareSettings : new MiddlewareConfiguration(tokenBodyAuthorizedValues : ['level' => 'ADMIN']));
    
     //POST
       
    $route->post("authorize", function(Request $request){
        authExample($request);
    }, middlewareSettings : new MiddlewareConfiguration(tokenControl : false, acceptedContentType  : ["application/x-www-form-urlencoded"]) );
    
    $route->post("example/tableinsert", function(){
        exampleTableInsert();
    });

    $route->post("example/databaseclass", function(){
        exampleDatabaseClass();
    });

    $route->post("example/databaseclass/sexecquery", function(){
        exampleSExecQuery();
    });
     
    $route->post("example/databaseclass/table/insert", function(Request $request){
        insertWithTableFeature($request);
    }, middlewareSettings :  new MiddlewareConfiguration(tokenBodyAuthorizedValues : ["level" => "ADMIN"]));
    
    
    //DELETE
    
    $route->delete("example/databaseclass/table/{id:numeric}/delete", function(Request $request){
        deleteWithTableFeature($request);
    }, middlewareSettings : new MiddlewareConfiguration(tokenBodyAuthorizedValues : ["level" => "ADMIN"]));
    
    //PUT

    $route->put("example/databaseclass/table/{id:numeric}/update", function(Request $request){
        updateWithTableFeature($request);
    }, middlewareSettings : new MiddlewareConfiguration(tokenBodyAuthorizedValues : ["level" => "ADMIN"]));

    
    $route->notFound("404.php");
} catch (\Throwable $th) {
    $response = new Response();
    $response->response($th->getMessage(), [], false, $th->getCode());
    exit;
}
