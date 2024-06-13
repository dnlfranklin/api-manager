<?php

namespace ApiManager\Http;

use ApiManager\Extension\MiddlewareExtension;

class RouteGroup{

    private $middlewares = [];
    private $routes = [];

    public function __construct(private string $path){}

    public function __get($prop){
        if(property_exists($this, $prop)){
            return $this->{$prop};
        }
    }

    public function middleware(MiddlewareExtension|string $middleware, string $name = null):self {
        if(!is_a($middleware, 'ApiManager\Extension\MiddlewareExtension', true)){            
            throw new \InvalidArgumentException('$middleware must implements MiddlewareExtension');
        }
        
        if(!$name){
            $name = uniqid(rand());
        }
        
        $this->middlewares[$name] = $middleware;

        return $this;
    }    

    public function get(string $path, callable $callback, Array $middlewares = []):self {
        $this->routes[] = Route::get(Path::concat($this->path, $path), $callback, $middlewares);

        return $this;
    }

    public function post(string $path, callable $callback, Array $middlewares = []):self {
        $this->routes[] = Route::post(Path::concat($this->path, $path), $callback, $middlewares);

        return $this;
    }

    public function put(string $path, callable $callback, Array $middlewares = []):self {
        $this->routes[] = Route::put(Path::concat($this->path, $path), $callback, $middlewares);

        return $this;
    }

    public function delete(string $path, callable $callback, Array $middlewares = []):self {
        $this->routes[] = Route::delete(Path::concat($this->path, $path), $callback, $middlewares);

        return $this;
    }

    public function patch(string $path, callable $callback, Array $middlewares = []):self {
        $this->routes[] = Route::patch(Path::concat($this->path, $path), $callback, $middlewares);

        return $this;
    }

    public function options(string $path, callable $callback, Array $middlewares = []):self {
        $this->routes[] = Route::options(Path::concat($this->path, $path), $callback, $middlewares);

        return $this;
    }

    public function head(string $path, callable $callback, Array $middlewares = []):self {
        $this->routes[] = Route::head(Path::concat($this->path, $path), $callback, $middlewares);

        return $this;
    }

    public function map(string|Array $method, string $path, callable $callback):self {
        $map_methods = is_string($method) ? [$method] : $method;
        
        $this->routes[] = RouteMap::create(
            $path, 
            \Closure::fromCallable($callback),
            $map_methods
        );

        return $this;
    }

}