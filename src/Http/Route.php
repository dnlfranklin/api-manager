<?php

namespace ApiManager\Http;

use ApiManager\Provider\Middleware;

class Route{

    private $endpoint;
    private $controller;
    private $method;
    private $middlewares;
    private $http_method;

    public function __construct(
        $http_method, 
        $endpoint, 
        $controller, 
        $method = NULL, 
        Array $middlewares = []
    ){
        $this->endpoint = $endpoint;
        $this->controller = $controller;
        $this->method = $method;
        $this->http_method = $http_method;        
        $this->setMiddlewares($middlewares);
    }

    public function getEndpoint(){
        return $this->endpoint;
    }

    public function setEndpoint($endpoint){
        $this->endpoint = $endpoint;
    }

    public function getController(){
        return $this->controller;
    }

    public function setController($controller){
        $this->controller = $controller;
    }
    
    public function getMethod(){
        return $this->method;
    }

    public function setMethod($method){
        $this->method = $method;
    }

    public function getMiddlewares(){
        return $this->middlewares;
    }

    public function setMiddlewares(Array $middlewares){
        foreach( $middlewares as $middleware ){
            if( !$middleware instanceof Middleware ){
                throw new \Exception('Middleware must be a instance of Middleware.');     
            }
        }

        $this->middlewares = $middlewares;
    }

    public function appendMiddleware(Middleware $middleware){
        $this->middlewares[] = $middleware;
    }        

    public function getHttpMethod(){
        return $this->http_method;
    }

    public function setHttpMethod($http_method){
        $this->http_method = $http_method;
    }

}