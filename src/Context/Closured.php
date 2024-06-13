<?php

namespace ApiManager\Context;

use ApiManager\Extension\ContextExtension;
use ApiManager\Http\Request;
use ApiManager\Http\Response;

class Closured implements ContextExtension{

    public function __construct(private \Closure $callback){}

    public function process(Request $req, Response $res){
        $callback = $this->callback;
        
        call_user_func($callback, $req, $res);
    }

}