<?php
use core\Response;
use core\DataHelper\DataHelper;
use core\JWT;

require_once "micron/Micron.php";

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

function exampleRequestObject(Request $request){
    try {
        $response = new Response();

        $response->success("Uri param : {$request->URIparams["param"]}, Query Param : {$request->requestBody["qparam"]}");
        
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

function exampleTableInsert(){
    $response = new Response();
    try {
        $db = new Database();
        $table = $db->Table("example_table");
        $insertBody = array(
            "description" => "example"
        );
        $id = $table->insert($insertBody);
        $response->created("Insert in table completed with id : {$id}");
    } catch (\Throwable $th) {
        $response->response($th->getMessage(), array(), false, $th->getCode() );
    }
}

function exampleDatabaseClass(){
    $response = new Response();
    try {
        $database = new Database();



        $query = "  CREATE TABLE IF NOT EXISTS `database_class_example_table` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `description` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
                        `some_field` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
                        PRIMARY KEY (`id`) USING BTREE
                    )";
        //this version of ExecQuery mantain the connection with database opened, this allow to use transactions and better performance
        $database->ExecQuery($query);

        $inserted_row = 0;
        //remember to begin the transactions after CREATE or ALTER because this operations fires the autocommit
        $database->beginTransaction(); //with auto_rollback setted to true, if the query fail the rollback function will automatically fired.

        $query = "INSERT INTO database_class_example_table (description) VALUES (:description)"; //all Database::ExecQuery are prepared, so don't worry about escaping SQL characters!
        $description = "TEST";
        $result = $database->ExecQuery($query, ["description" => $description]);

        $inserted_row += $result->rowCount(); 

        $query = "INSERT INTO database_class_example_table (description) VALUES (:description)"; //all Database::ExecQuery are prepared, so don't worry about escaping SQL characters!
        $description = "TEST2";
        $result = $database->ExecQuery($query, ["description" => $description]);

        $inserted_row += $result->rowCount();

        //decomment this block to fire the auto-rollback
        /* $query = "INSERT INTO not_existing_table (description) VALUES (:description)"; //all Database::ExecQuery are prepared, so don't worry about escaping SQL characters!

        $description = "TEST2";

        $database->ExecQuery($query, ["description" => $description]); */


        $database->commit();

        $response->created("Inserted {$inserted_row} rows.");

    } catch (\Throwable $th) {
        $response->response($th->getMessage(), array(), false, $th->getCode() );
    }
}

function exampleSExecQuery(){
    $response = new Response();
    try {

        $query = "  CREATE TABLE IF NOT EXISTS `database_class_example_table` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `description` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
            `some_field` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
            PRIMARY KEY (`id`) USING BTREE
        )";
        Database::SExecQuery($query);

        $affected_rows = 0;

        $query = "INSERT INTO database_class_example_table (description) VALUES (:description)"; //all Database::ExecQuery are prepared, so don't worry about escaping SQL characters!
        $description = "TEST";
        $affected_rows += Database::SExecQuery($query, ["description" => $description])->rowCount();

        $query = "INSERT INTO database_class_example_table (description) VALUES (:description)"; //all Database::ExecQuery are prepared, so don't worry about escaping SQL characters!
        $description = "TEST2";
        $affected_rows += Database::SExecQuery($query, ["description" => $description])->rowCount();

        //Faster but you can't use Transactions and this can lower performances
        $response->success("Inserted {$affected_rows} rows.");

    } catch (\Throwable $th) {
        $response->response($th->getMessage(), array(), false, $th->getCode() );
    }
}

function insertWithTableFeature(Request $request){
    $response = new Response();
    try {

        $db = new Database();
        $table = $db->Table("database_class_example_table");

        $createdId = $table->create($request->requestBody);

        $response->created("Adding row to database_class_example_table with id : $createdId");

    } catch (\Throwable $th) {
        $response->response($th->getMessage(), array(), false, $th->getCode() );
    }
}

function deleteWithTableFeature(Request $request){
    $response = new Response();
    try {
        
        $db = new Database();
        $table = $db->Table("database_class_example_table");

        $table->delete("id = :id", ["id" => $request->URIparams["id"]]);

        $response->success("Row successful deleted.");

    } catch (\Throwable $th) {
        $response->response($th->getMessage(), array(), false, $th->getCode() );
    }
}


function updateWithTableFeature(Request $request){
    $response = new Response();
    try {
        
        $db = new Database();
        $table = $db->Table("database_class_example_table");

        $affectedRows = $table->update("id = :id", ["id" => $request->URIparams["id"]], ["description" => "update test"]);

        if($affectedRows === 0){
            $response->success("Any modification applied.");
        }

        $response->success("Update successful");

    } catch (\Throwable $th) {
        $response->response($th->getMessage(), array(), false, $th->getCode() );
    }
}


function readWithTableFeature(Request $request){
    $response = new Response();
    try {
        
        $db = new Database();
        $table = $db->Table("database_class_example_table");

        $row = $table->read("id = :id", ["id" => $request->URIparams["id"]]);

        if(count($row) === 0){
            $response->notFound("No data found.");
        }

        $response->success("Operation successful", $row);

    } catch (\Throwable $th) {
        $response->response($th->getMessage(), array(), false, $th->getCode() );
    }
}

function readListWithTableFeature(Request $request){
    $response = new Response();
    try {
        
        $db = new Database();
        $table = $db->Table("database_class_example_table");

        $row = $table->read();

        if(count($row) === 0){
            $response->notFound("No data found.");
        }

        $response->success("Operation successful", $row);

    } catch (\Throwable $th) {
        $response->response($th->getMessage(), array(), false, $th->getCode() );
    }
}




