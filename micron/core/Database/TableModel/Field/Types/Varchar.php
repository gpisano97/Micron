<?php

namespace core\Database\Field\Types;

class Varchar
{
    public int $size;

    /**
     * Create a VARCHAR type for DBTable creation fields.
     *
     * @param int $size = 50
     * 
     */
    public function __construct(int $size = 50){
        $this->size = $size;
    }
}
