<?php

namespace ApiManager\Server;

use ApiManager\Extension\ContextExtension;
use ApiManager\Http\Path;
use ApiManager\Http\Request;
use ApiManager\Http\Response;

class Container{

    private $path_alias = [];
    private $callback_start = null;
    private $callback_success = null;
    private $callback_error = null;
    private $callback_resolved = null;
    private $resolved = false;

    public function __construct(
        private string $path,
        private ContextExtension $context, 
        private string|null $method = null       
    ){}

    public function __get($prop){
        if(property_exists($this, $prop)){
            return $this->{$prop};
        }
    }

    public function alias(string $path){
        $path = Path::concat($path);
                
        $this->path_alias[$path] = $path;   
        
        return $this;
    }
    
    public function onStart(callable $callback):self {
        $this->callback_start = $callback;

        return $this;
    }

    public function onSuccess(callable $callback):self {
        $this->callback_success = $callback;

        return $this;
    }

    public function onError(callable $callback):self {
        $this->callback_error = $callback;

        return $this;
    }

    public function onResolved(callable $callback):self {
        $this->callback_resolved = $callback;

        return $this;
    }
    
    public function run(Request $request, Response $response){
        if($this->method && strtoupper($request->httpMethod()) != strtoupper($this->method)){
            return;    
        }
        
        $path_container = null;
        
        if(Path::hasPrefixPath($this->path, $request->originalUrl())){
            $path_container = $this->path;   
        }
        else{
            foreach($this->path_alias as $path){
                if(Path::hasPrefixPath($path, $request->originalUrl())){
                    $path_container = $path;                    
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

            if($this->callback_success){
                call_user_func($this->callback_success, $request, $response);
            }
        }
        catch(\Throwable $e){
            if($this->callback_error){
                call_user_func($this->callback_error, $request, $response, $e);
                return;
            }

            throw $e;
        }
        finally{
            $this->resolved = true;

            if($this->callback_resolved){
                call_user_func($this->callback_resolved, $request, $response);
                return;
            }
        }
    }

}