<?php

namespace core\Database;
use Exception;
class DatabaseException extends Exception {
    private string $messsage;
    private int $MySQLCode;
}