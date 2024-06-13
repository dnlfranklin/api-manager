<?php

namespace ApiManager\Http;

use ApiManager\Extension\MiddlewareExtension;

class Route{
    
    const METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];

    private $middlewares = [];
    
    public function __construct(
        private string $method,
        private string $path,
        private \Closure $callback
    ){}
    
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

    public static function get(string $path, callable $callback, Array $middlewares = []):self {
        return self::create('GET', $path, $callback, $middlewares);
    }

    public static function post(string $path, callable $callback, Array $middlewares = []):self {
        return self::create('POST', $path, $callback, $middlewares);
    }

    public static function put(string $path, callable $callback, Array $middlewares = []):self {
        return self::create('PUT', $path, $callback, $middlewares);
    }

    public static function delete(string $path, callable $callback, Array $middlewares = []):self {
        return self::create('DELETE', $path, $callback, $middlewares);
    }

    public static function patch(string $path, callable $callback, Array $middlewares = []):self {
        return self::create('PATCH', $path, $callback, $middlewares);
    }

    public static function options(string $path, callable $callback, Array $middlewares = []):self {
        return self::create('OPTIONS', $path, $callback, $middlewares);
    }

    public static function head(string $path, callable $callback, Array $middlewares = []):self {
        return self::create('HEAD', $path, $callback, $middlewares);
    }

    public static function validateMethod(string $method){
        $method = strtoupper($method);

        if(!in_array($method, self::METHODS)){
            throw new \InvalidArgumentException('$method is not a valid HTTP Method');
        }

        return $method;
    }

    public static function create(string $method, string $path, callable $callback, Array $middlewares = []):self {
        $method = self::validateMethod($method);
        
        $route = new self($method, $path, \Closure::fromCallable($callback));

        foreach($middlewares as $middleware){
            $route->middleware($middleware);
        }

        return $route;
    }

}