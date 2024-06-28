<?php

require '../vendor/autoload.php';
require './MyController.php';
require './MyMiddleware.php';

use ApiManager\Context\Router\Router;
use ApiManager\Http\RouteGroup;
use ApiManager\Server\App;

//$_SERVER['REQUEST_URI'] = '/mygroup/myroutegroup/2';

$router = new Router;
$router->middleware(MyMiddleware::class);

//Roteamendo padrão
$router->get('/myroute', [new MyController, 'index']); 
//Rota com Query Param
$router->post('myroute/{id}', ['MyController', 'index']); 
//Executa um callback para chamada /DELETE a partir de /
$router->map(['DELETE'], '/', function($req, $res, $args){
    $req->setBodyParam('call_delete', true);    
});
$router->delete('myroute/{id}', ['MyController', 'index']); 
//Rota com Middleware
$router->post('/with-middleware', ['MyController', 'index'], [MyMiddleware::class]);
//Trabalhando com grupo de routas
$router->group('/mygroup', function(RouteGroup $group){
    //Executa um callback para chamada /GET do grupo
    $group->map('GET', '/', function($req, $res, $args){
        $req->setBodyParam('route_from_group', true);    
    });
    //Adiciona rotas ao grupo (/mygroup/myroutegroup)
    $group->get('/myroutegroup', [new MyController, 'index']);
    //Adiciona rota com Middlaware dentro do grupo
    $group->get('/myroutegroup/{id}', [new MyController, 'index'], [new MyMiddleware]);
    // Passando middleware global para todas as rotas do grupo
    $group->middleware(MyMiddleware::class);
});

//Instancia o app
$app = new App;
// Para rotas iniciados em /api será utilizado o $router para resolução e em caso de erro imprime em tela
$app->use('/', $router)
    ->onError(function($req, $res, $err){
        $res->status(400)
            ->json(['error' => $err->getMessage()]);
    });
$app->run();