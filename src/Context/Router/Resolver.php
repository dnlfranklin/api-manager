<?php

namespace ApiManager\Context\Router;

use ApiManager\Http\Path;
use ApiManager\Http\Request;
use ApiManager\Http\Route;
use ApiManager\Http\RouteGroup;
use ApiManager\Http\RouteMap;

class Resolver{

    private $method;
    private $route_path;
    private $request_path;
    private $params;
    private $middlewares;
    private $callbacks;
    
       
    public function __get($prop){
        if(property_exists($this, $prop)){
            return $this->{$prop};
        }
    }

    public function resolve(Router $router, Request $request){
        $this->method = strtoupper($request->httpMethod());
        $this->request_path = $request->getPath();
        $this->route_path = [];
        $this->params = [];
        $this->middlewares = [];
        $this->callbacks = [];
        
        foreach($router->registered_routes as $route_or_group){
            if(
                $router->require_first && 
                $this->route_path && 
                !$route_or_group instanceof RouteMap
            ){
                continue;
            }

            $this->fill($route_or_group, $router->require_first);
        }
        
        if(!$this->callbacks){
            throw new \ApiManager\Exception\RouterException("{$this->method}:{$this->request_path} not found in Router.");
        }
    }

    private function fill(Route|RouteGroup|RouteMap $route_or_group, bool $require_first){
        $routes = $route_or_group instanceof RouteGroup ? $route_or_group->routes : [$route_or_group];
        
        foreach($routes as $route){            
            if($route instanceof RouteMap){                
                if(
                    $route->hasMethod($this->method) && 
                    Path::hasPrefixPath($route->path, $this->request_path)
                ){
                    $this->callbacks[] = $route->callback;
                }
                continue;
            }    
            
            if($require_first && $this->route_path){
                continue;
            }
            
            if($this->method != strtoupper($route->method)){
                continue;
            }
            
            $path_base = Path::trim($route->path);
            $path_target = Path::trim($this->request_path);

            $base_peaces = explode('/', $path_base);
            $target_peaces = explode('/', $path_target);

            if(count($base_peaces) != count($target_peaces)){
                continue;
            }

            $params = [];            
                    
            foreach($base_peaces as $key => $level){
                if(substr($level, 0, 1) == '{' && substr($level, -1) == '}'){
                    $params[substr($level, 1, -1)] = $target_peaces[$key]; 
                    continue;
                }
                
                if($level != $target_peaces[$key]){
                    continue 2;
                }
            }

            $this->route_path[] = $route->path;
            $this->params+= $params;
            $this->callbacks[]= $route->callback;
            $this->middlewares+= $route_or_group instanceof RouteGroup ? array_merge($route_or_group->middlewares, $route->middlewares) : $route->middlewares;            
        }
    } 

}