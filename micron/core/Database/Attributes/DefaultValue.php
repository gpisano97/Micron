<?php
namespace core\Database\Attributes;
use Attribute;

#[Attribute]
final class DefaultValue
{
    public $value;
    public function __construct($value){
        $this->value = $value;
    }
}
