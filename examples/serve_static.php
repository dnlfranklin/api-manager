<?php

/**
 * Serve arquivos a partir de um diretório pré-determinado
 */

use ApiManager\Context\ServeStatic;
use ApiManager\Server\App;

require '../vendor/autoload.php';

// Mude para rota desejável de Testes
$_SERVER['REQUEST_URI'] =  '/public';


$static = new ServeStatic('./files');
$static->setDotFiles('deny')
       ->setExtensions('txt');

$app = new App;
$app->use('/public', $static);
$app->init();
