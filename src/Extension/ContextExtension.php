<?php

namespace ApiManager\Extension;

use ApiManager\Http\Request;
use ApiManager\Http\Response;

interface ContextExtension{

    public function process(Request $req, Response $res);

}