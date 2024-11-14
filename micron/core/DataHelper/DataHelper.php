<?php
namespace core\DataHelper;

use core\Attributes\ResourceName;

require_once 'ParamKey.php';
require_once 'Node.php';
require_once 'UriParam.php';


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
    {
    }

    public static function postGetBody()
    {
        return json_decode(file_get_contents("php://input"), true);
    }

    public static function getUrlEncodedBody($resultLikeObject = false)
    {
        $data = null;
        parse_str(file_get_contents("php://input"), $data);
        if (!$resultLikeObject) {
            return $data;
        } else {
            $data = (object) $data;
            return $data;
        }
    }

    public static function getToken()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
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
        $headers ??= "";
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
    public static function checkParameters(array $keys, array $requestBody)
    {
        if (count($keys) === 0 && count($requestBody) === 0) {
            return true;
        }
        foreach ($keys as $key) {
            if ($key instanceof ParamKey) {
                if (!isset($requestBody[$key->key])) {
                    return false;
                } else if ($key->isNullable && $requestBody[$key->key] !== null) {
                    return false;
                } else if ($key->toBeFull && $requestBody[$key->key] === "") {
                    return false;
                }
            } else {
                throw new \Exception('$keys array items must be instance of ParamKey class.');
            }
        }
        return true;
    }

    public static function checkIfSomeParametersInBody(array $keys, array $requestBody)
    {
        if (is_array($requestBody)) {
            try {
                $dataKeys = array_keys($requestBody);
                return !empty(array_intersect($keys, $dataKeys));
            } catch (\Throwable $th) {
                throw new \Exception($th->getMessage(), 500);
            }
        }
        throw new \Exception("requestBody must be an array.", 500);
    }

    private function makeTree($adjacency_list, $index = 0, $id_key = "id", $parent_id_key = "parent_id", $depth = -1)
    {
        $nodeDepth = $depth + 1;
        $node = new Node($adjacency_list[$index], $nodeDepth, ($index % 2 === 0 ? false : true));
        for ($i = $index; $i < count($adjacency_list); $i++) {
            if ($node->node[$id_key] === $adjacency_list[$i][$parent_id_key]) {
                $node->addChildren($this->makeTree($adjacency_list, $i, $id_key, $parent_id_key, $nodeDepth));
            }
        }
        return $node;
    }

    /**
     * @param string $path_to_remove
     * 
     * Recursively delete the given path.
     */
    public function rrmdir(string $path_to_remove)
    {
        if (is_dir($path_to_remove)) {
            $objects = scandir($path_to_remove);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($path_to_remove . DIRECTORY_SEPARATOR . $object) && !is_link($path_to_remove . "/" . $object))
                        $this->rrmdir($path_to_remove . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($path_to_remove . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($path_to_remove);
        }
    }

    /**
     * @param $text
     * 
     * Generate a server log inside "micron-logs" folder
     */
    public static function log(string $text)
    {
        $log_path = $_SERVER["DOCUMENT_ROOT"] . "/micron-logs/";
        if (!is_dir($log_path)) {
            mkdir($log_path);
        }
        $file_path = $log_path . "log.txt";
        $lock_file = $log_path . "log.txt.lock";
        try {
            $file_free = false;
            while (!$file_free) {
                if (file_exists($lock_file)) {
                    usleep(150);
                } else {
                    $file_free = true;
                }
            }
            file_put_contents($lock_file, "");
            if (file_exists($file_path)) {
                file_put_contents($file_path, date("Y-m-d H:i:s") . " - " . $text . "\r\n", FILE_APPEND);
            } else {
                file_put_contents($file_path, date("Y-m-d H:i:s") . " - " . $text . "\r\n");
            }
            unlink($lock_file);
        } catch (\Throwable $th) {
            if (file_exists($lock_file)) {
                unlink($lock_file);
            }
        }
    }

    /**
     * @param int $seconds
     * 
     * Convert given seconds in 'h m s' format. 
     * 
     */
    public static function fromSecondsToTime(int $seconds)
    {
        return intval(($seconds / 3600)) . "h " . ($seconds / 60 % 60) . "m " . ($seconds % 60) . "s";
    }

    /**
     * Summary of convertAdjacencyListToNestedObject
     * @param array $adjacency_list
     * @param int $index
     * @param string $id_key
     * @param string $parent_id_key
     * @return Node
     * 
     * Function that convert an Adjacency List Array (common for modelling nested structures in MySQL) into an Object.
     * Every item of the list become a Node Class Object. Every Node has a list of nested childrends.
     */
    public function convertAdjacencyListToNestedObject(array $adjacency_list, int $index = 0, string $id_key = "id", string $parent_id_key = "parent_id")
    {
        return $this->makeTree($adjacency_list, $index, $id_key, $parent_id_key);
    }


    public static function getResourceName(string $className): string | null {
        $reflectionClass = new \ReflectionClass($className);
        $resourceAttributes = $reflectionClass->getAttributes(ResourceName::class);
        if (count($resourceAttributes) === 0)
            return null;

        $resourceNameAttributeParams = $resourceAttributes[0]->getArguments();

        if (count($resourceNameAttributeParams) === 0)
            return "";

        return $resourceNameAttributeParams[0];
    }

    /**
     * Take a list of keys and a body and return a new array with only allowed keys
     * @param array $allowedKeys Contains the keys allowed in the body. e.g. ["user_id", "name"]
     * @param array $body The body to normalize
     * @return array
     * 
     * 
     */
    public static function normalizeBody(array $allowedKeys, array $body) : array {
        $normalizedBody = [];
        foreach ($allowedKeys as $allowedKey) {
            if(isset($body[$allowedKey])){
                $normalizedBody[$allowedKey] = $body[$allowedKey];
            }
        }
        return $normalizedBody;
    }


}