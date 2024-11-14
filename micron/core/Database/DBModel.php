<?php
namespace core\Database;

use core\Database;
use core\Database\Attributes\AutoIncrement;
use core\Database\Attributes\ColumnTypes\Numbers\BigInt;
use core\Database\Attributes\ColumnTypes\Numbers\DbDouble;
use core\Database\Attributes\ColumnTypes\Numbers\DbInt;
use core\Database\Attributes\ColumnTypes\Numbers\DbFloat;
use core\Database\Attributes\ColumnTypes\Strings\Text;
use core\Database\Attributes\ColumnTypes\Strings\Varchar;
use core\Database\Attributes\CreateIfNotExist;
use core\Database\Attributes\DefaultValue;
use core\Database\Attributes\PrimaryKey;
use core\Database\Attributes\Table;
use core\Database\Attributes\TableField;
use core\Database\DBConnectors\DbConnectorInterface;
use core\Database\DBConnectors\MySQLDbConnector;
use core\Database\Types\PropertyInfo;

use core\Database\Types\VoidField;
use Exception;
use PDO;
use ReflectionClass;
use ReflectionProperty;

require_once "micron/core/Database/DBConnectors/DbConnectorInterface.php";
require_once "micron/core/Database/DBConnectors/MySQLDbConnector.php";
include_once "Database.php";

class PrimaryKeysCondition
{
    public $condition = "";
    public $data = [];

    public function __construct(string $condition, array $data)
    {
        $this->condition = $condition;
        $this->data = $data;
    }
}

class DBModel
{
    private $tableName = "";
    private Database $db;

    private DbConnectorInterface | null $dbConnector;

    private $fieldsToIgnore = [];
    private $primaryKeys = []; 
    private $autoIncrements = [];
    private $properties = []; //contains the class properties. Every entry is an object of PropertyInfo (Reflection Property, attributes, attributes names). This is an associative array, the key is the property name.
    private $propertiesKeys = []; //this array contains the propertyNames, every entry allows to access the properties array.
    private ReflectionClass $reflectionClass; //the reflection of the class. In usecase contains the child class.
    private array $columnTypes = [DbInt::class, DbFloat::class, Varchar::class, BigInt::class, DbDouble::class, Text::class]; //helper array containing all allowed types 

