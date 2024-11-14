<?php
namespace core\Database\Types;
use ReflectionProperty;
final class PropertyInfo
{
    public ReflectionProperty $reflection;
    public array $attributes = [];
    public array $attributeNames = [];

    public function __construct($reflection = null, $attributes = [], $attributeNames = []){
        $this->reflection = $reflection;
        $this->attributes = $attributes;
        $this->attributeNames = $attributeNames;
    }

}
