<?php

namespace ApiManager\Provider;

use ApiManager\Http\Data;

abstract class Response {
    
    private $server_http_data;
    private $idempotencykey;
    private $body;
    
    abstract public function exec($controller, $method);
    
    abstract public function send(int $code, mixed $body = null);

    protected function setCode(int $code){
        http_response_code($code);
    }

    protected function setBody(string|null $body){
        $this->body = $body;
    } 
    
    protected function setIdempotencykey(string $key){
        $this->idempotencykey = $key;
    }

    protected function getServerHttpData(){
        return $this->server_http_data;    
    }

    public function getCode(){
        return http_response_code();
    }

    public function getHeaders(){
        return headers_list();
    }

    public function getBody(){
        return $this->body;    
    }

    public function getIdempotencykey(){
        return $this->idempotencykey;
    }
    
    public function setServerHttpData(Data $server_http_data){
        $this->server_http_data = $server_http_data;    
    }

    public function addHeader(string $header){
        header($header);
    }

    public static function echo(int $code, Array $headers, string $body = null){
        http_response_code($code);
        
        if( !empty($headers) ){            
            foreach($headers as $header){
                header($header);
            }
        }

        if( !empty($body) ){
            echo $body;    
        }
    }
    
    
    

}