<?php

require_once 'DataHelper/DataHelper.php';
require_once 'JWT/JWT.php';
require_once 'Request.php';

use core\DataHelper\DataHelper;
use core\JWT;

/**
 * Summary of Route
 */
class Route
{

    private $allowedCORSUrl = "*";
    private $CORSEnabled = false;
    private $middlewareConfig = [];
    private $allowedContentType = ["application/json", "text/json"];

    /**
     * Initialize Micron Framework
     *
     * @param array $defaultMiddlewareConfig = [ 
     *      'TOKEN_CONTROL' => true|false //set the middleware to check authorization bearer token
     *      'TOKEN_AUTH' => ['token_body_param' => 'authorized_value', ...] //check if specified token body param has the authorized value 
     * ]
     * @param mixed 
     * 
     */
    public function __construct(array $defaultMiddlewareConfig = ['TOKEN_CONTROL' => true])
    {
        $this->middlewareConfig = $defaultMiddlewareConfig;
    }

    private function Middleware($config, $URIparams = [], $queryParams = [])
    {

        $token = DataHelper::getToken();
        if (isset($config["TOKEN_CONTROL"]) && $config["TOKEN_CONTROL"]) {
            if (empty($token)) {
                throw new Exception("Missing auth token.", 400);
            }
            $token = JWT::decode($token);
            if (isset($config['TOKEN_AUTH'])) {
                if (gettype($config['TOKEN_AUTH']) !== 'array') {
                    throw new Exception("Bad middleware's TOKEN_AUTH config: value is not an array.", 500);
                }

                foreach ($config['TOKEN_AUTH'] as $tokenBodyParam => $checkingValue) {
                    if (!isset($token->getBody()[$tokenBodyParam])) {
                        throw new Exception("Bad middleware's TOKEN_AUTH config: param {$tokenBodyParam} is not in Token Body.", 500);
                    }

                    if ($token->getBody()[$tokenBodyParam] !== $checkingValue) {
                        throw new Exception("Insufficent permissions.", 401);
                    }
                }
            }
        }

        $requestBody = DataHelper::postGetBody();
        if ($requestBody === null) {
            $requestBody = [];
        }
        $requestBody = array_merge($requestBody, $_POST, $_FILES);

        $qParams = [];
        $queryParamsKeys = array_keys($queryParams);
        $queryParamTypes = ["numeric", "string"];
        foreach ($queryParamsKeys as $param) {
            if (isset($_GET[$param])) {
                if( !in_array($queryParams[$param], $queryParamTypes)){
                    throw new Exception("Query param type for {$param} not allowed, can be 'string' or 'numeric' ", 400);
                }
                if( (is_numeric($_GET[$param]) && $queryParams[$param] === "numeric" ) || (!is_numeric($_GET[$param]) && $queryParams[$param] === "string")){
                    $qParams[$param] = $_GET[$param];
                }
                else {
                    throw new Exception("Query param value for {$param} not allowed, should be : {$queryParams[$param]}", 400);
                }
            }
        }


        if (gettype($token) !== "string") {
            $token = $token->getBody();
        } else {
            $token = [];
        }

        $request = new Request($_REQUEST["uri"], $_SERVER["REQUEST_METHOD"], $URIparams, $requestBody, $token, $qParams);
        return $request;
    }


    private function simpleRoute($callback, $route, $middlewareSettings, $queryParams)
    {

        if (!empty($_REQUEST['uri'])) {
            $route = preg_replace("/(^\/)|(\/$)/", "", $route);
            $reqUri = preg_replace("/(^\/)|(\/$)/", "", $_REQUEST['uri']);
        } else {
            $reqUri = "/";
        }

        if ($reqUri == $route) {
            $callback($this->Middleware($middlewareSettings, queryParams: $queryParams));
            exit();
        }

    }

