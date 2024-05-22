<?php

namespace ApiManager\Http;

class Data {

    const MIDDLEWARE_PARAM_RESPONSE = '_MIDDLEWARE_RESPONSE_';

    private $datetime;
    private $server_id;
    private $prefix_uri;
    private $request_id;
    private $http_origin;
    private $request_uri;
    private $protocol;
    private $server_name;
    private $request_method;
    private $header_params;
    private $body_params;
    private $query_params;
    private $middleware_params;


    public function __construct($server_id = null, $prefix_uri = null){
        $this->datetime = date('Y-m-d H:i:s');
        $this->server_id = $server_id;
        $this->prefix_uri = $prefix_uri;
        $this->request_id = strtoupper(implode('-', [
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(chr((ord(random_bytes(1)) & 0x0F) | 0x40)) . bin2hex(random_bytes(1)),
            bin2hex(chr((ord(random_bytes(1)) & 0x3F) | 0x80)) . bin2hex(random_bytes(1)),
            bin2hex(random_bytes(6))
        ]));

        $this->getHttpOrigin();
        $this->getRequestUri();
        $this->getProtocol();
        $this->getServerName();
        $this->getRequestMethod();
        $this->getHeaderParams();
        $this->getBodyParams();
        $this->query_params = [];
        $this->middleware_params = [];
    }

    public function getDatetime(){
        return $this->datetime;
    }

    public function getServerId(){
        return $this->server_id;
    }

    public function getPrefixUri(){
        return $this->prefix_uri;
    }

    public function getSufixUri(){
        $uri = trim($this->request_uri, '/'); 
        $prefix = trim($this->prefix_uri ?? '', '/');
        $sufix = str_replace($prefix, '', $uri);
        
        return trim($sufix, '/');
    }

    public function getRequestId(){
        return $this->request_id;
    }

    public function getHttpOrigin(){
        if( empty($this->http_origin) ){
            $this->http_origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        }
        return $this->http_origin;
    }

    public function getRequestUri(){
        if( empty($this->request_uri) ){
            if( !empty($_REQUEST['REQUEST_URI']) ){
                $this->request_uri = $_REQUEST['REQUEST_URI'];
            }
            else{
                $this->request_uri = $_SERVER['REQUEST_URI'] ?? '';
            }
        }
        return $this->request_uri;
    }

    public function getProtocol(){
        if( empty($this->protocol) ){
            $this->protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http';
        }   
        return $this->protocol; 
    }

    public function getServerName(){
        if( empty($this->server_name) ){
            $this->server_name = $_SERVER['SERVER_NAME'] ?? '';
        }
        return $this->server_name;
    }  
    
    public function getIpOrigin(){
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

    public function getRequestMethod(){
        if( empty($this->request_method) ){
            $this->request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        }   
        return $this->request_method; 
    }

    public function getHeaderParams(){
        if( empty($this->header_params) ){
            $headers = array();
            
            foreach ($_SERVER as $key => $value)
            {
                if (substr($key, 0, 5) == 'HTTP_')
                {
                    $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                    $headers[$header] = $value;
                }
            }
            
            if (function_exists('getallheaders'))
            {
                $allheaders = getallheaders();
                
                $this->header_params = $allheaders ? $allheaders : $headers;
            }
            else{
                $this->header_params = $headers;
            }
        }
        return $this->header_params;
    }

    public function getBodyParams(){
        if( empty($this->body_params) ){
            $request = $_REQUEST;
            
            $input = file_get_contents("php://input");

            $params = (array) json_decode($input, true);
            
            if( empty($params) ){
                parse_str($input, $params);                
            }
            
            $request  = array_merge($request, $params);
            
            $this->body_params = $request;
        }
        return $this->body_params;
    }

    public function getQueryParams(){
        return $this->query_params;    
    }

    public function setQueryParams(Array $params){
        $this->query_params = $params;
    }
    
    public function addBodyParam( $key, $value ){
        $this->body_params[$key] = $value;
    }
    
    public function getMiddlewareParams(){
        return $this->middleware_params;
    }

    public function addMiddlewareParam(string $key, mixed $value){
        $this->middleware_params[$key] = $value;
    }   

    public function getRequestParams(){
        return array_merge(
            $this->getBodyParams(), 
            $this->getQueryParams(),
            $this->getMiddlewareParams()
        );
    }

}