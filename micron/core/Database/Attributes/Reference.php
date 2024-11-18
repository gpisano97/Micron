<?php
namespace core\Database\Attributes;
use Attribute;

enum ReferenceActions : string {
    case no_action = "NO ACTION";
    case restrict = "RESTICT";
    case cascade = "CASCADE";
    case set_null = "SET NULL";
}

#[Attribute]
final class Reference
{
    public string $tableReference;
    public array $columnReferences;

    public ReferenceActions $onUpdate;
    public ReferenceActions $onDelete;

    public function __construct(string $tableReference, array $columnReferences, ReferenceActions $onUpdate = ReferenceActions::cascade, ReferenceActions $onDelete = ReferenceActions::cascade){
        $this->columnReferences = $columnReferences;
        $this->tableReference = $tableReference;
        $this->onDelete = $onDelete;
        $this->onUpdate = $onUpdate;
    }
}