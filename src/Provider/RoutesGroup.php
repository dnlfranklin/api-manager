<?php

namespace ApiManager\Provider;

use Exception;
use ApiManager\Http\Route;

class RoutesGroup {

    private $routes = [];
    private $unique_keys = [];

    public function add(Route $route){
        $endpoint = $route->getEndpoint();
        $http_method = $route->getHttpMethod();
        
        if( !empty($this->unique_keys[$endpoint][$http_method]) ){
            throw new Exception("Duplicate route not allowed: {$endpoint} ({$http_method})");    
        }

        $this->routes[] = $route;
        $this->unique_keys[$endpoint][$http_method] = true;
    }

    public function addGroup( RoutesGroup $group, $prefix = '' ){
        foreach( $group->getRoutes() as $route ){
            if( !empty($prefix) ){
                $route->setEndpoint( $prefix.$route->getEndpoint() );
            }    
            $this->add($route);
        }    
    }

    public function getRoutes(){
        return $this->routes;    
    }

    public static function get_static(){
        return new static();    
    }
    
}