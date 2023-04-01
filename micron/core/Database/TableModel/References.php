<?php

namespace core\Database\Types;

class References
{
    public array $references;
    public array $onDelete;
    public array $onUpdate;
    /**
     * Create a References type for DBTable creation field
     *
     * @param array $references = ["table" => ["column1", "column2"]]
     * @param array $onDelete = ["table" => "CASCADE"]
     * @param array $onUpdate = ["table" => "CASCADE"]
     * 
     * 
     */
    public function __construct(array $references, array $onDelete, array $onUpdate){
        $this->references = $references;
        $this->onDelete = $onDelete;
        $this->onUpdate = $onUpdate;
    }
}
