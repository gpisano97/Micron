<?php
use core\Response;
use core\DataHelper\DataHelper;
use core\JWT;
use core\DataHelper\ParamKey;

require_once "micron/Micron.php";


function authExample(Request $request){
 
    //use x-www-form-urlencoded in the request
    
    //if you want to use raw-body, use DataHelper::postGetBody() to get data.
    
    try {
        $response = new Response();
        
        $keys = array(
            0 => new ParamKey("username", true),
            1 => new ParamKey("password", true)
        );
        
        if(DataHelper::checkParameters($keys, $request->requestBody)){
            $body = array("name" => "test", "surname" => "example", "username" => $_POST["username"], "level" => "ADMIN");
            //Make SQL query using Database::ExecQuery($query_string, $params) for check user in DB and return data.
            $token = new JWT($body); //set the secret encription key on JWT/config.php or use the second parameter
            $response->success("User is authenticated.", array("token" => $token->getToken(), "body" => $body));
            
        }
        else{
            $response->badRequest("Missing parameters.");
        }
        
    } catch (Exception $e) {
        $response->response($e->getMessage(), array(), false, $e->getCode() );
    }
}
