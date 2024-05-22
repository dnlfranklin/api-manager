<?php

namespace ApiManager\Provider;

abstract class ErrorHandler {

  abstract public static function handle($errno, $errstr, $errfile, $errline);

}