<?php
use helper\Response;
use helper\DataHelper;
use JWT\JWT;
use helper\ParamKey;

include_once 'helper/DataHelper/DataHelper.php';
include_once 'helper/Response.php';
include_once 'helper/Database.php';
include_once 'JWT/JWT.php';


function authExample(array $params = []){
 
    //use x-www-form-urlencoded in the request
    
    //if you want to use raw-body, use DataHelper::postGetBody() to get data.
    
    try {
        $response = new Response();
        
        $keys = array(
            0 => new ParamKey("username", true),
            1 => new ParamKey("password", true)
        );
        
        if(DataHelper::checkParameters($keys, $_POST)){
            
            //Make SQL query using Database::ExecQuery($query_string, $params) for check user in DB and return data.
            $token = new JWT(array("name" => "test", "surname" => "example", "username" => $_POST["username"])); //set the secret encription key on JWT/config.php or use the second parameter
            $response->created("User is authenticated.", array("token" => $token->getToken()));
            
        }
        else{
            $response->badRequest("Missing parameters.");
        }
        
    } catch (Exception $e) {
        $response->response($e->getMessage(), array(), false, $e->getCode() );
    }
}
