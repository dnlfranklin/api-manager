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
    

    public function get(Array|string $path, callable $callback, callable $exception_handler = null):Container {
        return $this->container($path, new Closured(\Closure::fromCallable($callback)), 'GET', $exception_handler);
    }

    public function post(Array|string $path, callable $callback, callable $exception_handler = null):Container {
        return $this->container($path, new Closured(\Closure::fromCallable($callback)), 'POST', $exception_handler);
    }

    public function put(Array|string $path, callable $callback, callable $exception_handler = null):Container {
        return $this->container($path, new Closured(\Closure::fromCallable($callback)), 'PUT', $exception_handler);
    }

    public function delete(Array|string $path, callable $callback, callable $exception_handler = null):Container {
        return $this->container($path, new Closured(\Closure::fromCallable($callback)), 'DELETE', $exception_handler);
    }

    public function patch(Array|string $path, callable $callback, callable $exception_handler = null):Container {
        return $this->container($path, new Closured(\Closure::fromCallable($callback)), 'PATCH', $exception_handler);
    }

    public function all(Array|string $path, callable $callback, callable $exception_handler = null):Container {
        return $this->container($path, new Closured(\Closure::fromCallable($callback)), null, $exception_handler);
    }

    public function use(Array|string $path, ContextExtension|callable $context, callable $exception_handler = null):Container {
        if(is_callable($context)){
            $context = new Closured(\Closure::fromCallable($context));    
        }

        return $this->container($path, $context, null, $exception_handler, false);
    }

    public function redirect(string $path_from, string $path_to){
        $this->redirects[Path::trim($path_from)] = $path_to;
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
            foreach($this->redirects as $from => $to){
                $container->addRedirectPath($from, $to);
            }

            $container->run($request, $response);
        }        
        
        foreach($default_containers as $container){
            foreach($this->redirects as $from => $to){
                $container->addRedirectPath($from, $to);
            }

            $container->run($request, $response);
        }        
    }

    private function container(
        Array|string $path, 
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
