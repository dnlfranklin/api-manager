<?php

namespace ApiManager\Context\Router;

use ApiManager\Extension\ContextExtension;
use ApiManager\Http\Request;
use ApiManager\Http\Response;
use ApiManager\Http\Route;

class Router implements ContextExtension{

    private $queue = [];
    private $registered_routes = [];
    

    public function __construct(private bool $require_first = true){}

    public function __get($prop){
        if(property_exists($this, $prop)){
            return $this->{$prop};
        }
    }

    public function get(string $path, callable $callback, Array $middlewares = []):Route {
        return $this->add(Route::get($path, $callback, $middlewares));
    }   

    public function post(string $path, callable $callback, Array $middlewares = []):Route {
        return $this->add(Route::post($path, $callback, $middlewares));
    }

    public function put(string $path, callable $callback, Array $middlewares = []):Route {
        return $this->add(Route::put($path, $callback, $middlewares));
    }

    public function delete(string $path, callable $callback, Array $middlewares = []):Route {
        return $this->add(Route::delete($path, $callback, $middlewares));
    }

    public function patch(string $path, callable $callback, Array $middlewares = []):Route {
        return $this->add(Route::patch($path, $callback, $middlewares));
    }

    public function options(string $path, callable $callback, Array $middlewares = []):Route {
        return $this->add(Route::options($path, $callback, $middlewares));
    }

    public function head(string $path, callable $callback, Array $middlewares = []):Route {
        return $this->add(Route::head($path, $callback, $middlewares));
    }

    public function add(Route $route):Route {
        $this->registered_routes[] = $route;

        return $route;
    }

    public function group(string $path, callable $callback_group):\ApiManager\Http\RouteGroup {
        $group = new \ApiManager\Http\RouteGroup($path);
        
        if($callback_group){
            call_user_func($callback_group, $group);
        }

        $this->registered_routes[] = $group;  
        
        return $group;
    }    

    public function map(string|Array $method, string $path, callable $callback):void {
        $map_methods = is_string($method) ? [$method] : $method;
        
        $this->registered_routes[] = \ApiManager\Http\RouteMap::create(
            $path, 
            \Closure::fromCallable($callback),
            $map_methods
        );
    } 

    public function middleware(\ApiManager\Extension\MiddlewareExtension|string $middleware, string $name = null):self {
        if(!is_a($middleware, 'ApiManager\Extension\MiddlewareExtension', true)){            
            throw new \InvalidArgumentException('$middleware must implements MiddlewareExtension');
        }
        
        if(!$name){
            $name = uniqid(rand());
        }
        
        $this->queue[$name] = $middleware;

        return $this;
    }

    public function process(Request $request, Response $response){
        $resolver = new Resolver;
        $resolver->resolve($this, $request);

        foreach($resolver->params as $key => $value){
            $request->setParam($key, $value);
        }            

        $this->queue+= $resolver->middlewares;          
        
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