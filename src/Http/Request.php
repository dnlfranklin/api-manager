<?php

namespace ApiManager\Http;

class Request{
    
    private $base_url = '/';
    private $body_params = [];
    private $query_params = [];
    private $middleware_params = [];

    
    public function __construct(){
        $request = $_REQUEST;
        
        $input = file_get_contents("php://input");

        $params = (array) json_decode($input, true);
        
        if(empty($params)){
            parse_str($input, $params);                
        }
        
        $this->body_params = array_merge($request, $params);
    }

    public function getBaseUrl(){
        return $this->base_url;
    }

    public function getPath(){
        $url = parse_url($this->originalUrl());

        return Path::removePrefix($this->base_url, $url['path']);
    }

    public function getbodyParams(){
        return $this->body_params;
    }

    public function getQueryParams(){
        return $this->query_params;    
    }   
    
    public function getMiddlewareParams(){
        return $this->middleware_params;
    }

    public function getAllParams(){
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

    public function httpOrigin(){
        return $_SERVER['HTTP_ORIGIN'] ?? null;        
    }

    public function originalUrl(){        
        return $_SERVER['REQUEST_URI'] ?? '';
    }

    public function protocol(){
        return  isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http';
    }        

    public function serverName(){
        return $_SERVER['SERVER_NAME'] ?? '';
    }   
    
    public function ipOrigin(){
        if(!empty($_SERVER['HTTP_CLIENT_IP'])) {   
            return $_SERVER['HTTP_CLIENT_IP'];   
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {   
           return $_SERVER['HTTP_X_FORWARDED_FOR'];   
        } 
        else{   
            return $_SERVER['REMOTE_ADDR'];   
        } 
    }

    public function httpMethod(){
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }  
    
    public function cookies(string $name = null){
        return $name ? $_COOKIE[$name] : $_COOKIE;
    }

    public function headerParams(){        
        $headers = array();
        
        foreach ($_SERVER as $key => $value){
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

}