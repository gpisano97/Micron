<?php
use core\DataHelper\DataHelper;
use core\JWT;
use core\MiddlewareConfiguration;

require_once 'micron/core/MiddlewareConfiguration.php';
require_once 'micron/core/JWT/JWT.php';
require_once 'micron/core/DataHelper/DataHelper.php';

class JWTControl
{
    private $acceptedTypes =["boolean", "integer", "double", "string"];

    public function __construct(MiddlewareConfiguration $config, &$token){
        $token = DataHelper::getToken();
        if ($config->getTokenControl()) {
            if (empty($token)) {
                throw new Exception("Missing auth token.", 400);
            }
            $token = JWT::decode($token);

            $tokenAuthorizedParams = $config->getTokenBodyAuthorizedValues(); 
            if (count($tokenAuthorizedParams) > 0) {

                foreach ($tokenAuthorizedParams as $tokenBodyParam => $checkingValue) {
                    if (!isset($token->getBody()[$tokenBodyParam])) {
                        throw new Exception("Bad middleware's TOKEN_AUTH config: param {$tokenBodyParam} is not in Token Body.", 500);
                    }
                    if(!in_array(gettype($token->getBody()[$tokenBodyParam]), $this->acceptedTypes) ){
                        throw new Exception("the tokenBodyAuthorizedValues argument must be an array.", 500);
                    }
                    if (!in_array($token->getBody()[$tokenBodyParam], $checkingValue)) {
                        throw new Exception("Insufficent permissions.", 401);
                    }
                }
            }
        }
    }  
}
