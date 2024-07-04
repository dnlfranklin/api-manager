<?php

namespace ApiManager\Http;

class Request{
    
    private $uniqid;
    private $server_params;
    private $header_params;
    private $params;    
    private $base_url;
    private $body_string;
    private $body_decode;
    

    public function __construct(){
        $this->uniqid = uniqid(time().rand());
        $this->server_params = $_SERVER;
        $this->params = [];  
        $this->base_url = '/';
        $this->getHeaderParams();
        $this->getbodyString();
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

    public function getServerParams():Array{
        return $this->server_params;
    }
    
    public function getHeaderParams():Array{
        if(!$this->header_params){
            foreach ($this->server_params as $key => $value){
                if (substr($key, 0, 5) == 'HTTP_'){
                    $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                    $headers[$header] = $value;
                }
            }
            
            if (function_exists('getallheaders')){
                $allheaders = getallheaders();
                
                if($allheaders){
                    $headers = $allheaders;
                }            
            }        
    
            $this->header_params = $headers;
        }
        
        return $this->header_params;
    }

    public function getbodyParams(){ 
        if(empty($this->body_string)){
            return [];    
        }

        if($this->body_decode){
            return call_user_func($this->body_decode, $this->body_string);
        }

        switch($this->getContentType()){
            case 'multipart/form-data':
            case 'application/x-www-form-urlencoded':
                parse_str($this->body_string, $body_params);  
                
                return $body_params;
                break;
            case 'application/xml':
                $xml = simplexml_load_string($this->body_string);
                
                return json_decode(json_encode((array) $xml), true);
                break;
            default:
                return json_decode($this->body_string, true);
        }
    }

    public function getQueryParams():Array {
        parse_str($this->getQueryString(), $query_params);
        
        return $query_params;   
    } 
    
    public function getbodyString():string {
        if(is_null($this->body_string)){
            $body = file_get_contents("php://input");
            
            if(empty($body) && !empty($_POST)){
                $body = http_build_query($_POST);
            }

            $this->body_string = $body;
        }

        return $this->body_string;
    }

    public function getQueryString():string {
        return parse_url($this->server_params['REQUEST_URI'])['query'] ?? '';
    } 
    
    public function getParams():Array {
        return $this->params;
    }

    public function getAllParams():Array {
        $body_params = $this->getbodyParams();
        
        return array_merge(
            $this->getQueryParams(),
            is_array($body_params) ? $body_params : ['_decode' => $body_params], 
            $this->params
        );
    }

    public function getHeader(string $name):Array {
        $value = $this->getHeaderLine($name);
        
        return $value ? explode(';', $value) : [];
    }

    public function getHeaderLine(string $name):?string {
        foreach($this->header_params as $header_key => $header_value){
            if(strtolower($name) == strtolower($header_key)){
                return $header_value;
            }
        }
        
        return null;
    }

    public function hasHeader(string $name):bool {
        return empty($this->getHeaderLine($name));
    }

    public function getContentType():?string {
        $result = $this->getHeader('Content-Type');
        
        return $result ? $result[0] : null;
    }

    public function getPort():string {
        return $this->server_params['SERVER_PORT'] ?? '';
    }
    
    public function getRequestTime():int {
        return $this->server_params['REQUEST_TIME'] ?? 0;
    } 

    public function isMethod(string $method):bool {
        return strtoupper($this->httpMethod()) == strtoupper($method);
    }

    public function setBaseUrl(string $path){
        if(!Path::hasPrefixPath($path, $this->originalUrl())){
            throw new \InvalidArgumentException('Base URL does not match the original URL.');
        }
        
        $this->base_url = $path;
    }    

    public function setParam(string $key, mixed $value){
        $this->params[$key] = $value;
    }
    
    public function setBodyDecode(callable $decode){
        $this->body_decode = $decode;
    }

    public function httpOrigin():?string {
        return $this->server_params['HTTP_ORIGIN'] ?? null;        
    }

    public function originalUrl():string {        
        return $this->server_params['REQUEST_URI'] ?? '';
    }

    public function protocol():string {
        return  isset($this->server_params['HTTPS']) && $this->server_params['HTTPS'] != 'off' ? 'https' : 'http';
    }        

    public function serverName():string {
        return $this->server_params['SERVER_NAME'] ?? '';
    }   
    
    public function ipOrigin():string {
        if(!empty($this->server_params['HTTP_CLIENT_IP'])) {   
            return $this->server_params['HTTP_CLIENT_IP'];   
        }
        elseif (!empty($this->server_params['HTTP_X_FORWARDED_FOR'])) {   
           return $this->server_params['HTTP_X_FORWARDED_FOR'];   
        } 
        else{   
            return $this->server_params['REMOTE_ADDR'];   
        } 
    }

    public function httpMethod():string {
        return $this->server_params['REQUEST_METHOD'] ?? '';
    } 
    
    public function cookies(string $name = null){
        return $name ? $_COOKIE[$name] : $_COOKIE;
    }
    
    /**
     * Alias for getHeaderParams()
     */
    public function headerParams():Array {
        return $this->getHeaderParams();
    }

}