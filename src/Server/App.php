<?php

namespace ApiManager\Server;

use ApiManager\Context\Closured;
use ApiManager\Context\Router\Router;
use ApiManager\Context\ServeStatic;
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

    public function redirect(string $path_from, string $path_to, int $status_code = 302){
        $this->redirects[Path::trim($path_from)] = [$path_to, $status_code];
    }

    public function run(){
        $priority_containers = $this->registered_containers['priority'];
        $default_containers = $this->registered_containers['default'];
        
        $request  = new Request;
        $response = new Response;

        foreach($this->redirects as $from => $to){
            if($from == Path::trim($request->originalUrl())){
                $response->redirect($to[0], $to[1]);    
            }
        }

        foreach($priority_containers as $container){
            $container->run($request, $response);
        }        
        
        $resolved = false;

        foreach($default_containers as $container){
            $context = $container->context;
            
            if(
                $resolved && 
                ($context instanceof ServeStatic || $context instanceof Router)
            ){
                continue;
            }
            
            $container->run($request, $response);

            if($context instanceof ServeStatic || $context instanceof Router){
                $resolved = $container->resolved;
            }                
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
