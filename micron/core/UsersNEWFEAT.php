<?php
use core\Database;
use core\Response;
use core\DataHelper\DataHelper;
use core\JWT;

include_once 'DataHelper/DataHelper.php';
include_once 'Response.php';
include_once 'Database/Database.php';
include_once 'JWT/JWT.php';


class Users
{
    private array $levels;
    public function __construct()
    {

        if(defined(USER_LEVELS)){
            $this->levels = explode(",",USER_LEVELS);
        }
        
        $database = new Database();

        $query = "CREATE TABLE IF NOT EXISTS `users` (
            `user_id` INT NOT NULL AUTO_INCREMENT,
            `username` VARCAHR(50) NOT NULL DEFAULT '0',
            `email` VARCAHR(100) NOT NULL DEFAULT '',
            `name` VARCAHR(50) NOT NULL DEFAULT '',
            `surname` VARCAHR(50) NOT NULL DEFAULT '',
            " . (defined(USER_LEVELS) ? "`level` ENUM(" . USER_LEVELS . ")" : "") . "
            PRIMARY KEY (`user_id`),
            UNIQUE INDEX `username` (`username`),
            UNIQUE INDEX `email` (`email`)
        )
        COLLATE='utf8mb4_general_ci'
        ";

        $database->ExecQuery($query);

        $query = "CREATE TABLE IF NOT EXISTS `users_passwords` (
            `user_id` INT NOT NULL,
            `password` VARCAHR(300) NULL,
            PRIMARY KEY (`user_id`),
            CONSTRAINT `users_passwords_references_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE CASCADE
        )
        COLLATE='utf8mb4_general_ci'";

        $database->ExecQuery($query);

    }

    public function addUser()
    {
        try {
            $response = new Response();
            $database = new Database();

            $requestBody = DataHelper::postGetBody();
            if (count($requestBody) === 0) {
                $response->badRequest("Given body is empty.");
            }


            /*             $query = "SHOW TABLES LIKE 'users'";
            $result = $database->ExecQuery($query); */


            //getting table scheme and checking if request body is coerent.

            $users_scheme = $database->getTableScheme("users");

            //username, email and password has to be present:

            $bodyKeys = array_keys($requestBody);
            $missingCount = 0;
            $missingParameters = "";
            if (!in_array("username", $bodyKeys)) {
                $missingCount++;
                $missingParameters .= " username ";
            }
            if (!in_array("email", $bodyKeys)) {
                $missingCount++;
                $missingParameters .= " email ";
            }
            if (!in_array("password", $bodyKeys)) {
                $missingCount++;
                $missingParameters .= " password ";
            }

            $missingParameters = trim($missingParameters);

            if ($missingCount > 0) {
                $response->badRequest("Request body missing parameter" . ($missingCount > 1 ? "s" : "") . ": {$missingParameters}");
            }


            //now remove the unwanted keys from requestBody

            $fields = "";
            $values = "";
            $password = $requestBody["password"];
            unset($requestBody["password"]);
            foreach ($requestBody as $field => $value) {
                if (!in_array($field, $users_scheme)) {
                    unset($requestBody[$field]);
                    $fields .= "{$field},";
                    $values .= ":{$field},";
                }
            }

            $fields = rtrim($fields, ",");
            $values = rtrim($values, ",");


            $database->beginTransaction();

            $query = "INSERT INTO users ({$fields}) VALUES ({$values})";
            $database->ExecQuery($query, $requestBody);

            $query = "SELECT LAST_INSERT_ID() id";
            $id = $database->ExecQuery($query)->fetch(PDO::FETCH_ASSOC)["id"];

            $query = "INSERT INTO users_passwords (user_id, password) VALUES (:uid, SHA2(CONCAT(:uid, :pw),256))";
            $database->ExecQuery($query, ["uid" => $id, "pw" => $password]);

            $response->created("New users successful created.");


        } catch (\Throwable $th) {
            $response->response($th->getMessage(), [], false, $th->getCode());
        }

    }

    public function deleteUser(int $userId)
    {
        try {

            $response = new Response();
            $database = new Database();

            $query = "SELECT * FROM users WHERE user_id = :uid";
            $result = $database->ExecQuery($query, ["uid" => $userId]);

            if ($result->rowCount() === 0) {
                $response->notFound("User not found.");
            }

            $query = "DELETE FROM users WHERE user_id = :uid";
            $database->ExecQuery($query, ["uid" => $userId]);

            $response->success("User deleted.");

        } catch (\Throwable $th) {
            $response->response($th->getMessage(), [], false, $th->getCode());
        }

    }

    public function userList()
    {
        try {

            $response = new Response();
            $database = new Database();

            $query = "SELECT * FROM users";

            $result = $database->ExecQuery($query);

            if($result->rowCount() === 0){
                $response->notFound("Any user found.");
            }

            $response->success("User list successful compiled.", $result->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Throwable $th) {
            $response->response($th->getMessage(), [], false, $th->getCode());
        }
    }
}