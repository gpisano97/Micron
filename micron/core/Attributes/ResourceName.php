<?php

namespace core\Attributes;
use Attribute;

#[Attribute]
final class ResourceName
{
    public string $resourceName;

    public function __construct(string $resourceName){
        $this->resourceName = $resourceName;
    }
}
