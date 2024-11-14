<?php

namespace core\Database\Attributes\ColumnTypes\Strings;
use Attribute;

#[Attribute]
final class Varchar
{
    public int $length = 50;
    const name = "VARCHAR";

    public function __construct(int $lenght){
        $this->length = $lenght;
    }
}
