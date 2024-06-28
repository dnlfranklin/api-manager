<?php

use ApiManager\Extension\MiddlewareExtension;
use ApiManager\Http\Request;
use ApiManager\Http\Response;

class MyMiddleware implements MiddlewareExtension{
    
    public function handle(Request $req, Response $res, \Closure $next){
        $count = $req->getParams()['count_middleware_call'] ?? 0;
        
        $req->setParam('count_middleware_call', ($count + 1));

        $next($req, $res);
    }
}