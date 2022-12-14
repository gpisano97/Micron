<?php
class Route {
    
    private $allowedCORSUrl = "*";
    private $CORSEnabled = false;
    
    private function simpleRoute($callback, $route){
        
        if(!empty($_REQUEST['uri'])){
            $route = preg_replace("/(^\/)|(\/$)/","",$route);
            $reqUri =  preg_replace("/(^\/)|(\/$)/","",$_REQUEST['uri']);
        }else{
            $reqUri = "/";
        }
        
        if($reqUri == $route){
            $params = [];
            $callback();
            exit();
            
        }
        
    }
    
    private function navigate($route, $callback, $method, $headers = array("Access-Control-Max-Age" => "3600")){
        
        if($this->CORSEnabled){
            header("Access-Control-Allow-Origin: {$this->allowedCORSUrl}");
        }
        header("Access-Control-Allow-Methods: ".$method);
        header("Access-Control-Allow-Headers: Origin, Content-Type, Content-Type, Authorization, X-Requested-With");
        header("Content-Type: application/json; charset=UTF-8");
        $headers_key = array_keys($headers);
        
        foreach($headers_key as $header){
            header($header.": ".$headers[$header]);
        }
        $params = [];
        
        $paramKey = [];
        
        preg_match_all("/(?<={).+?(?=})/", $route, $paramMatches);
        
        if(empty($paramMatches[0])){
            $this->simpleRoute($callback, $route);
            return;
        }
        
        foreach($paramMatches[0] as $key){
            $paramKey[] = $key;
        }
        
        if(!empty($_REQUEST['uri'])){
            $route = preg_replace("/(^\/)|(\/$)/","",$route);
            $reqUri =  preg_replace("/(^\/)|(\/$)/","",$_REQUEST['uri']);
        }else{
            $reqUri = "/";
        }
        
        $uri = explode("/", $route);
        
        $indexNum = [];
        
        foreach($uri as $index => $param){
            if(preg_match("/{.*}/", $param)){
                $indexNum[] = $index;
            }
        }
        
        $reqUri = explode("/", $reqUri);
        
        foreach($indexNum as $key => $index){
            
            if(empty($reqUri[$index]) && $reqUri[$index] != 0 ){
                return;
            }
            
            $params[$paramKey[$key]] = $reqUri[$index];
            
            $reqUri[$index] = "{.*}";
        }
        
        $reqUri = implode("/",$reqUri);
        
        $reqUri = str_replace("/", '\\/', $reqUri);
        
        if(preg_match("/$reqUri/", $route))
        {
            $callback($params);
            exit();
            
        }
    }
    
    
    public function get($route, $callback, $headers = []){
        if($_SERVER["REQUEST_METHOD"] === "GET"){
            if(count($headers) > 0){
                $this->navigate($route, $callback, "GET", $headers);
            }
            else{
                $this->navigate($route, $callback, "GET");
            }
        }
    }
    
    public function post($route,$callback, $headers = []){
        
        if($_SERVER["REQUEST_METHOD"] === "POST"){
            if(count($headers) > 0){
                $this->navigate($route, $callback, "POST", $headers);
            }
            else{
                $this->navigate($route, $callback, "POST");
            }
        }
    }
    
    public function delete($route, $callback, $headers = []){
        if($_SERVER["REQUEST_METHOD"] === "DELETE"){
            if(count($headers) > 0){
                $this->navigate($route, $callback, "DELETE", $headers);
            }
            else{
                $this->navigate($route, $callback, "DELETE");
            }
        }
    }
    
    public function put($route, $callback, $headers = []){
        if($_SERVER["REQUEST_METHOD"] === "PUT"){
            if(count($headers) > 0){
                $this->navigate($route, $callback, "PUT", $headers);
            }
            else{
                $this->navigate($route, $callback, "PUT");
            }
            
        }
    }
    
    
    public function notFound($file){
        include($file);
        exit();
    }
    
    public function enableCORS($allowedOrigin = "*"){
        $this->allowedCORSUrl=$allowedOrigin;
        $this->CORSEnabled = true;
        if($_SERVER["REQUEST_METHOD"] === "OPTIONS"){
            http_response_code(204);
            header("Access-Control-Allow-Origin: {$allowedOrigin}");
            header("Access-Control-Allow-Method: POST, GET, OPTIONS, PUT, DELETE");
            header("Content-Type: application/json");
            header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin");
            exit;
        }
    }
}
