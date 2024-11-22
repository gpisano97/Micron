<?php

namespace core\Database\Attributes\ColumnTypes\Numbers;
use Attribute;

#[Attribute]
final class BigInt
{
    public int $length = 20;
    const name = "BIGINT";

    public function __construct(int $length = 20){
        $this->length = $length;
    }
}
