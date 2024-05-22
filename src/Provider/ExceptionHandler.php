<?php

namespace ApiManager\Provider;


abstract class ExceptionHandler {

    private $code;
    private $message;

    abstract public function handle(\Throwable $exception);
    
    public function getCode(){
        return $this->code;
    }

    public function getMessage(){
        return $this->message;
    }

    protected function setCode(int $code){
        $this->code = $code;
    }

    protected function setMessage($message){
        $this->message = $message;
    } 

    protected function getExceptionClass(\Throwable $e){
        return get_class($e);    
    }

}