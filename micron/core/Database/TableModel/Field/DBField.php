<?php

namespace core\Database\Field;

use core\Database\Field\Types\Double;
use core\Database\Field\Types\Integer;
use core\Database\Field\Types\Varchar;

class DBField
{

    private Integer|Varchar|Double $type;
    private int $length;
    private string|int|float $default;
    private string $comment;
    private string $charset;
    private string $expression;
    private string $virtuality;


    public function __construct(Integer|Varchar|Double $type, int $length = 0, string|int|float $default = "", string $comment = "", string $charset = "", string $expression = "", string $virtuality = ""){
        $this->type = $type;
        $this->length = $length;
        $this->default = $default;
        $this->comment = $comment;
        $this->charset = $charset;
        $this->expression = $expression;
        $this->virtuality = $virtuality;
    }
}
