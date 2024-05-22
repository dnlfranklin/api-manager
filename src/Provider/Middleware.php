<?php

namespace ApiManager\Provider;

use ApiManager\Http\Data;

abstract class Middleware {
    
    private $http_data;

    public function getHttpData(){
        return $this->http_data;
    }
    
    public function setHttpData(Data $http_data){
        $this->http_data = $http_data;
    }

    protected function next(string $key, mixed $value){
        $this->http_data->addMiddlewareParam($key, $value);
    }

    abstract public function process();
    
}