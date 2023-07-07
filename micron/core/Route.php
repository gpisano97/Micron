<?php

require_once 'DataHelper/DataHelper.php';
require_once 'JWT/JWT.php';
require_once 'Request.php';
require_once 'MiddlewareConfiguration.php';
require_once 'MiddlewareModules/JWTControl.php';

use core\DataHelper\DataHelper;
use core\JWT;
use core\MiddlewareConfiguration;

/**
 * Main component of Micron Framework. This class allow to register Routes with methods and requests behavior.
 */
class Route
{

    private $allowedCORSUrl = "*";
    private $CORSEnabled = false;
    private MiddlewareConfiguration $middlewareConfig;
    private $allowedContentType = ["application/json", "text/json"];

    /**
     * Initialize Micron Framework
     *
     * @param MiddlewareConfiguration $defaultMiddlewareConfig A MiddlewareConfiguration Object, this set the default behavior of the Micron Middleware.
     * @param mixed 
     * 
     */
    public function __construct(MiddlewareConfiguration $defaultMiddlewareConfig = new MiddlewareConfiguration())
    {
        $this->middlewareConfig = $defaultMiddlewareConfig;
    }

    private function Middleware(MiddlewareConfiguration $config, $URIparams = [], $queryParams = [])
    {
        //JWT Token Control
        $token = new JWT([]);
        new JWTControl(config: $config, token: $token);

        //Request Content-Type Control
        $acceptedContentType = $config->getAcceptedContentType();

        $headers = getallheaders();
        $contentType = "none";
        if(isset($headers['Content-Type'])){
            $contentType = $headers['Content-Type'];
        }
        $inArray = false;
        foreach ($acceptedContentType as $allowed) {
            if(str_contains($contentType, $allowed)){
                $inArray = true;
            }
        }
        if(!$inArray){
            throw new Exception("Invalid request content type. Allowed content type for this route ".(count($acceptedContentType) > 1 ? "are" : "is")." :  ".(count($acceptedContentType) > 0 ? implode(array:$acceptedContentType, separator: ", ") : "none"), 400);
        }


        //Operations on incoming datas
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

        if (gettype($token) === "object" && get_class($token) === "core\JWT") {
            $token = $token->getBody();
        } else {
            $token = [];
        }

        $uri = "";
        if(!isset($_REQUEST["uri"])){
            $uri = "/";
        }
        else{
            $uri = $_REQUEST["uri"];
        }

        $request = new Request($uri, $_SERVER["REQUEST_METHOD"], $URIparams, $requestBody, $token, $qParams);
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
     * @param string $route The URI Route -> e.g 'home', 'users', 'users/{user_id:numeric}' etc.
     * @param closure $callback The function to execute if the Route is matched
     * @param array $headers Some additional headers
     * @param MiddlewareConfiguration|null $middlewareSettings Middleware Behavior for the request -> with this you can control token checking, token authorization and allowed content types . If null the default configuration will be loaded.
     * @param array $allowedQueryParams In this array you must define the allowed query params, e.g. ['param1' => "string || numeric", 'param2' =>  "string || numeric" , ... , 'paramN' => "string || numeric"]
     * 
     * @return void
     * 
     */
    public function get(string $route, closure $callback, array $headers = [], MiddlewareConfiguration|null $middlewareSettings = null, array $allowedQueryParams = []) : void
    {
        if ($_SERVER["REQUEST_METHOD"] === "GET") {
            $middlewareBehavior = ($middlewareSettings === null ? $this->middlewareConfig : $middlewareSettings);
            $this->navigate($route, $callback, "GET", headers: (count($headers) > 0 ? $headers : []), middlewareSettings: $middlewareBehavior, queryParams: $allowedQueryParams);
        }
    }

    /**
     * Add a POST method route
     *
     * @param string $route The URI Route -> e.g 'home', 'users', 'users/{user_id:numeric}' etc.
     * @param closure $callback The function to execute if the Route is matched
     * @param array $headers Some additional headers
     * @param MiddlewareConfiguration|null $middlewareSettings Middleware Behavior for the request -> with this you can control token checking, token authorization and allowed content types . If null the default configuration will be loaded.
     * @param array $allowedQueryParams In this array you must define the allowed query params, e.g. ['param1' => "string || numeric", 'param2' =>  "string || numeric" , ... , 'paramN' => "string || numeric"]
     * 
     * @return void
     * 
     */
    public function post(string $route, closure $callback, array $headers = [], MiddlewareConfiguration|null $middlewareSettings = null, array $allowedQueryParams = []) : void
    {

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $middlewareBehavior = ($middlewareSettings === null ? $this->middlewareConfig : $middlewareSettings);
            $this->navigate($route, $callback, "POST", headers: (count($headers) > 0 ? $headers : []), middlewareSettings: $middlewareBehavior, queryParams: $allowedQueryParams);
        }
    }

    /**
     * Add a DELETE method route
     *
     * @param string $route The URI Route -> e.g 'home', 'users', 'users/{user_id:numeric}' etc.
     * @param closure $callback The function to execute if the Route is matched
     * @param array $headers Some additional headers
     * @param MiddlewareConfiguration|null $middlewareSettings Middleware Behavior for the request -> with this you can control token checking, token authorization and allowed content types . If null the default configuration will be loaded.
     * @param array $allowedQueryParams In this array you must define the allowed query params, e.g. ['param1' => "string || numeric", 'param2' =>  "string || numeric" , ... , 'paramN' => "string || numeric"]
     * 
     * @return void
     * 
     */
    public function delete(string $route, closure $callback, array $headers = [], MiddlewareConfiguration|null $middlewareSettings = null, array $allowedQueryParams = []) : void
    {
        if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
            $middlewareBehavior = ($middlewareSettings === null ? $this->middlewareConfig : $middlewareSettings);
            $this->navigate($route, $callback, "DELETE", headers: (count($headers) > 0 ? $headers : []), middlewareSettings: $middlewareBehavior, queryParams: $allowedQueryParams);
        }
    }

    /**
     * Add a PUT route
     *
     * @param string $route The URI Route -> e.g 'home', 'users', 'users/{user_id:numeric}' etc.
     * @param closure $callback The function to execute if the Route is matched
     * @param array $headers Some additional headers
     * @param MiddlewareConfiguration|null $middlewareSettings Middleware Behavior for the request -> with this you can control token checking, token authorization and allowed content types . If null the default configuration will be loaded.
     * @param array $allowedQueryParams In this array you must define the allowed query params, e.g. ['param1' => "string || numeric", 'param2' =>  "string || numeric" , ... , 'paramN' => "string || numeric"]
     * 
     * @return void
     * 
     */
    public function put(string $route, closure $callback, array $headers = [], MiddlewareConfiguration|null $middlewareSettings = null, array $allowedQueryParams = []) : void
    {
        if ($_SERVER["REQUEST_METHOD"] === "PUT") {
            $middlewareBehavior = ($middlewareSettings === null ? $this->middlewareConfig : $middlewareSettings);
            $this->navigate($route, $callback, "DELETE", headers: (count($headers) > 0 ? $headers : []), middlewareSettings: $middlewareBehavior, queryParams: $allowedQueryParams);
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