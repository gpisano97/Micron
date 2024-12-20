<?php
namespace core\Database;

use core\Database;
use core\DataHelper\DataHelper;
use Exception;
use PDO;
use stdClass;

require_once "Database.php";
require_once "micron/core/DataHelper/DataHelper.php";

define("EQUAL", 1);
define("NOTEQUAL", -1);
define("LIKE", 2);

/**
 * The Table class is an entry point for making simple CRUD operation on a database's table.
 * Is suggested using this class and his methods for archive's tables.
 * The table must be present on the database.
 */
class DBTable
{

    private Database $database;
    private string $tableName;
    private array $tableScheme;


    const DISABLE_TRHOW_EXCEPTION = true;
    const READ_RETURN_ALWAYS_ARRAY = true;


    /**
     * @param Database $database
     * @param string $tableName
     * 
     */
    public function __construct(Database $database, string $tableName /* , stdClass $model = null */)
    {
        $this->database = $database;
        $this->tableName = $tableName;

        try {
            $scheme = $this->database->getTableScheme($this->tableName);
            $this->tableScheme = $scheme;
        } catch (\Throwable $th) {
            if ($th->getCode() === 404) {
                 throw new Exception($th->getMessage(), $th->getCode());
            } else {
                throw new Exception($th->getMessage(), ($th->getCode() === null ? 500 : $th->getCode()));
            }
        }
    }

    /**
     * This function allow to insert a record in the database and return the id.
     *
     * @param array $requestBody -> the body request according to table scheme
     * 
     * @return int
     * 
     * @throws Exception
     * 
     * 
     */
    public function create(array $requestBody)
    {
        if (DataHelper::checkIfSomeParametersInBody($this->tableScheme, $requestBody)) {
            $transactionBeginHere = false;
            $fields = "";
            $values = "";
            $keys = array_keys($requestBody);
            foreach ($keys as $value) {
                if (in_array($value, $this->tableScheme)) {
                    $fields .= $value . ",";
                    $values .= ":" . $value . ",";
                }
            }

            $fields = rtrim($fields, ",");
            $values = rtrim($values, ",");
            $query = "INSERT INTO {$this->tableName} ({$fields}) VALUES ({$values})";
            if(!$this->database->inTransaction()){
                $this->database->beginTransaction();
                $transactionBeginHere = true;
            }
            try {
                $result = $this->database->ExecQuery($query, $requestBody);
                if ($result->rowCount() > 0) {
                    $query = "SELECT LAST_INSERT_ID() id";
                    $result = $this->database->ExecQuery($query);
                }
                if($transactionBeginHere){
                    $this->database->commit();
                }
            } catch (\Throwable $th) {
                if($this->database->inTransaction()){
                    $this->database->rollBack();
                }
                throw new Exception($th->getMessage(), 500);
            }

            return intval($result->fetch(PDO::FETCH_ASSOC)["id"]);
        }
        throw new Exception("Incoerent request body.", 400);
    }


    /**
     * Delete one (or more) row from the table and return the number of deleted row. 
     * 
     * @param string $rowIdenfingCondition = "field1 = :field1 AND field2 <> :field2" //use this scheme to build the row identifing condition
     * @param array $rowIdenfingConditionValues = ["field1" => $value1, "field2" => $value2] //this array is very important to identify the row
     * @param array $options, this parameter is optional and is used to configure the function behavior. Accept an array with two possibile value:  DBTable::DISABLE_TRHOW_EXCEPTION and this option disable the exception throwing.
     * 
     * @return int
     * 
     * @throws Exception
     */
    public function delete(string $rowIdenfingCondition, array $rowIdenfingConditionValues, array $options = [self::DISABLE_TRHOW_EXCEPTION])
    {
        //checking row's existency and unicity
        $checkIfRowExistQeury = " SELECT * FROM {$this->tableName} WHERE {$rowIdenfingCondition}";
        $checkResult = $this->database->ExecQuery($checkIfRowExistQeury, $rowIdenfingConditionValues);

        if ($checkResult->rowCount() === 0 && !in_array(self::DISABLE_TRHOW_EXCEPTION, $options)) {
            throw new Exception("Row in {$this->tableName} table not found.", 404);
        }
        else if ($checkResult->rowCount() === 0){
            return 0;
        }

        if ($checkResult->rowCount() > 1) {
            trigger_error("Warning, you are deleting more than a row.");
        }

        $query = "DELETE FROM {$this->tableName} WHERE {$rowIdenfingCondition} ";
        $deleteResult = $this->database->ExecQuery($query, $rowIdenfingConditionValues);

        return $deleteResult->rowCount();
    }

