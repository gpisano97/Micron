<?php
use helper\Response;
use helper\DataHelper;
use JWT\JWT;

include_once 'helper/DataHelper/DataHelper.php';
include_once 'helper/Response.php';
include_once 'helper/Database.php';
include_once 'JWT/JWT.php';

function example(array $params = []){
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

function exampleAdjacency(){
    try {
        $response = new Response();
        $AdjacencyListSimulation = [
            array("id" => 1, "parent_id" => null, "description" => "root"),
            array("id" => 2, "parent_id" => 1, "description" => "Children1"),
            array("id" => 3, "parent_id" => 1, "description" => "Children2"),
            array("id" => 4, "parent_id" => 2, "description" => "Children1-1"),
            array("id" => 5, "parent_id" => 2, "description" => "Children1-2"),
        ];
        $dHelper = new DataHelper();
        $tree = $dHelper->convertAdjacencyListToNestedObject($AdjacencyListSimulation);
        $response->success("Success", $tree);
    } catch (\Throwable $e) {
        $response->response($e->getMessage(), array(), false, $e->getCode() );
    }
}


