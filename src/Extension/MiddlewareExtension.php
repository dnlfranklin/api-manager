<?php

namespace ApiManager\Extension;

use ApiManager\Http\Request;
use ApiManager\Http\Response;

interface MiddlewareExtension{

    public function handle(Request $req, Response $res, \Closure $next);

}