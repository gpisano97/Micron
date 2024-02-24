<?php
namespace core\DataHelper;
enum UriParamType {
    case number;
    case string;
} 


class UriParam
{
    private string $URIKey = "";
    public string $paramName = "";
    public UriParamType $type;
    function __construct(string $paramName, UriParamType $type){
        $paramType = "";
        switch ($type) {
            case UriParamType::number :
                $paramType = "numeric";
                break;
            case UriParamType::string :
                $paramType = "string";
                break;
        }

        if($paramType === ""){
            throw new \Exception("Incorrect Uri param type: must be an UriParamType enumerator value.");
        }
        $this->paramName = $paramName;
        $this->type = $type;
        $this->URIKey = "{".$paramName.':'.$paramType."}";
    }

    function __toString(){
        return $this->URIKey;
    }
}