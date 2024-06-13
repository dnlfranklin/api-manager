<?php

namespace ApiManager\Server;

use ApiManager\Extension\ContextExtension;
use ApiManager\Http\Path;
use ApiManager\Http\Request;
use ApiManager\Http\Response;

class Container{

    private $redirect_path = null;
    private $callback_start = null;
    private $callback_complete = null;
    private $callback_error = null;

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

    public function setRedirectPath(string $redirect_path){
        $this->redirect_path = $redirect_path;
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
        $path = $this->path;
        
        if(!Path::hasPrefixPath($path, $request->originalUrl())){
            if($this->redirect_path){
                $path = $this->redirect_path;

                if(!Path::hasPrefixPath($path, $request->originalUrl())){
                    return;
                }
            }
            else{
                return;
            }            
        }

        if($this->method && strtoupper($request->httpMethod()) != strtoupper($this->method)){
            return;    
        }

        try{
            $request->setBaseUrl($path);

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