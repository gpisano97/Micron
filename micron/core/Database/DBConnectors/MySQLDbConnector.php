<?php
namespace core\Database\DBConnectors;

require_once "micron/Micron.php";
require_once "DbConnectorInterface.php";

use core\Database;
use core\Database\DBConnectors\DbConnectorInterface;
use PDO;


final class MySQLDbConnector implements DbConnectorInterface
{
    private function getForeignKeyInfo(string $tableName, Database $connection): array
    {
        $query = "SHOW CREATE TABLE `$tableName`";
        $crTableResult = $connection->ExecQuery($query)->fetch(PDO::FETCH_ASSOC)["Create Table"];
        $crTableRows = explode("\n", $crTableResult);
        $crTableRows = array_splice($crTableRows, 1, count($crTableRows) - 2);

        $remoteColumnData = [];

        foreach ($crTableRows as $column) {
            if (str_contains($column, "FOREIGN KEY")) {
                $columnSplitted = explode(" ", trim($column));
                $columnName = substr($columnSplitted[4], 2, strlen($columnSplitted[4]) - 4);
                $tableReferenced = str_replace("`", "", $columnSplitted[6]);

                //this way supports more columns
                $columnRefrenced = substr($columnSplitted[7], 1, strlen($columnSplitted[7]) - 2);
                $columnRefrenced = str_replace("`", "", $columnRefrenced);
                $columnRefrenced = explode(",", $columnRefrenced);

                $onDeleteOperation = $columnSplitted[10];
                $onUpdateOperation = $columnSplitted[13];

                $referenceName = str_replace("`", "", $columnSplitted[1]);

                $remoteColumnData[$columnName] = [
                    "column_name" => $columnName,
                    "reference_name" => $referenceName,
                    "table_refrenced" => $tableReferenced,
                    "columns_referenced" => $columnRefrenced,
                    "on_delete" => $onDeleteOperation,
                    "on_update" => $onUpdateOperation
                ];
            }
        }

        return $remoteColumnData;
    }