    /**
     * Create an Object mapped representation of a database table
     * @throws \Exception
     */
    public function __construct()
    {
        //initialize the ReflectionClass
        $this->reflectionClass = new ReflectionClass($this::class);

        //check the Table attribute
        $this->checkTableName();

        //create the properties array, this array will contains all the class properties
        $this->properties = [];
        foreach ($this->reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {

            $propertyInfo = new PropertyInfo($property, $property->getAttributes());
            $propertyInfo->attributeNames = array_map(fn($attribute) => $attribute->getName(), $propertyInfo->attributes);
            if (in_array(TableField::class, $propertyInfo->attributeNames)) {
                $this->properties[$property->name] = $propertyInfo;
                array_push($this->propertiesKeys, $property->name);
            }

        }

        for ($i = 0; $i < count($this->propertiesKeys); $i++) {

            $field = $this->propertiesKeys[$i];

            $reflectionProperty = $this->properties[$field]->reflection;
            $propertyAttributes = $this->properties[$field]->attributes;
            $propertyAttributeNames = $this->properties[$field]->attributeNames;

            if (!$reflectionProperty->isInitialized($this)) {
                $defaultValueIndex = array_search(DefaultValue::class, $propertyAttributeNames);
                if (is_int($defaultValueIndex)) {
                    $value = $propertyAttributes[$defaultValueIndex]->getArguments();
                    $this->{$field} = $value[0];
                }
            }


            if (in_array(AutoIncrement::class, $propertyAttributeNames)) {
                $type = $reflectionProperty->getType();
                $hasType = $reflectionProperty->hasType();
                if ($hasType && $type->getName() !== "int") {
                    throw new Exception("Auto increment property must be of type integer.");
                }
                $this->autoIncrements[$field] = 1;
            }

            if (in_array(PrimaryKey::class, $propertyAttributeNames)) {
                $this->primaryKeys[$field] = 1;
            }
        }

        //here put the manageTableOnDatabase function
        $this->db = new Database();

        switch ($this->db->getDbType()) {
            case 'mysql':
                $this->dbConnector = new MySQLDbConnector();
                break;
            default:
                $this->dbConnector = null;
                break;
        }
        //check if the table exist and check for schema modification (in standby for now)
        $this->manageTableOnDatabase();
    }

    /**
     * Get the current Database object
     * 
     * @return \core\Database
     */
    final public function getDatabase(): Database
    {
        return $this->db;
    }

    /**
     * Check if the Table attribute is setted for the class
     * 
     * @throws \Exception
     * @return void
     */
    private function checkTableName(): void
    {
        if ($this->tableName === "") {
            $attribute = $this->reflectionClass->getAttributes(Table::class);
            $reflectionArguments = $attribute[0]->getArguments();
            if(isset($reflectionArguments["tableName"])){
                $this->tableName = $reflectionArguments["tableName"];    
            }
            else{
                $this->tableName = $reflectionArguments[0];
            }
            if ($this->tableName === "") {
                throw new Exception("Table attribute must be setted.");
            }
        }
    }

    private function convertPhpTypeToDbType(string $phpType)
    {
        switch ($phpType) {
            case 'integer':
                return BigInt::name;

            case 'double':
                return DbDouble::name;

            case 'string':
                return Text::name;

            case "float":
                return DbDouble::name;
        }
    }

    private function manageTableOnDatabase(): void
    {
        $attributes = $this->reflectionClass->getAttributes(CreateIfNotExist::class);
        
        $thereIsCreateIfNotExistAttribute = count($attributes) >= 1; 
        
        $tableExist = $this->db->ExecQuery("SHOW TABLES LIKE '{$this->tableName}'")->rowCount() !== 0;
        
        $mustCreateTable = $thereIsCreateIfNotExistAttribute; 
        //must implement "Update if different" mechanism, add an Attribute UpdateTableIfDifferent
        if ($mustCreateTable) {
            //create the table on DB according to public properties.
            $propertiesCount = count($this->propertiesKeys);
            $i = 0;
            $primaryKeys = [];
            $columnsData = [];
            $autoIncrementFound = false;
            while ($i < $propertiesCount) {
                //column datas
                $propertyName = $this->propertiesKeys[$i];
                $defaultValue = null;
                $columnType = null;
                $columnValueLength = null;
                $is_pk = false;
                $nullable = null;
                /****/

                $property = $this->properties[$propertyName]->reflection;
                $attributesNames = $this->properties[$propertyName]->attributeNames;


                //checking if is autoincrement.
                $isAutoincrement = is_int(array_search(AutoIncrement::class, $attributesNames));
                if ($isAutoincrement) {
                    $isPrimaryKey = is_int(array_search(PrimaryKey::class, $attributesNames));
                    if ($autoIncrementFound || !$isPrimaryKey) {
                        throw new Exception("You can define only one AutoIncrement column and must be PrimaryKey");
                    } else if (count($primaryKeys) > 0) {
                        throw new Exception("AutoIncrement column already setted, you cannot add other PrimaryKey columns");
                    }
                    $autoIncrementFound = true;
                    $nullable = false;
                    $is_pk = true;
                    $columnType = BigInt::name;
                    $columnValueLength = 20;
                }

                //checking if is a PrimaryKey
                if (!$is_pk) {
                    $primaryKeyIndex = array_search(PrimaryKey::class, $attributesNames);
                    if (is_int($primaryKeyIndex) && $autoIncrementFound === true) {
                        throw new Exception("AutoIncrement column already setted, you cannot add PrimaryKey columns");
                    } else if (is_int($primaryKeyIndex)) {
                        $is_pk = true;
                    }
                }

                if ($columnType === null) {
                    //checking explicit type
                    $columnType = array_values(array_intersect($attributesNames, $this->columnTypes));
                    if (count($columnType) > 1) {
                        throw new Exception("$propertyName must have only one 'type' attribute. " . count($columnType) . " found.");
                    } else if (count($columnType) === 1) {
                        $columnType = $columnType[0];
                        $typeAttributeIndex = array_search($columnType, $attributesNames);

                        $columnType = $columnType::name;
                        $value = $this->properties[$propertyName]->attributes[$typeAttributeIndex]->getArguments();
                        if (count($value) > 0) {
                            $columnValueLength = $value[0];
                            if(isset($value["length"])){
                                $columnValueLength = $value["length"];
                            }
                        }
                    } else {
                        $columnType = null;
                    }


                    //checking default value by attribute
                    $defaultAttributeIndex = array_search(DefaultValue::class, $attributesNames);
                    if (is_int($defaultAttributeIndex)) {
                        $value = $this->properties[$propertyName]->attributes[$defaultAttributeIndex]->getArguments();
                        $defaultValue = $value[0];
                        if(isset($value["value"])){
                            $defaultValue = $value["value"];
                        }
                        //type inference.
                        if ($columnType === null) {
                            $columnType = gettype($defaultValue);
                            $columnType = $this->convertPhpTypeToDbType($columnType);
                        }
                    }

                    //checking default value by property type
                    if ($defaultValue === null && $property->hasDefaultValue()) {
                        $defaultValue = $property->getDefaultValue();
                        if ($columnType === null) {
                            $columnType = gettype($defaultValue);
                            $columnType = $this->convertPhpTypeToDbType($columnType);
                        }
                    }

                    //checking column type by property type
                    if($columnType === null){
                        $propertyType = $property->getType();
                        if($propertyType !== null){
                            $columnType = $this->convertPhpTypeToDbType($propertyType->getName());
                        }
                    }

                    if ($columnType === null) {
                        throw new Exception("$propertyName must have a type in order to be created. Set a type through: attribute, DefaultValue attribute, property default value or property type. Otherwise you need to manually create your table.");
                    }
                }


                if ($nullable === null) {
                    $nullable = $defaultValue === null;
                }


                $columnsData[$propertyName] = [
                    "name" => $propertyName,
                    "type" => strtolower($columnType),
                    "length" => $columnValueLength,
                    "is_autoincrement" => $isAutoincrement,
                    "is_pk" => $is_pk || $isAutoincrement,
                    "is_nullable" => $nullable,
                    "default_value" => $defaultValue
                ];
                $i++;
            }

            if($tableExist){
                $this->dbConnector->updateTable($columnsData, $this->tableName, $this->db);
            }
            else{
                $this->dbConnector->createTable($columnsData, $this->tableName, $this->db);
            }
            
        }
    }

    private function getNewInstanceFromArray($array)
    {
        $className = $this::class;
        $obj = new $className();
        $pkIndex = 0;
        $pkCount = count($this->propertiesKeys);
        while ($pkIndex < $pkCount) {
            $propertyName = $this->propertiesKeys[$pkIndex];

            if (array_key_exists($propertyName, $array)) {
                $obj->$propertyName = $array[$propertyName];
            }

            $pkIndex++;
        }

        return $obj;
    }

    private function getConditionByPrimaryKeys(): PrimaryKeysCondition
    {
        $primaryKeysCount = count($this->primaryKeys);
        $pIndex = 0;
        $primaryKeysFields = array_keys($this->primaryKeys);
        $conditions = [];
        $queryData = [];
        while ($pIndex < $primaryKeysCount) {
            $reflectionProperty = $this->properties[$primaryKeysFields[$pIndex]]->reflection;

            if ($reflectionProperty->isInitialized($this)) {
                array_push($conditions, "{$primaryKeysFields[$pIndex]} = :{$primaryKeysFields[$pIndex]}");
                $queryData[$primaryKeysFields[$pIndex]] = $reflectionProperty->getValue($this);
            }
            $pIndex++;
        }
        $condition = implode(" AND ", $conditions);
        return new PrimaryKeysCondition($condition, $queryData);
    }

    final public function ignoreFields(array $fields)
    {
        for ($i = 0; $i < count($fields); $i++) {
            $this->fieldsToIgnore[$fields[$i]] = 1;
        }
        return $this;
    }

    /**
     * Clear the class public properties to the default value.
     * 
     * @return static
     */
    final public function clear()
    {
        $i = 0;
        while ($i < count($this->propertiesKeys)) {
            $property = $this->properties[$this->propertiesKeys[$i]];
            $index = array_search(DefaultValue::class, $property->attributeNames);

            if (is_int($index)) {
                $property->reflection->setValue($this, $property->attributes[$index]->getArguments()[0]);
            } else if ($property->reflection->hasDefaultValue()) {
                $property->reflection->setValue($this, $property->reflection->getDefaultValue());
            } else {
                unset($this->{$property->reflection->getName()});
            }
            $i++;
        }

        return $this;
    }

    /**
     * Insert a new row on the database table based on model's properties values.
     * Concatenable with the others DBModel's methods.
     * @return static
     */
    final public function insert()
    {
        $fieldsToInsert = [];

        //ignoring fields
        $fieldsToInsert = array_filter($this->propertiesKeys, fn($item) => !isset ($this->fieldsToIgnore[$item]));

        $into = "";
        $values = "";
        $data = [];
        $index = 0;

        while ($index < count($fieldsToInsert)) {
            $property = $fieldsToInsert[$index];
            if (!isset($this->autoIncrements[$property])) {
                $into .= $property . ",";
                $values .= ":" . $property . ",";
                if(is_bool($this->$property)){
                    $data[$property] = $data[$property] ? 1 : 0;
                }
                else {
                    $data[$property] = $this->$property;    
                }
            }
            $index++;
        }

        $into = substr_replace($into, "", -1);
        $values = substr_replace($values, "", -1);

        $query = "INSERT INTO {$this->tableName} ($into)
                  VALUES ($values)";

        $insertResult = $this->db->ExecQuery($query, $data);

        $keysOfPrimaryKeys = array_keys($this->primaryKeys);
        if (count($this->primaryKeys) === 1 && isset($this->autoIncrements[$keysOfPrimaryKeys[0]])) {
            $this->{$keysOfPrimaryKeys[0]} = intval($this->db->lastInsertId());
        }

        $this->fieldsToIgnore = [];
        return $this;
    }

    /**
     * Reads from the database table according to the "PrimaryKey" properties values
     * Concatenable with the others DBModel's methods.
     * @return static
     */
    final public function read()
    {
        $condition = "";
        $data = [];
        $i = 0;
        $primary = array_keys($this->primaryKeys);
        $primaryCount = count($primary);

        //checking if the class had a PrimaryKey property.
        if ($primaryCount === 0) {
            throw new Exception("PrimaryKey attribute for at least one property required. Use readAll, with a condition, instead.", 500);
        }
        while ($i < $primaryCount) {
            $condition .= "AND {$primary[$i]} = :{$primary[$i]}";
            $data[$primary[$i]] = $this->properties[$primary[$i]]->reflection->getValue($this);
            $i++;
        }

        $query = "SELECT *
                 FROM {$this->tableName}
                 WHERE 1=1 $condition";

        $result = $this->db->ExecQuery($query, $data);

        //throwing exception on fetch data failure.
        if($result->rowCount() === 0){
            throw new Exception("Data not found.", 404);
        }
        $data = $result->fetch(PDO::FETCH_ASSOC);

        //filling properties with DB data
        $i = 0;
        while ($i < count($this->propertiesKeys)) {
            $property = $this->propertiesKeys[$i];
            if (array_key_exists($property, $data)) {
                $this->$property = $data[$property];
            } else {
                unset($this->$property);
            }
            $i++;
        }

        return $this;
    }

    /**
     * Reads from the database table.
     * @param mixed $condition = "" searching condition in SQL style. E.g. 'id = :id AND date > :dt'
     * @param mixed $data = [] condition datas. E.g. ['id' => $id, 'dt' => $dt]
     * @return array
     */
    final public function readAll(string $condition = "", array $data = []): array
    {
        $query = "SELECT *
                 FROM {$this->tableName}";
        if ($condition !== "") {
            $query .= " WHERE $condition";
        }

        $result = $this->db->ExecQuery($query, $data);
        $data = $result->fetchAll(PDO::FETCH_ASSOC);

        $index = 0;
        $dataCount = count($data);

        while ($index < $dataCount) {
            $data[$index] = $this->getNewInstanceFromArray($data[$index]);
            $index++;
        }

        return $data;
    }

    /**
     * Delete data from database table. If "condition" argument is not set, the function will use the class PrimaryKey properties.
     * If "condition" argument is set, the function will use "condition" and "data" in order to perform the elimination.
     * 
     * @param string $condition = "" searching condition in SQL style. E.g. 'id = :id AND date > :dt'
     * @param array $data = [] condition datas. E.g. ['id' => $id, 'dt' => $dt]
     * @throws \Exception
     * @return void
     */
    final public function delete(string $condition = "", array $data = []): void
    {
        $query = "DELETE FROM {$this->tableName} WHERE ";
        $primaryKeysCount = count($this->primaryKeys);
        if ($primaryKeysCount === 0 && $condition === "") {
            throw new Exception("You must add at least one PrimaryKey property or set a condition in order to perform this operation.");
        }
        $queryData = [];
        if ($primaryKeysCount > 0 && $condition === "") {
            $conditionByPKs = $this->getConditionByPrimaryKeys();

            if ($conditionByPKs->condition === "") {
                throw new Exception("Delete error. Your Primary Keys attributes are not initialized and 'condition argument' is not setted.");
            }

            $query .= $conditionByPKs->condition;
            $queryData = $conditionByPKs->data;
        } else {
            $queryData = $data;
            $query .= $condition;
        }

        $this->clear();

        $this->db->ExecQuery($query, $queryData);

    }

    /**
     * Delete all datas from a Database Table.
     * 
     * @return \core\Database\DBModel
     */
    final public function emptyTable(): DBModel
    {
        $query = "DELETE FROM {$this->tableName}";
        $this->db->ExecQuery($query, []);
        $this->clear();
        return $this;
    }

    final public function update(string $condition = "", array $conditionData = [], array $newValues = [])
    {
        $query = "UPDATE {$this->tableName} SET ";
        $primaryKeysCount = count($this->primaryKeys);
        if($primaryKeysCount === 0 && $condition === ""){
            throw new Exception("You must add at least one PrimaryKey property or set a condition in order to perform this operation.");
        }
        $queryData = [];
        if($primaryKeysCount > 0 && $condition === ""){
            $conditionByPKs = $this->getConditionByPrimaryKeys();

            if($conditionByPKs->condition === ""){
                throw new Exception("Update error. Your Primary Keys attributes are not initialized and 'condition argument' is not setted.");
            }

            $condition .= $conditionByPKs->condition;
            $conditionData = $conditionByPKs->data;
        }

        $primaryKeys = array_keys($this->primaryKeys);

        $fieldToUpdate = array_values(array_diff($this->propertiesKeys, $primaryKeys));

        $propertyIndex = 0;
        while($propertyIndex < count($fieldToUpdate)){
            $fieldName = $fieldToUpdate[$propertyIndex]; 
            $query .=  "$fieldName= :$fieldName,";
            $queryData[$fieldName] = $this->$fieldName;
            $propertyIndex++;
        }

        $query = substr($query, 0, strlen($query) - 1);
        $query .= " WHERE $condition";
        $queryData = array_merge($queryData, $conditionData);

        $this->db->ExecQuery($query, $queryData);
        return $this;
    }
}
