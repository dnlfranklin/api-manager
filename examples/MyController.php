<?php

use ApiManager\Extension\ControllerExtension;
use ApiManager\Http\Request;
use ApiManager\Http\Response;

class MyController implements ControllerExtension{
    
    public static function index(Request $req, Response $res, Array $args = []){
        $args['route_path'] = $req->originalUrl();
        $args['route_method'] = $req->httpMethod();
        
        $res->status(200)->json($args);
    }
}