    /**
     * Summary of manageTableExternalReference
     * @param mixed $tableName
     * @param \core\Database $connection
     * @param mixed $fksToRemove => ["referenceNames"]
     * @param mixed $fksToAdd => ["columnNames"]
     * @return void
     */
    private function manageTableExternalReference($tableName, Database $connection, $fksToRemove = [], $fksToAdd = [], $columnsData = [])
    {
        if (count($fksToRemove) > 0) {
            $query = "ALTER TABLE `$tableName` ";
            foreach ($fksToRemove as $fkToRemove) {
                $query .= "DROP FOREIGN KEY `{$fkToRemove["reference_name"]}`, ";
            }
            $query = substr($query, 0, strlen($query) - 2);
            $connection->ExecQuery($query);
        }

        if (count($fksToAdd) > 0) {
            $query = "ALTER TABLE `$tableName` ";
            foreach ($fksToAdd as $fkToAdd) {
                $columnData = $columnsData[$fkToAdd];
                if (isset($columnData["reference"]) && is_array($columnData["reference"]) && count($columnData["reference"]) > 0) {
                    $constraintName = "{$tableName}_refer_{$columnData["reference"]["table"]}";

                    $refCols = implode(array_map(function ($item) {
                        return "`$item`";
                    }, $columnData["reference"]["columns"]));

                    $onDelete = $columnData["reference"]["onDelete"];
                    $onUpdate = $columnData["reference"]["onUpdate"];
                    $query .= "ADD CONSTRAINT `$constraintName` FOREIGN KEY (`$fkToAdd`) REFERENCES `{$columnData["reference"]["table"]}` ($refCols) ON UPDATE $onUpdate ON DELETE $onDelete, ";
                }
            }

            $query = substr($query, 0, strlen($query) - 2);
            $connection->ExecQuery($query);
        }
    }
    public function updateTable(array $columnsData, string $tableName, Database $connection): void
    {
        $resultRemoteColumns = $connection->ExecQuery("SHOW FIELDS FROM {$tableName}");
        $remoteColumns = $resultRemoteColumns->fetchAll(PDO::FETCH_ASSOC);
        $columnsMustChange = [];
        $fksRemove = [];
        $fksAdd = [];
        $foreignKeyInfo = [];
        foreach ($remoteColumns as $remoteColumn) {
            $remoteColumnName = $remoteColumn["Field"];
            $remoteType = $remoteColumn["Type"];
            $remoteIsNullable = $remoteColumn["Null"] === "YES";
            $remoteIsPrimaryKey = $remoteColumn["Key"] === "PRI";
            $remoteIsForeignKey = $remoteColumn["Key"] === "MUL";
            $remoteDefaultValue = $remoteColumn["Default"];

            if ($remoteIsForeignKey && count($foreignKeyInfo) === 0) {
                $foreignKeyInfo = $this->getForeignKeyInfo($tableName, $connection);
            }

            if ($remoteDefaultValue === "''") {
                $remoteDefaultValue = "";
            }
            $remoteIsAutoIncrement = str_contains($remoteColumn["Extra"], "auto_increment");

            if (!isset($columnsData[$remoteColumnName])) {
                $columnsMustChange[$remoteColumnName] = [
                    "name" => $remoteColumnName,
                    "operation" => "remove"
                ];

                if($remoteIsForeignKey){
                    $fksRemove[] = $foreignKeyInfo[$remoteColumnName];
                }
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

                $mustRemoveFK = false;
                $mustChangeFK = false;
                if ($remoteIsForeignKey) {
                    if ($classColumn["reference"] === null || count($classColumn["reference"]) === 0) {
                        $mustRemoveFK = true;
                    } else {
                        $remoteForeignKey = null;
                        if(isset($foreignKeyInfo[$remoteColumnName]))
                        {
                            $remoteForeignKey = $foreignKeyInfo[$remoteColumnName];
                        }

                        if($remoteForeignKey === null){
                            $mustChangeFK = true;
                        }
                        if ($remoteForeignKey !== null) {

                            if ($remoteForeignKey["table_refrenced"] != $classColumn["reference"]["table"]) {
                                $mustChangeFK = true;
                            }
                            $sorting = function ($a, $b) {
                                if ($a < $b)
                                    return -1;
                                if ($b > $a)
                                    return 1;
                                return 0;
                            };

                            usort($remoteForeignKey["columns_referenced"], $sorting);
                            usort($classColumn["reference"]["columns"], $sorting);

                            if (implode($remoteForeignKey["columns_referenced"]) !== implode($classColumn["reference"]["columns"])) {
                                $mustChangeFK = true;
                            }
                            if ($remoteForeignKey["on_update"] !== $classColumn["reference"]["onUpdate"]) {
                                $mustChangeFK = true;
                            }
                            if ($remoteForeignKey["on_delete"] !== $classColumn["reference"]["onDelete"]) {
                                $mustChangeFK = true;
                            }
                        }
                    }
                    //in order to remove or updates column reference
                    if ($mustRemoveFK || ($mustChangeFK && $remoteForeignKey !== null)) {
                        $fksRemove[] = $remoteForeignKey;
                    }
                    //this means that the reference already exist on DB, but we can change it
                    if ($mustChangeFK) {
                        $fksAdd[] = $remoteColumnName;
                    }

                } //below if the reference not exists on the db but exist in the class.
                else if (is_array($classColumn["reference"]) && count($classColumn["reference"]) > 0) {
                    $fksAdd[] = $remoteColumnName;
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
            if (!isset($columnData["found"])) {
                $columnsMustChange[$columnData["name"]] = [
                    "name" => $columnData["name"],
                    "operation" => "add"
                ];
            }
        }

        if (count($columnsMustChange) > 0) {

            $alterSql = "ALTER TABLE `{$tableName}` ";

            foreach ($columnsMustChange as $columnToChange) {
                if ($columnToChange["operation"] === "update" || $columnToChange["operation"] === "add") {
                    $columnData = $columnsData[$columnToChange["name"]];
                    if ($columnToChange["operation"] === "add") {
                        $alterSql .= "ADD COLUMN ";
                    } else {
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
                } else if ($columnToChange["operation"] === "remove") {
                    $alterSql .= "DROP COLUMN `{$columnToChange["name"]}`,";
                }
            }
            $alterSql[strlen($alterSql) - 1] = ";";
            if(count($fksRemove) > 0){
                $this->manageTableExternalReference($tableName, $connection, $fksRemove, [], $columnsData);
            }
            $connection->ExecQuery($alterSql);
        }
        if (count($fksAdd) > 0) {
            try {
                $this->manageTableExternalReference($tableName, $connection, [], $fksAdd, $columnsData);
            } catch (\Throwable $th) {
                $query = "DROP TABLE `{$tableName}`";
                $connection->ExecQuery($query);
            }

        }

    }

    public function createTable(array $columnsData, string $tableName, Database $connection): void
    {
        $primaryKeys = [];
        $columnSQL = "";
        $fksToAdd = [];

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

            if (is_array($column["reference"]) && count($column["reference"]) > 0) {
                array_push($fksToAdd, $column["name"]);
            }
        }

        if (count($primaryKeys) === 0) {
            $columns = substr($columnSQL, 0, strlen($columnSQL) - 1);
        }

        $primaryKeysSQL = implode(array_map(function ($item) {
            return "`{$item["name"]}`";
        }, $primaryKeys));

        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
        $columnSQL
        " . (count($primaryKeys) > 0 ? "PRIMARY KEY ($primaryKeysSQL)" : "") . "
        )COLLATE='utf8mb4_general_ci'";

        $connection->ExecQuery($sql);
        if (count($fksToAdd) > 0) {
            $this->manageTableExternalReference($tableName, $connection, [], $fksToAdd, $columnsData);
        }
    }
}
