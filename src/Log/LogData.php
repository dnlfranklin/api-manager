<?php

namespace ApiManager\Log;

use ApiManager\Http\Data;
use ApiManager\Provider\Response;

class LogData{

    public $server_id;
    public $request_id;
    public $server_name;
    public $ip_origin;
    public $protocol;
    public $uri;
    public $http_method;
    public $prefix;
    public $sufix;
    public $header_params;
    public $body_params;
    public $creation_datetime;
    public $query_params;
    public $middleware_params;
    public $response_code;
    public $response_headers;
    public $response_body;
    public $response_execution_time;
    public $idempotencykey;
    public $send_to_service;

    public function parse(Array|Data|Response $data):void {
        if($data instanceof Data){
            $this->body_params = $data->getBodyParams(); 
            $this->query_params = $data->getQueryParams(); 
            $this->middleware_params = $data->getMiddlewareParams(); 
            $this->server_id = $data->getServerId();
            $this->request_id = $data->getRequestId();
            $this->server_name = $data->getServerName();
            $this->ip_origin = $data->getIpOrigin();
            $this->protocol = $data->getProtocol();
            $this->uri = $data->getRequestUri();
            $this->http_method = $data->getRequestMethod();
            $this->prefix = $data->getPrefixUri();
            $this->sufix = $data->getSufixUri();
            $this->header_params = $data->getHeaderParams();
            $this->creation_datetime = $data->getDatetime();
        }
        else if($data instanceof Response){
            $this->response_code = $data->getCode();
            $this->response_headers = $data->getHeaders();
            $this->response_body = $data->getBody();          
            $this->idempotencykey = $data->getIdempotencykey();  
        }
        else{
            foreach($data as $key => $value){
                if(property_exists($this, $key)){
                    $this->{$key} = $value;
                }
            }    
        }
    }

}