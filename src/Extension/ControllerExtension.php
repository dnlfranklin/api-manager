<?php

namespace ApiManager\Extension;

use ApiManager\Http\Request;
use ApiManager\Http\Response;

interface ControllerExtension{

    public static function index(Request $req, Response $res, Array $args);

}