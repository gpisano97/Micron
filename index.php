<?php
use core\Response;
require_once "api/auth/authExample.php";
require_once "api/example/example.php";
require_once "micron/Micron.php";


$route = new Route();



$route->enableCORS();

try {

    //GET
    $route->get("example/adjacency", function () {
        exampleAdjacency();
    });
    
    $route->get("example", function(){
        example();
    });
    
    
    
    $route->get("example/{param_example:string}", function($request){
        example($request->URIparams);
    });


    $route->get("example/request/{param:string}", function($request){
        exampleRequestObject($request);
    }, allowedQueryParams : ["qparam" => "string"], middlewareSettings : ["TOKEN_CONTROL" => false]);


    $route->get("example/request/{param:numeric}", function($request){
        exampleRequestObject($request);
    }, allowedQueryParams : ["qparam" => "string"], middlewareSettings : ["TOKEN_CONTROL" => false]);

    
    $route->get("example/databaseclass/table", function(Request $request){
        readListWithTableFeature($request);
    }, middlewareSettings : ["TOKEN_CONTROL" => true]);

    $route->get("example/databaseclass/table/{id:numeric}", function(Request $request){
        readWithTableFeature($request);
    }, middlewareSettings : ["TOKEN_CONTROL" => true, "TOKEN_AUTH" => ["level" => "ADMIN"]]);
    
    //POST
       
    $route->post("authorize", function($request){
        authExample($request);
    }, middlewareSettings : ["TOKEN_CONTROL" => false]);
    
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
    }, middlewareSettings : ["TOKEN_CONTROL" => true, "TOKEN_AUTH" => ["level" => "ADMIN"]]);
    
    
    //DELETE
    
    $route->delete("example/databaseclass/table/{id:numeric}/delete", function(Request $request){
        deleteWithTableFeature($request);
    }, middlewareSettings : ["TOKEN_CONTROL" => true, "TOKEN_AUTH" => ["level" => "ADMIN"]]);
    
    //PUT

    $route->put("example/databaseclass/table/{id:numeric}/update", function(Request $request){
        updateWithTableFeature($request);
    }, middlewareSettings : ["TOKEN_CONTROL" => true, "TOKEN_AUTH" => ["level" => "ADMIN"]]);

    
    $route->notFound("404.php");
} catch (\Throwable $th) {
    $response = new Response();
    $response->response($th->getMessage(), [], false, $th->getCode());
    exit;
}
