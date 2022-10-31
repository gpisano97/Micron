<?php
use helper\Response;
use helper\DataHelper;
use JWT\JWT;

function example(array $params = []){
    
    include_once 'helper/DataHelper.php';
    include_once 'helper/Response.php';
    include_once 'helper/Database.php';
    include_once 'JWT/JWT.php';
    
    echo "qua";
    
    try {
        $response = new Response();
        $token = DataHelper::getToken(); //JWT Bearer Token -> check authExample.php
        if(JWT::verify($token)){
            //Make SQL query using Database::ExecQuery($query_string, $params)
            
            if(isset($params["param_example"])){ //this array contains URI parameters.
                
                $response->success("Operation successful", array("your_params_value" => $params["param_example"]));
            }
            else{
                $data_fetched_from_db = array("example" => "Hello world from Micron Rest API");
                if(count($data_fetched_from_db) > 0){
                    $response->success("Operation successful", $data_fetched_from_db);
                }
                else{
                    $response->notFound("No data found for this resource.");
                }
            }
            
            
            
        }
        
    } catch (Exception $e) {
        $response->response($e->getMessage(), array(), false, $e->getCode() );
    }
}


