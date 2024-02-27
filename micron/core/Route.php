<?php

require_once 'DataHelper/DataHelper.php';
require_once 'JWT/JWT.php';
require_once 'Request.php';
require_once 'MiddlewareConfiguration.php';
require_once 'MiddlewareModules/JWTControl.php';
require_once 'Resource.php';

use core\DataHelper\DataHelper;
use core\JWT;
use core\MiddlewareConfiguration;
use core\Resource;
use core\Response;

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
     * If false all the application end points are not reachable.
     * By default is set to true
     *
     * @var bool
     */
    public $isPublished = true;
    /**
     * Callback function that will be run if the isPublished property is set to false
     *
     * @var null
     */
    public $notPulishedCallback = null;

    public $accessPassphraseKeyIfNotPublished = "";
    public $accessPassphraseIfNotPublished = "";

    public $enableUsersManagement = false;
    

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
        if (isset($headers['Content-Type'])) {
            $contentType = $headers['Content-Type'];
        }
        $inArray = false;
        foreach ($acceptedContentType as $allowed) {
            if (str_contains($contentType, $allowed)) {
                $inArray = true;
            }
        }
        if (!$inArray) {
            throw new Exception("Invalid request content type. Allowed content type for this route " . (count($acceptedContentType) > 1 ? "are" : "is") . " :  " . (count($acceptedContentType) > 0 ? implode(array: $acceptedContentType, separator: ", ") : "none"), 400);
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
            $keyToCheck = $param;
            if (is_int($keyToCheck)) {
                $keyToCheck = $queryParams[$keyToCheck];
            }
            if (isset($_GET[$keyToCheck])) {
                if (is_int($param)) {
                    $qParams[$keyToCheck] = $_GET[$keyToCheck];
                } else {
                    if (!in_array($queryParams[$param], $queryParamTypes)) {
                        throw new Exception("Query param type for {$param} not allowed, can be 'string', 'numeric' or 'mixed' ", 400);
                    }
                    if ((is_numeric($_GET[$param]) && $queryParams[$param] === "numeric") || (!is_numeric($_GET[$param]) && $queryParams[$param] === "string")) {
                        $qParams[$param] = $_GET[$param];
                    } else if($queryParams[$param] === "mixed"){
                        $qParams[$param] = $_GET[$param];
                    }
                    else{
                        throw new Exception("Query param value for {$param} not allowed, should be : {$queryParams[$param]}", 400);
                    }
                }

            }
        }

        if (gettype($token) === "object" && get_class($token) === "core\JWT") {
            $token = $token->getBody();
        } else {
            $token = [];
        }

        $uri = "";
        if (!isset($_REQUEST["uri"])) {
            $uri = "/";
        } else {
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

        if (!$this->isPublished) {
            if (!isset($_GET[$this->accessPassphraseKeyIfNotPublished]) || $_GET[$this->accessPassphraseKeyIfNotPublished] !== $this->accessPassphraseIfNotPublished) {
                if ($this->notPulishedCallback !== null) {
                    $callback = $this->notPulishedCallback;
                    if (is_callable($callback)) {
                        $callback();
                        exit;
                    } else {
                        throw new Exception("Given callback for 'not published' is not callable.");
                    }

                } else {
                    Response::instance()->unhatorized("This application is not published yet.");
                }
            } else {
                unset($_GET[$this->accessPassphraseKeyIfNotPublished]);
            }
        }


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
            if (!isset($keyExsposion[1])) {
                //throw new Exception("Missing type for {$keyExsposion[0]} path param.", 500);
                $paramType[] = "none";
            } else {
                if (!in_array($keyExsposion[1], $paramAllowedTypes)) {
                    throw new Exception("Unricognized type for {$keyExsposion[0]} path param. Use 'string' or 'numeric'", 500);
                }
                $paramType[] = $keyExsposion[1];
            }
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
                if ((is_numeric($reqUri[$index]) && $paramType[$key] === "numeric") || (!is_numeric($reqUri[$index]) && $paramType[$key] === "string")) {
                    $params[$paramKey[$key]] = $reqUri[$index];
                } else if ($paramType[$key] === "none") {
                    $params[$paramKey[$key]] = $reqUri[$index];
                } else {
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
    public function get(string $route, closure $callback, array $headers = [], MiddlewareConfiguration|null $middlewareSettings = null, array $allowedQueryParams = []): void
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
    public function post(string $route, closure $callback, array $headers = [], MiddlewareConfiguration|null $middlewareSettings = null, array $allowedQueryParams = []): void
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
    public function delete(string $route, closure $callback, array $headers = [], MiddlewareConfiguration|null $middlewareSettings = null, array $allowedQueryParams = []): void
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
    public function put(string $route, closure $callback, array $headers = [], MiddlewareConfiguration|null $middlewareSettings = null, array $allowedQueryParams = []): void
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

    /**
     * Register a resource and listen for incoming request. This function is a bit faster of 'start' with autodiscover
     * A Micron resource is a class that implements the Resource interface. 
     *
     * @param array $resources can ben an array of objects, class names (string) or mixed (object and classnames). Use Objects if you want to pass custom attributes or Router
     * 
     * @return void
     * 
     */
    public function registerResources(array $resources): void
    {
        foreach ($resources as $resource) {
            $resourceInstance = null;
            if (gettype($resource) === "string") {
                $resourceInstance = new $resource();
            } else {
                $resourceInstance = $resource;
            }
            if ($resourceInstance instanceof Resource) {
                $resourceInstance->listen($this);
            } else {
                throw new Exception("The class " . get_class($resourceInstance) . " is not a resource.", 500);
            }
        }
    }

    /**
     * Start the server and autodiscover the available Resources.
     * A Micron resource is a class that implements the Resource interface. 
     *
     * 
     * @return void
     * 
     */
    public function start(): void
    {
        $resources = array_filter(
            get_declared_classes(),
            function ($className) {
                $conditionImplementResource = in_array('core\Resource', class_implements($className));
                if(!$this->enableUsersManagement && $className == "core\Users"){
                    return false;
                }
                return $conditionImplementResource;
            }
        );

        foreach ($resources as $resource) {
            $resourceInstance = new $resource();
            $resourceInstance->listen($this);
        }

    }


    private function staticMakePath(string $path, array &$paths){
        $scan = scandir($path);
        $scan = array_filter($scan, function($item) {
            return $item !== "." && $item !== "..";
        });
        foreach ($scan as $folderObject) {
            $completeFolder = $path."/".$folderObject;
            if(is_dir($completeFolder)){
                $this->staticMakePath($completeFolder, $paths);
            }
            else{
                array_push($paths, $completeFolder);
            }
        }
        return;
    }

    /**
     * This function serve files statically. If you make subfolders they will be used to build the path for the static files. 
     * @param string $staticFolderPath the path of the folder which contains the files to provide statically.
     * 
     * @return void
     * 
     */
    public function static(string $staticFolderPath){
        $pathsToServe = [];
        $this->staticMakePath($staticFolderPath, $pathsToServe);
        foreach ($pathsToServe as $path) {
            $uri = str_replace($staticFolderPath."/", "", $path);
            $this->get($uri, function(Request $request) use($path){
                Response::instance()->provideFile($path, false);
            }, middlewareSettings: MiddlewareConfiguration::getConfiguration(tokenControl: false));
        }
        
    }
}