    /**
     * Get one (or more) row from the table and return the result
     *
     * @param string $rowIdenfingCondition = "field1 = :field1 AND field2 <> :field2" //use this scheme to build the row identifing condition, can be omitted
     * @param array $rowIdenfingConditionValues = ["field1" => $value1, "field2" => $value2] //this array is very important to identify the row, can't be omitted if rowIdentifingCondition is present
     * @param array $options, this parameter is optional and is used to configure the function behavior. Accept an array with two possibile value:  DBTable::DISABLE_TRHOW_EXCEPTION, DBTable::RETURN_ALWAIS_ARRAY. The first option disable the exception throwing, the second option allow to return an array also if the function find only a row.
     * 
     * @return array
     * 
     */
    public function read(string $rowIdenfingCondition = "", 
                         array $rowIdenfingConditionValues = [], 
                         array $options = [self::DISABLE_TRHOW_EXCEPTION, self::READ_RETURN_ALWAYS_ARRAY],
                         string $modelClassName = "") : array | object
    {

        $query = "SELECT * FROM {$this->tableName} " . ($rowIdenfingCondition !== "" ? " WHERE " : "") . " " . $rowIdenfingCondition;
        $result = $this->database->ExecQuery($query, $rowIdenfingConditionValues);
        if ($result->rowCount() === 0 && !in_array(self::DISABLE_TRHOW_EXCEPTION, $options)) {
            throw new Exception("Any row found.", 404);
        }
        $data = [];
        if ($result->rowCount() === 1 && !in_array(self::READ_RETURN_ALWAYS_ARRAY, $options)) {
            $data = $result->fetch(PDO::FETCH_ASSOC);
        } else {
            $data = $result->fetchAll(PDO::FETCH_ASSOC);
        }

        return $data;
    }

    /**
     * Update one (or more) row from the table and return the number of updated row. 
     * 
     * @param string $rowIdenfingCondition = "field1 = :field1 AND field2 <> :field2" //use this scheme to build the row identifing condition,
     * @param array $rowIdenfingConditionValues = ["field1" => $value1, "field2" => $value2] //this array is very important to identify the row, can't be omitted if rowIdentifingCondition is present
     * @param array $body = ["field1" => updValue1, "field2" => updValue2] //fields (with values) to be updated in the selected row(s).
     * @throws Exception
     * @return int
     */
    public function update(string $rowIdenfingCondition, array $rowIdenfingConditionValues, array $body)
    {

        //removing unwanted keys, only existent fields
        $intruderFound = false;
        $fields = "";
        $tableScheme = $this->getTableScheme();
        foreach ($body as $field => $value) {
            if (in_array($field, $tableScheme)) {
                //modifing the key for avoiding problems with rowIdenfingConditionValues
                $fields .= $field . " = :b_{$field} ,";
                $body["b_" . $field] = $value;
                unset($body[$field]);
            } else {
                $intruderFound = true;
                unset($body[$field]);
            }
        }

        if ($intruderFound) {
            trigger_error("Warning, check your request body, there are not in scheme fields.");
        }

        $fields = rtrim($fields, ",");

        $params = array_merge($rowIdenfingConditionValues, $body);

        $query = "UPDATE {$this->tableName} SET {$fields} " . ($rowIdenfingCondition !== "" ? " WHERE " : "") . $rowIdenfingCondition;

        $updateResult = $this->database->ExecQuery($query, $params);

        return $updateResult->rowCount();
    }

    /**
     * Return the table scheme.
     *
     * @return array
     * 
     */
    public function getTableScheme()
    {
        return $this->tableScheme;
    }
}