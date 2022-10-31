<?php
namespace JWT;

include_once 'config.php';

define("TAMPER_TOKEN_MSG", "Given token is tamper.");
define("TAMPER_TOKEN_CODE", "9999");
define("TOKEN_EXPIRED_MSG", "Given token is expired");
define("TOKEN_EXPIRED_CODE", "0001");
define("TOKEN_IS_MISSING", "Missing token.");

/**
 *
 * @author Girolamo Dario Pisano
 *        
 */

function base64url_encode($obj) {
    return str_replace(['+','/','='], ['-','_',''], base64_encode($obj));
}

function base64url_decode($obj) {
    return base64_decode(str_replace(['-','_'], ['+','/'], $obj));
}
class JWT
{

    /**
     */
    private $header = array("alg" => "HS256", "typ" => "JWT");
    private $body = [];
    private $secret_key ="";
    private $signature = "";
    private $token = "";

    /**
     * @param array $body
     * @param string $secret_key
     * @param int $hours_before_expire
     * 
     * Generate token
     */
    public function __construct(array $body, string $secret_key = JWT_SECRET, int $hours_before_expire = 24){   
        $this->body = $body;
        $this->secret_key = $secret_key;
        $this->body["iat"] = time();
        $this->body["exp"] = $this->body["iat"] + ($hours_before_expire * 3600);
        $body_encoded = base64url_encode(json_encode($this->body));
        $header_encoded = base64url_encode(json_encode($this->header));
        $this->signature = hash_hmac("sha256", $header_encoded.".".$body_encoded, $this->secret_key);
        $this->token = $header_encoded.".".$body_encoded.".".base64url_encode($this->signature);
    }
    
    /**
     * @return string
     * 
     * Get generated token string.
     * 
     */
    public function getToken(){
        return $this->token;
    }
    
    /**
     * @return array
     * 
     * get token body like associative array
     */
    public function getBody(){
        return $this->body;
    }
   
    /**
     * @param string $token
     * @param string $secret_key
     * @throws \Exception
     * @return \JWT\JWT
     * 
     * Decode and validate given token
     * 
     * HTTP Code 400 if token is missing
     * HTTP Code 401 if is tamperized or expired.
     */
    public static function decode(string $token,string $secret_key = JWT_SECRET){
        if(JWT::verify($token, $secret_key)){
            $token_parts = explode(".", $token);
            return new JWT(json_decode(base64url_decode($token_parts[1]) ,true), $secret_key);
        }
    }
    
    /**
     * @param string $token
     * @param string $secret_key
     * @throws \Exception
     * @return boolean
     * 
     * Verify if a token is valid or not. Throw exceptions
     * HTTP Code 400 if token is missing
     * HTTP Code 401 if is tamperized or expired.
     *
     */
    public static function verify(string $token, string $secret_key = JWT_SECRET){
        if($token === ""){
            throw new \Exception(TOKEN_IS_MISSING, 400);
        }
        $token_parts = explode(".", $token);
        if(count($token_parts) === 3){
            //l'header ed il body sono in chiaro, la signature invece ha la chiave segreta quindi non si può replicare facilmente
            //devo prendere header e body e rigenerare la signature con la chiave segreta e poi verificare che la signature arrivata
            //e la signature calcolata siano uguali.
            $calculated_signature = base64url_encode(hash_hmac("sha256", $token_parts[0].".".$token_parts[1], $secret_key));
            if($calculated_signature === $token_parts[2]){
                $body = json_decode(base64url_decode($token_parts[1]), true);
                if($body["exp"] > time()){
                    return true;
                }
                else{
                    throw new \Exception(TOKEN_EXPIRED_MSG, 401);
                }
            }
            else{
                throw new \Exception(TAMPER_TOKEN_MSG, 401);
            }
        }
        else{
            throw new \Exception(TAMPER_TOKEN_MSG, 401);
        }
    }

}