    private function navigate($route, $callback, $method, $headers = array("Access-Control-Max-Age" => "3600"), $middlewareSettings = [], $queryParams = [])
    {

        if ($this->CORSEnabled) {
            header("Access-Control-Allow-Origin: {$this->allowedCORSUrl}");
        }
        header("Access-Control-Allow-Methods: " . $method);
        header("Access-Control-Allow-Headers: Origin, Content-Type, Content-Type, Authorization, X-Requested-With");
        header("Content-Type: application/json; charset=UTF-8");
        $headers_key = array_keys($headers);

        foreach ($headers_key as $header) {
            header($header . ": " . $headers[$header]);
        }
        $params = [];

        $paramKey = [];

        preg_match_all("/(?<={).+?(?=})/", $route, $paramMatches);

        if (empty($paramMatches[0])) {
            $this->simpleRoute($callback, $route, $middlewareSettings, $queryParams);
            return;
        }

        $paramAllowedTypes = ["string", "numeric"];
        $paramType = [];
        foreach ($paramMatches[0] as $key) {
            $keyExsposion = explode(":", $key); 
            $paramKey[] = $keyExsposion[0];
            if(!isset($keyExsposion[1])){
                throw new Exception("Missing type for {$keyExsposion[0]} path param.", 500);
            }
            if(!in_array($keyExsposion[1], $paramAllowedTypes)){
                throw new Exception("Unricognized type for {$keyExsposion[0]} path param. Use 'string' or 'numeric'", 500);
            }
            $paramType[] =  $keyExsposion[1] ; 
        }

        if (!empty($_REQUEST['uri'])) {
            $route = preg_replace("/(^\/)|(\/$)/", "", $route);
            $reqUri = preg_replace("/(^\/)|(\/$)/", "", $_REQUEST['uri']);
        } else {
            $reqUri = "/";
        }

        $uri = explode("/", $route);

        $indexNum = [];
       
        foreach ($uri as $index => $param) {
            if (preg_match("/{.*}/", $param)) {
                $indexNum[] = $index;
            }
        }

        $reqUri = explode("/", $reqUri);

        foreach ($indexNum as $key => $index) {

            if (isset($reqUri[$index]) && empty($reqUri[$index]) && $reqUri[$index] != 0) {
                return;
            }

            if (isset($reqUri[$index])) {
                if((is_numeric($reqUri[$index]) && $paramType[$key] === "numeric") || (!is_numeric($reqUri[$index]) && $paramType[$key] === "string")){
                    $params[$paramKey[$key]] = $reqUri[$index];
                }
                else{
                    return;
                }
            }


            $reqUri[$index] = "{.*}";
        }

        $reqUri = implode("/", $reqUri);

        $reqUri = str_replace("/", '\\/', $reqUri);

        if (preg_match("/$reqUri/", $route)) {
            $callback($this->Middleware($middlewareSettings, $params, $queryParams));
            exit();
        }
    }


    /**
     * Add a GET method route
     *
     * @param mixed $route
     * @param mixed $callback
     * @param array $headers
     * @param array $middlewareSettings = [ 
     *      'TOKEN_CONTROL' => true|false //set the middleware to check authorization bearer token
     *      'TOKEN_AUTH' => ['token_body_param' => 'authorized_value', ...] //check if specified token body param has the authorized value
     * ]
     * @param array $allowedQueryParams = [
     *      'param1', 'param2', ... , 'paramN'
     * ] //set the allowed query params
     * 
     * @return [type]
     * 
     */
    public function get($route, $callback, $headers = [], $middlewareSettings = [], $allowedQueryParams = [])
    {
        if ($_SERVER["REQUEST_METHOD"] === "GET") {
            $this->navigate($route, $callback, "GET", headers: (count($headers) > 0 ? $headers : []), middlewareSettings: (count($middlewareSettings) > 0 ? $middlewareSettings : $this->middlewareConfig), queryParams: $allowedQueryParams);
        }
    }

