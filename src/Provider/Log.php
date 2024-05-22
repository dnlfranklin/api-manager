<?php

namespace ApiManager\Provider;

use ApiManager\Log\LogData;

abstract class Log{

    abstract public function register(LogData $data):void;

}