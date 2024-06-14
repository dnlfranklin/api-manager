<?php

namespace ApiManager\Server;

use ApiManager\Extension\ContextExtension;
use ApiManager\Http\Path;
use ApiManager\Http\Request;
use ApiManager\Http\Response;

class Container{

    private $paths = [];
    private $redirect_paths = [];
    private $callback_start = null;
    private $callback_complete = null;
    private $callback_error = null;

    public function __construct(
        string|Array $path,
        private ContextExtension $context, 
        private string|null $method = null       
    ){
        is_string($path) ? $this->addPath($path) : array_map([$this, 'addPath'], $path);
    }

    public function __get($prop){
        if(property_exists($this, $prop)){
            return $this->{$prop};
        }
    }

    public function addPath(string $path){
        $key = empty($path) || $path == '/' ? '/' : Path::trim($path);
        
        $this->paths[$key] = $path;
    }

    public function addRedirectPath(string $redirect_from, string $redirect_path){
        $key = empty($redirect_path) || $redirect_path == '/' ? '/' : Path::trim($redirect_path);
                
        if(array_key_exists($key, $this->paths)){
            $this->redirect_paths[$key][] = $redirect_from;
        }
    }
    
    public function onStart(callable $callback):self {
        $this->callback_start = $callback;

        return $this;
    }

    public function onComplete(callable $callback):self {
        $this->callback_complete = $callback;

        return $this;
    }

    public function onError(callable $callback):self {
        $this->callback_error = $callback;

        return $this;
    }
    
    public function run(Request $request, Response $response){
        if($this->method && strtoupper($request->httpMethod()) != strtoupper($this->method)){
            return;    
        }
        
        $path_container = null;
        
        foreach($this->paths as $key => $path){
            if(Path::hasPrefixPath($path, $request->originalUrl())){
                $path_container = $path;           
                break;     
            }
            else{
                $redirects = $this->redirect_paths[$key] ?? null;

                if($redirects){
                    foreach($redirects as $redirect){
                        if(Path::hasPrefixPath($redirect, $request->originalUrl())){
                            $path_container = $redirect;
                            break 2;
                        }
                    }
                }
            }
        }

        if(!$path_container){
            return;      
        }

        try{
            $request->setBaseUrl($path_container);
            
            if($this->callback_start){
                call_user_func($this->callback_start, $request, $response);
            }
            
            $this->context->process($request, $response);                           

            if($this->callback_complete){
                call_user_func($this->callback_complete, $request, $response);
            }
        }
        catch(\Throwable $e){
            if($this->callback_error){
                call_user_func($this->callback_error, $request, $response, $e);
                return;
            }

            throw $e;
        }
    }

}