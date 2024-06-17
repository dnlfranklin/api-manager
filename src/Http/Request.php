<?php

namespace ApiManager\Http;

class Request{
    
    private $uniqid;
    private $server;
    private $base_url = '/';
    private $body_params = [];
    private $query_params = [];
    private $middleware_params = [];

    
    public function __construct(){
        $this->uniqid = uniqid(time().rand());
        $this->server = $_SERVER;
        $this->bodyParams();               
    }

    public function getUniqid():string {
        return $this->uniqid;
    }

    public function getBaseUrl():string {
        return $this->base_url;
    }

    public function getPath():string {
        $url = parse_url($this->originalUrl());

        return Path::removePrefix($this->base_url, $url['path']);
    }

    public function getbodyParams():Array {
        return $this->body_params;
    }

    public function getQueryParams():Array {
        return $this->query_params;    
    }   
    
    public function getMiddlewareParams():Array {
        return $this->middleware_params;
    }

    public function getAllParams():Array {
        return array_merge(
            $this->body_params, 
            $this->query_params,
            $this->middleware_params
        );
    }

    public function setBaseUrl(string $path){
        if(!Path::hasPrefixPath($path, $this->originalUrl())){
            throw new \InvalidArgumentException('Base URL does not match the original URL.');
        }
        
        $this->base_url = $path;
    }

    public function setBodyParam(string $key, mixed $value){
        $this->body_params[$key] = $value;
    }

    public function setQueryParam(string $key, mixed $value){
        $this->query_params[$key] = $value;
    }

    public function setMiddlewareParam(string $key, mixed $value){
        $this->middleware_params[$key] = $value;
    }    

    public function httpOrigin():?string {
        return $this->server['HTTP_ORIGIN'] ?? null;        
    }

    public function originalUrl():string {        
        return $this->server['REQUEST_URI'] ?? '';
    }

    public function protocol():string {
        return  isset($this->server['HTTPS']) && $this->server['HTTPS'] != 'off' ? 'https' : 'http';
    }        

    public function serverName():string {
        return $this->server['SERVER_NAME'] ?? '';
    }   
    
    public function ipOrigin():string {
        if(!empty($this->server['HTTP_CLIENT_IP'])) {   
            return $this->server['HTTP_CLIENT_IP'];   
        }
        elseif (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {   
           return $this->server['HTTP_X_FORWARDED_FOR'];   
        } 
        else{   
            return $this->server['REMOTE_ADDR'];   
        } 
    }

    public function httpMethod():string {
        return $this->server['REQUEST_METHOD'] ?? '';
    }  
    
    public function cookies(string $name = null){
        return $name ? $_COOKIE[$name] : $_COOKIE;
    }

    public function headerParams():Array {        
        $headers = array();
        
        foreach ($this->server as $key => $value){
            if (substr($key, 0, 5) == 'HTTP_'){
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        
        if (function_exists('getallheaders')){
            $allheaders = getallheaders();
            
            return $allheaders ? $allheaders : $headers;
        }
        else{
            return $headers;
        }        
    }

    private function bodyParams(){
        $request = $_REQUEST;
        
        $input = file_get_contents("php://input");

        $params = (array) json_decode($input, true);
        
        if(empty($params)){
            parse_str($input, $params);                
        }
        
        $this->body_params = array_merge($request, $params); 
    }

}