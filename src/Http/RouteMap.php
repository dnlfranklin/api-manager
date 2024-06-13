<?php

namespace ApiManager\Http;


class RouteMap{

    private $methods = [];    

    public function __construct(
        private string $path, 
        private \Closure $callback)
    {}

    public function __get($prop){
        if(property_exists($this, $prop)){
            return $this->{$prop};
        }
    }

    public function addMethod(string $method){
        $method = Route::validateMethod($method);

        if(!in_array($method, $this->methods)){
            $this->methods[] = $method;
        }
    }

    public function hasMethod(string $method):bool {  
        return in_array(strtoupper($method), $this->methods);
    }

    public static function create(string $path, \Closure $callback, Array $methods):self {
        $map = new self($path, $callback);
                
        foreach($methods as $method){
            $map->addMethod($method);
        }

        return $map;
    }

}