    /**
     * Add a POST method route
     *
     * @param mixed $route
     * @param mixed $callback
     * @param array $headers
     * @param array $middlewareSettings = [ 
     *      'TOKEN_CONTROL' => true|false //set the middleware to check authorization bearer token
     *      'TOKEN_AUTH' => ['token_body_param' => 'authorized_value', ...] //check if specified token body param has the authorized value
     * ]
     * @param array $allowedQueryParams = [
     *      'param1', 'param2', ... , 'paramN'
     * ] //set the allowed query params
     * 
     * @return [type]
     * 
     */
    public function post($route, $callback, $headers = [], $middlewareSettings = [], $allowedQueryParams = [])
    {

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->navigate($route, $callback, "POST", headers: (count($headers) > 0 ? $headers : []), middlewareSettings: (count($middlewareSettings) > 0 ? $middlewareSettings : $this->middlewareConfig), queryParams: $allowedQueryParams);
        }
    }

    /**
     * Add a DELETE method route
     *
     * @param mixed $route
     * @param mixed $callback
     * @param array $headers
     * @param array $middlewareSettings = [ 
     *      'TOKEN_CONTROL' => true|false //set the middleware to check authorization bearer token
     *      'TOKEN_AUTH' => ['token_body_param' => 'authorized_value', ...] //check if specified token body param has the authorized value
     * ]
     * @param array $allowedQueryParams = [
     *      'param1', 'param2', ... , 'paramN'
     * ] //set the allowed query params
     * 
     * @return [type]
     * 
     */
    public function delete($route, $callback, $headers = [], $middlewareSettings = [], $allowedQueryParams = [])
    {
        if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
            $this->navigate($route, $callback, "DELETE", headers: (count($headers) > 0 ? $headers : []), middlewareSettings: (count($middlewareSettings) > 0 ? $middlewareSettings : $this->middlewareConfig), queryParams: $allowedQueryParams);
        }
    }

    /**
     * Add a get PUT route
     *
     * @param mixed $route
     * @param mixed $callback
     * @param array $headers
     * @param array $middlewareSettings = [ 
     *      'TOKEN_CONTROL' => true|false //set the middleware to check authorization bearer token
     *      'TOKEN_AUTH' => ['token_body_param' => 'authorized_value', ...] //check if specified token body param has the authorized value
     * ]
     * @param array $allowedQueryParams = [
     *      'param1', 'param2', ... , 'paramN'
     * ] //set the allowed query params
     * 
     * @return [type]
     * 
     */
    public function put($route, $callback, $headers = [], $middlewareSettings = [], $allowedQueryParams = [])
    {
        if ($_SERVER["REQUEST_METHOD"] === "PUT") {
            $this->navigate($route, $callback, "DELETE", headers: (count($headers) > 0 ? $headers : []), middlewareSettings: (count($middlewareSettings) > 0 ? $middlewareSettings : $this->middlewareConfig), queryParams: $allowedQueryParams);
        }
    }


    public function notFound($file)
    {
        include($file);
        exit();
    }


    /**
     * Enable CORS preflight request.
     * 
     * @param string $allowedOrigin = "*"
     * @param array $allowedContentType = ["application/json", "text/json"]
     * @return void
     * 
     * 
     */
    public function enableCORS($allowedOrigin = "*", $allowedContentType = ["application/json", "text/json"])
    {
        $this->allowedCORSUrl = $allowedOrigin;
        $this->CORSEnabled = true;
        $header = getallheaders();
        $contentType = "text/json";
        if (isset($header["Content-Type"]) && in_array($header["Content-Type"], $allowedContentType)) {
            $contentType = $header["Content-Type"];
        }

        if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
            http_response_code(204);
            header("Access-Control-Allow-Origin: {$allowedOrigin}");
            header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
            header("Content-Type: {$contentType}");
            header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin");
            exit;
        }
    }
}