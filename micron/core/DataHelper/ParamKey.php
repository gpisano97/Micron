<?php
namespace core\DataHelper;

/**
 *
 * @author Girolamo Dario Pisano
 *        
 */
class ParamKey {
    
    public $key = "";
    public $toBeFull = false;
    public $isNullable = false;
    
    /**
     * @param string $key
     * @param bool $toBeFull
     * @param bool $isNullable
     */
    public function __construct(string $key, bool $toBeFull = false, bool $isNullable = false){
        $this->key = $key;
        $this->toBeFull = $toBeFull;
        $this->isNullable = $isNullable;
    }
}
