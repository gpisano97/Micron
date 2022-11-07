<?php
class Route {
    
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
    
    private function navigate($route, $callback, $method, $headers = array("Access-Control-Allow-Origin" => "*", "Content-Type" => "application/json; charset=UTF-8", "Access-Control-Max-Age" => "3600", "Access-Control-Allow-Headers" => "Content-Type, Access-Contro-Allow-Headers, Authorization, X-Requested-With")){
        
        header("Access-Control-Allow-Methods: ".$method);
        
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
            
            if(empty($reqUri[$index])){
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
    

    public function get($route, $callback, $headers = array("Access-Control-Allow-Origin" => "*", "Content-Type" => "application/json; charset=UTF-8", "Access-Control-Max-Age" => "3600", "Access-Control-Allow-Headers" => "Content-Type, Access-Contro-Allow-Headers, Authorization, X-Requested-With")){
        if($_SERVER["REQUEST_METHOD"] === "GET"){
            $this->navigate($route, $callback, "GET", $headers);
        }
    }
    
    public function post($route,$callback, $headers = array("Access-Control-Allow-Origin" => "*", "Content-Type" => "application/json; charset=UTF-8", "Access-Control-Max-Age" => "3600", "Access-Control-Allow-Headers" => "Content-Type, Access-Contro-Allow-Headers, Authorization, X-Requested-With")){       
        if($_SERVER["REQUEST_METHOD"] === "POST"){
            $this->navigate($route, $callback, "POST", $headers);
        }       
    }
    
    public function delete($route, $callback, $headers = array("Access-Control-Allow-Origin" => "*", "Content-Type" => "application/json; charset=UTF-8", "Access-Control-Max-Age" => "3600", "Access-Control-Allow-Headers" => "Content-Type, Access-Contro-Allow-Headers, Authorization, X-Requested-With")){
        if($_SERVER["REQUEST_METHOD"] === "DELETE"){
            $this->navigate($route, $callback, "DELETE", $headers);
        } 
    }
    
    public function put($route,$callback){
        if($_SERVER["REQUEST_METHOD"] === "PUT"){
            $this->navigate($route, $callback, "PUT");
        }
    }
    
    public function notFound($file){
        include($file);
        exit();
    }
}
