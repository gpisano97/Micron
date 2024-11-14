<?php
namespace core\Database\DBConnectors;

require_once "micron/Micron.php";
use core\Database;

interface DbConnectorInterface {
    public function updateTable(array $columnsData, string $tableName, Database $connection) : void;

    public function createTable(array $columnsData, string $tableName, Database $connection): void;
}