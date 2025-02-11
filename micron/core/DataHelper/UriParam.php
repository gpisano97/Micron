<?php
namespace core\DataHelper;
enum UriParamType {
    case number;
    case string;
    case none;
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
            case UriParamType::none :
                $paramType = "none";
                break;
        }

        if($paramType === ""){
            throw new \Exception("Incorrect Uri param type: must be an UriParamType enumerator value.");
        }
        $this->paramName = $paramName;
        $this->type = $type;
        if($paramType !== "none")
            $this->URIKey = "{".$paramName.':'.$paramType."}";
        else
            $this->URIKey = "{".$paramName."}";
    }

    function __toString(){
        return $this->URIKey;
    }
}