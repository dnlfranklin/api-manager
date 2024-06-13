<?php

namespace ApiManager\Server;

use ApiManager\Context\Closured;
use ApiManager\Extension\ContextExtension;
use ApiManager\Http\Path;
use ApiManager\Http\Request;
use ApiManager\Http\Response;

class App{

    private $registered_containers = [
        'priority' => [],
        'default'  => []
    ];
    private $redirects = [];
    

    public function get(string $path, callable $callback, callable $exception_handler = null):Container {
        return $this->container($path, new Closured(\Closure::fromCallable($callback)), 'GET', $exception_handler);
    }

    public function post(string $path, callable $callback, callable $exception_handler = null):Container {
        return $this->container($path, new Closured(\Closure::fromCallable($callback)), 'POST', $exception_handler);
    }

    public function put(string $path, callable $callback, callable $exception_handler = null):Container {
        return $this->container($path, new Closured(\Closure::fromCallable($callback)), 'PUT', $exception_handler);
    }

    public function delete(string $path, callable $callback, callable $exception_handler = null):Container {
        return $this->container($path, new Closured(\Closure::fromCallable($callback)), 'DELETE', $exception_handler);
    }

    public function patch(string $path, callable $callback, callable $exception_handler = null):Container {
        return $this->container($path, new Closured(\Closure::fromCallable($callback)), 'PATCH', $exception_handler);
    }

    public function all(string $path, callable $callback, callable $exception_handler = null):Container {
        return $this->container($path, new Closured(\Closure::fromCallable($callback)), null, $exception_handler);
    }

    public function use(string $path, ContextExtension|callable $context, callable $exception_handler = null):Container {
        if(is_callable($context)){
            $context = new Closured(\Closure::fromCallable($context));    
        }

        return $this->container($path, $context, null, $exception_handler, false);
    }

    public function redirect(string $path_from, string $path_to){
        $this->redirects[Path::trim($path_to)] = Path::trim($path_from);
    }

    public function init(bool $keep_path_tree = true){
        $priority_containers = $this->registered_containers['priority'];
        $default_containers = $this->registered_containers['default'];
        
        if($keep_path_tree){
            usort($default_containers, function ($a, $b) {
                return $a->path <=> $b->path;
            });
        }

        $request  = new Request;
        $response = new Response;

        foreach($priority_containers as $container){
            $container_path = Path::trim($container->path);
            if(array_key_exists($container_path, $this->redirects)){
                $container->setRedirectPath($this->redirects[$container_path]);
            }

            $container->run($request, $response);
        }        
        
        foreach($default_containers as $container){
            $container_path = Path::trim($container->path);
            if(array_key_exists($container_path, $this->redirects)){
                $container->setRedirectPath($this->redirects[$container_path]);
            }

            $container->run($request, $response);
        }        
    }

    private function container(
        string $path, 
        ContextExtension $context, 
        string $method = null, 
        callable $exception_handler = null,
        bool $priority = true
    ){
        $container = new Container($path, $context, $method);
        
        if($exception_handler){
            $container->onError($exception_handler);
        }

        $key = $priority ? 'priority' : 'default';

        $this->registered_containers[$key][] = $container;        

        return $container;        
    }

}
