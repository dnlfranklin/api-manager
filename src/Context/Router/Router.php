<?php

namespace ApiManager\Context\Router;

use ApiManager\Extension\ContextExtension;
use ApiManager\Http\Request;
use ApiManager\Http\Response;
use ApiManager\Http\Route;
use ApiManager\Http\RouteGroup;
use ApiManager\Http\RouteMap;

class Router implements ContextExtension{

    private $queue = [];
    private $registered_routes = [];
    

    public function __construct(private bool $require_first = true){}

    public function __get($prop){
        if(property_exists($this, $prop)){
            return $this->{$prop};
        }
    }

    public function get(string $path, callable $callback, Array $middlewares = []):self {
        $this->add(Route::get($path, $callback, $middlewares));
        
        return $this;
    }

    public function post(string $path, callable $callback, Array $middlewares = []):self {
        $this->add(Route::post($path, $callback, $middlewares));
        
        return $this;
    }

    public function put(string $path, callable $callback, Array $middlewares = []):self {
        $this->add(Route::put($path, $callback, $middlewares));
        
        return $this;
    }

    public function delete(string $path, callable $callback, Array $middlewares = []):self {
        $this->add(Route::delete($path, $callback, $middlewares));
        
        return $this;
    }

    public function patch(string $path, callable $callback, Array $middlewares = []):self {
        $this->add(Route::patch($path, $callback, $middlewares));
        
        return $this;
    }

    public function options(string $path, callable $callback, Array $middlewares = []):self {
        $this->add(Route::options($path, $callback, $middlewares));
        
        return  $this;
    }

    public function head(string $path, callable $callback, Array $middlewares = []):self {
        $this->add(Route::head($path, $callback, $middlewares));

        return $this;
    }

    public function group(string $path, callable $callback_group = null):RouteGroup {
        $group = new RouteGroup($path);
        
        if($callback_group){
            call_user_func($callback_group, $group);
        }

        $this->add($group);  
        
        return $group;
    }

    public function map(string|Array $method, string $path, callable $callback):self {
        $map_methods = is_string($method) ? [$method] : $method;
        
        $this->registered_routes[] = RouteMap::create(
            $path, 
            \Closure::fromCallable($callback),
            $map_methods
        );

        return $this;
    }

    public function add(Route|RouteGroup $route_or_group):Route|RouteGroup {
        $this->registered_routes[] = $route_or_group;

        return $route_or_group;
    }

    public function process(Request $request, Response $response){
        $resolver = new Resolver;
        $resolver->resolve($this, $request);

        foreach($resolver->params as $key => $value){
            $request->setQueryParam($key, $value);
        }            

        $this->queue = $resolver->middlewares;          
        
        $this->next($request, $response);

        foreach($resolver->callbacks as $callback){
            call_user_func($callback, $request, $response, $request->getAllParams());
        }      
    }

    public function next(Request $request, Response $response){
        if(!empty($this->queue)){
            $middleware = array_shift($this->queue);
    
            $router = $this;
            $next = function(Request $request, Response $response) use ($router){
                return $router->next($request, $response);
            };
    
            if(is_string($middleware)){
                $rc = new \ReflectionClass($middleware);
                return $rc->newInstance()->handle($request, $response, $next);
            }
            
            return $middleware->handle($request, $response, $next);
        }
    }

}