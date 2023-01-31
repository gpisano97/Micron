<?php
namespace helper;

require_once 'ParamKey.php';
require_once 'Node.php';


/**
 *
 * @author Girolamo Dario Pisano
 *        
 */
class DataHelper
{

    /**
     */
    public function __construct()
    {}
    
    public static function postGetBody(){
        return json_decode(file_get_contents("php://input"), true);
    }
    
    public static function getUrlEncodedBody($resultLikeObject = false){
        $data = null;
        parse_str(file_get_contents("php://input"), $data);
        if(!$resultLikeObject){
            return $data;
        }
        else{
            $data = (object)$data;
            return $data;
        }
    }
    
    public static function getToken(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        $headers = str_replace("Bearer ", "", $headers);
        return $headers;
    }
    

    /**
     * @param array $keys
     * @param array $requestBody
     * @throws \Exception
     * @return boolean
     * <br />
     * 
     * This function return "true" if the params in $keys array match the $requestBody, "false" otherwise <br />
     * Throw a PHP Exception if $keys items are not instance of ParamKey class.
     */
    public static function checkParameters(array $keys, array $requestBody){ 
        if(count($keys) === 0 && count($requestBody) === 0){
            return true;
        }
        foreach ($keys as $key) {
            if($key instanceof ParamKey){
                if(!isset($requestBody[$key->key])){
                   return false; 
                }
                else if($key->isNullable && $requestBody[$key->key] !== null){
                    return false;
                }
                else if($key->toBeFull && $requestBody[$key->key] === ""){
                    return false;
                }
            }
            else{
                throw new \Exception('$keys array items must be instance of ParamKey class.');
            }
        }
       return true;
    }

    
    
    private function makeTree($adjacency_list, $index = 0, $id_key = "id", $parent_id_key = "parent_id", $depth = -1){
        $nodeDepth = $depth + 1;
        $node = new \Node($adjacency_list[$index], $nodeDepth, ($index % 2 === 0 ? false : true));
        for($i = $index; $i < count($adjacency_list); $i++){
            if($node->node[$id_key] === $adjacency_list[$i][$parent_id_key]){
                $node->addChildren($this->makeTree($adjacency_list, $i, $id_key, $parent_id_key, $nodeDepth));
            }
        }
        return $node;
    }

    /**
     * Summary of convertAdjacencyListToNestedObject
     * @param array $adjacency_list
     * @param int $index
     * @param string $id_key
     * @param string $parent_id_key
     * @return \Node
     * 
     * Function that convert an Adjacency List Array (common for modelling nested structures in MySQL) into an Object.
     * Every item of the list become a Node Class Object. Every Node has a list of nested childrends.
     */
    public function convertAdjacencyListToNestedObject(array $adjacency_list, int $index = 0, string $id_key = "id", string $parent_id_key = "parent_id")
    {
        return $this->makeTree($adjacency_list, $index, $id_key, $parent_id_key);
    }

}

