<?php

namespace core\Database\Attributes;
use Attribute;

#[Attribute]
final class Table
{
    public string $tableName;
    public function __construct($tableName){
        $this->tableName = $tableName;
    }
}
