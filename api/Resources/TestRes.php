<?php

use core\Database\Attributes\AutoIncrement;
use core\Database\Attributes\ColumnTypes\Numbers\BigInt;
use core\Database\Attributes\ColumnTypes\Strings\Varchar;
use core\Database\Attributes\CreateIfNotExist;
use core\Database\Attributes\PrimaryKey;
use core\Database\Attributes\Reference;
use core\Database\Attributes\Table;
use core\Database\Attributes\TableField;
use core\Database\DBModel;
use core\MiddlewareConfiguration;
use core\Resource;

require_once "micron/Micron.php";

#[Table("test1")]
#[CreateIfNotExist]
final class Test1 extends DBModel {
    #[TableField]
    #[AutoIncrement]
    #[PrimaryKey]
    public $test1_id;

    #[TableField]
    #[Varchar(100)]
    public $description;
}

#[Table("test2")]
#[CreateIfNotExist]
final class Test2 extends DBModel {
    #[TableField]
    #[AutoIncrement]
    #[PrimaryKey]
    public $test2_id;

    #[TableField]
   /*  #[Reference("test1", ["test1_id"])] */
    #[BigInt()]
    public $test1_id;

    #[TableField]
    #[Varchar(50)]
    public $description;
}

final class TestRes implements Resource
{
    public function listen(Route $router): void
    {
        $router->get(
            "test",
            function (Request $request) {
                new Test1();
                new Test2();
            },
            middlewareSettings: MiddlewareConfiguration::getConfiguration(tokenControl: false)
        );
    }
}
