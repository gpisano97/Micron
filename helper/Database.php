<?php

include_once $_SERVER["DOCUMENT_ROOT"].'/config.php';

class Database{
    private $host = DB_HOST;
    private $database_name = DB_DATABASE_NAME;
    private $username = DB_USERNAME;
    private $password = DB_PASSWORD;
    public $connection;
    
    /**
     * @return PDO
     * 
     * Use this for use all PDO class functions.
     */
    public function getConnection(){
        $this->connection = null;
        try {
            $this->connection = new PDO("mysql:host=" .$this->host . ";dbname=" . $this->database_name, $this->username, $this->password);
            $this->connection->exec("set names utf8");
        }
        catch(PDOException $exception){
            throw new Exception("Errore di connessione: ". $exception->getMessage(), 500);
        }
        return $this->connection;
    }
    
    /**
     * @param string $query_string
     * @param array $params
     * @throws Exception
     * @return PDOStatement
     * 
     * Use this for fast query executions.
     */
    public static function ExecQuery($query_string, $params = []){
        $db = new \Database();
        $conn = $db->getConnection();
        $q_p = $conn->prepare($query_string);
        if($q_p === false){
            throw new Exception("Query prepare error.", 501);
        }
        if($q_p->execute($params) === false){
            throw new Exception("Query execution error.", 501);
        }
        return $q_p;
    }
}