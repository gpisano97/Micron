<?php
namespace core\Database\DBConnectors;

require_once "micron/Micron.php";
require_once "DbConnectorInterface.php";

use core\Database;
use core\Database\DBConnectors\DbConnectorInterface;
use PDO;


final class MySQLDbConnector implements DbConnectorInterface
{
    public function updateTable(array $columnsData, string $tableName, Database $connection): void
    {
        $resultRemoteColumns = $connection->ExecQuery("SHOW FIELDS FROM {$tableName}");
        $remoteColumns = $resultRemoteColumns->fetchAll(PDO::FETCH_ASSOC);
        $columnsMustChange = [];
        foreach ($remoteColumns as $remoteColumn) {
            $remoteColumnName = $remoteColumn["Field"];
            $remoteType = $remoteColumn["Type"];
            $remoteIsNullable = $remoteColumn["Null"] === "YES";
            $remoteIsPrimaryKey = $remoteColumn["Key"] === "PRI";
            $remoteDefaultValue = $remoteColumn["Default"];

            if ($remoteDefaultValue === "''") {
                $remoteDefaultValue = "";
            }
            $remoteIsAutoIncrement = str_contains($remoteColumn["Extra"], "auto_increment");

            if (!isset($columnsData[$remoteColumnName])) {
                $columnsMustChange[$remoteColumnName] = [
                    "name" => $remoteColumnName,
                    "operation" => "remove"
                ];
            } else {
                $classColumn = $columnsData[$remoteColumnName];

                $mustChange = false;

                if (str_contains($remoteType, $classColumn["type"])) {
                    $replaces = 0;
                    $length = str_replace($classColumn["type"] . "(", "", $remoteType, count: $replaces);
                    $length = str_replace(")", "", $length);

                    if ($replaces > 0 && $length !== "" && $length != $classColumn["length"]) {
                        $mustChange = true;
                    }
                } else {
                    $mustChange = true;
                }

                if ($remoteIsAutoIncrement !== $classColumn["is_autoincrement"]) {
                    $mustChange = true;
                }

                if ($remoteDefaultValue !== $classColumn["default_value"]) {
                    $mustChange = true;
                }

                if ($remoteIsPrimaryKey !== $classColumn["is_pk"]) {
                    $mustChange = true;
                }

                if ($remoteIsNullable !== $classColumn["is_nullable"]) {
                    $mustChange = true;
                }

                if ($mustChange) {
                    $columnsMustChange[$remoteColumnName] = [
                        "name" => $remoteColumnName,
                        "operation" => "update"
                    ];
                }

                $columnsData[$remoteColumnName]["found"] = true;
            }
        }

        foreach ($columnsData as $columnData) {
            if(!isset($columnData["found"])){
                $columnsMustChange[$columnData["name"]] = [
                    "name" => $columnData["name"],
                    "operation" => "add"
                ];
            }
        }

        if (count($columnsMustChange) > 0) {

            $alterSql = "ALTER TABLE `{$tableName}` ";

            foreach ($columnsMustChange as $columnToChange) {
                if($columnToChange["operation"] === "update" || $columnToChange["operation"] === "add"){
                    $columnData = $columnsData[$columnToChange["name"]];
                    if($columnToChange["operation"] === "add"){
                        $alterSql .= "ADD COLUMN "; 
                    }
                    else{
                        $alterSql .= "CHANGE COLUMN `{$columnToChange["name"]}` "; 
                    }
                    
                    $alterSql .= "`{$columnToChange["name"]}` ";
                    
                    $alterSql .= "{$columnData["type"]}";
                    if ($columnData["length"] !== null) {
                        $alterSql .= "({$columnData["length"]})";
                    }
                    if ($columnData["is_nullable"]) {
                        $alterSql .= " NULL";
                    } else {
                        $alterSql .= " NOT NULL";
                    }
        
                    if ($columnData["is_autoincrement"]) {
                        $alterSql .= " AUTO_INCREMENT";
                    }
                    if ($columnData["default_value"] !== null) {
                        $alterSql .= " DEFAULT '{$columnData["default_value"]}'";
                    }
                    $alterSql .= ",";
                }
                else{
                    $alterSql .= "DROP COLUMN `{$columnData["name"]}`";
                }
                
            }
            $alterSql[strlen($alterSql) - 1] = ";"; 
            $connection->ExecQuery($alterSql);
        }
    }

    public function createTable(array $columnsData, string $tableName, Database $connection): void
    {

        $primaryKeys = [];
        $columnSQL = "";
        foreach ($columnsData as $column) {
            if ($column["is_pk"]) {
                $primaryKeys[] = $column;
            }

            $columnSQL .= "`{$column["name"]}` {$column["type"]}";
            if ($column["length"] !== null) {
                $columnSQL .= "({$column["length"]})";
            }
            if ($column["is_nullable"]) {
                $columnSQL .= " NULL";
            } else {
                $columnSQL .= " NOT NULL";
            }

            if ($column["is_autoincrement"]) {
                $columnSQL .= " AUTO_INCREMENT";
            }
            if ($column["default_value"] !== null) {
                $columnSQL .= " DEFAULT '{$column["default_value"]}'";
            }
            $columnSQL .= ",";
        }

        if(count($primaryKeys) === 0){
            $columns = substr($columnSQL, 0, strlen($columnSQL) - 1);
        }

        $primaryKeysSQL = implode(array_map(function ($item) {
            return "`{$item["name"]}`";
        }, $primaryKeys));
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
        $columnSQL
        ".(count($primaryKeys) > 0 ? "PRIMARY KEY ($primaryKeysSQL)" : "")."
        )COLLATE='utf8mb4_general_ci'";

        $connection->ExecQuery($sql);
    }
}
