<?php
include_once 'helper/Route.php';
require_once 'api/example/example.php';
require_once 'api/auth/authExample.php';


$route = new Route();

//GET

$route->get("example/adjacency", function () {
    exampleAdjacency();
});

$route->get("example/{param}", function($params){
    example();
});



/*$route->get("example/{param_example}", function($params){
    example($params);
});*/


//POST
   
$route->post("authorize", function(){
    authExample();
});
 


//DELETE



//PUT

$route->notFound("404.php");