<?php
namespace core;
use core\Database\DBTable;
use core\Database\Attributes;
use Exception;
use PDO;
use PDOException;
use PDOStatement;

include_once 'config.php';
include_once 'DBTable.php';
include_once 'Attributes/Table.php';
include_once 'Attributes/PrimaryKey.php';
include_once 'Attributes/DefaultValue.php';
include_once 'Attributes/Required.php';
include_once 'Attributes/TableField.php';
include_once 'Attributes/CreateIfNotExist.php';
include_once 'Types/PropertyInfo.php';
include_once 'ColumnTypes/Numbers/DbInt.php';
include_once 'ColumnTypes/Numbers/BigInt.php';
include_once 'ColumnTypes/Numbers/DbFloat.php';
include_once 'ColumnTypes/Numbers/DbDouble.php';
include_once 'ColumnTypes/Strings/Varchar.php';
include_once 'ColumnTypes/Strings/Text.php';
include_once 'DBModel.php';


class Database extends PDO
{

    private $host = DB_HOST;
    private $database_name = DB_DATABASE_NAME;
    private $username = DB_USERNAME;
    private $password = DB_PASSWORD;
    private $dbtype = DB_TYPE;
    private $port = DB_PORT;
    private bool $auto_rollback = false;

    private string $charset = "utf8";


    public function __construct(bool $auto_rollback = true, string $charset = "utf8")
    {
        try {
            parent::__construct($this->dbtype. ":host=" . $this->host .";port=".$this->port . ";dbname=" . $this->database_name.";charset={$charset}", $this->username, $this->password);
            $this->auto_rollback = $auto_rollback;
            $this->charset = $charset;
        } catch (PDOException $exception) {
            throw new Exception("Database connection error: " . $exception->getMessage(), 500);
        }
    }


    /**
     * Execute a query on the Database.
     * @param string $query -> example : SELECT * FROM table WHERE id = :id
     * @param array $params -> example (using the $query example) : ["id" => $id]
     * 
     * @return \PDOStatement
     * @throws \Exception
     * 
     */
    public function ExecQuery(string $query, array $params = [])
    {
        try {
            
            $q_p = $this->prepare($query);
            $exec = $q_p->execute($params);
        } catch (Exception $e) {
            if ($this->auto_rollback && $this->inTransaction()) {
                $this->rollback();
            }
            throw new Exception("Database Error: " . $e->getMessage(), 500);
        }

        if ($q_p === false) {
            if ($this->auto_rollback && $this->inTransaction()) {
                $this->rollback();
            }
            throw new Exception("Query prepare error.", 500);
        }
        if ($exec === false) {
            if ($this->auto_rollback && $this->inTransaction()) {
                $this->rollback();
            }
            throw new Exception("Query execution error.", 500);
        }
        return $q_p;
    }

    /**
     * Connect and execute a query on the database.
     * Use this for fast query executions. For multiple queries i suggest to use the ExecQuery function of Database object 
     * because this function open a connection for every query and this can result in poor performance.
     * 
     * @param string $query_string
     * @param array $params
     * @throws Exception
     * @return PDOStatement
     * 
     */
    public static function SExecQuery($query_string, $params = [])
    {
        try {
            $db = new Database();
            $q_p = $db->prepare($query_string);
            $exec = $q_p->execute($params);
            unset($db);
        } catch (Exception $e) {
            throw new Exception("Database Error: " . $e->getMessage(), 500);
        }

        if ($q_p === false) {
            throw new Exception("Query prepare error.", 500);
        }
        if ($exec === false) {
            throw new Exception("Query execution error.", 500);
        }
        return $q_p;
    }



    /**
     * @param string $tableName
     * 
     * @return array
     * 
     * @throws Exception
     *
     */
    public function getTableScheme(string $tableName)
    {
        if ($tableName !== "") {
            try {
                $query = "SHOW TABLES LIKE '{$tableName}'";
                $result = $this->ExecQuery($query);
                if ($result->rowCount() === 0) {
                    throw new Exception("Table not found.", 404);
                }
                $query = "DESCRIBE $tableName";
                $result = $this->ExecQuery($query);
                return $result->fetchAll(PDO::FETCH_COLUMN);
            } catch (\Throwable $e) {
                throw new Exception($e->getMessage(), ($e->getCode() === null ? 500 : $e->getCode()));
            }

        }
        throw new Exception("Missing table's name.", 500);
    }

    public function Table(string $tableName)
    {
        return new DBTable($this, $tableName);
    }

    public function getDbType(){
        return $this->dbtype;
    }
}