# API Manager

Gerenciador de APIs para tratar requisições e respostas HTTP.

## Instalação

Para instalar esta dependência basta executar o comando abaixo:
```shell
composer require bonuscred/api-manager
```

## Configuração

### Apache

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
	
	#Main Redirect	
    RewriteRule ^ index.php [NC,L,QSA]
</IfModule>
```

## Utilização

### Aplicação

```php
$app = new ApiManager\Server\App;

$app->use('/', function(){
    echo 'Hello World';
});

$app->get('/some-get-route', function($req, $res){
    echo 'Call GET: /some-get-route';
});

$app->post('/some-post-route', function($req, $res){
    echo 'Call POST: /some-post-route';
});

$app->all('/some-route', function($req, $res){
    echo 'Call: /some-route';
});

$app->run();
```

#### Hooks

Métodos gancho dão a flexibilidade no tratamento e manipulação dos dados em cada momento da execução.

```php
$app = new ApiManager\Server\App;

$cont = $app->use('/', function(){});
$cont->onStart(function($req, $res){
    //Executa callback antes da execução do container
});
$cont->onSuccess(function($req, $res){
    //Executa callback se o container resolveu com sucesso
});
$cont->onError(function($req, $res, $err){
    //Executa callback se o container encontrou algum erro
    //$err é uma extensão de Throwable   
});
$cont->onResolved(function($req, $res){
    //Executa callback ao finalizar container  
});
```

#### Alias

É comum casos de migração de prefixos de entradas, usando alias é possivel manter o mesmo comportamento do container em diversos prefixos de entrada.

```php
$app = new ApiManager\Server\App;

$app->use('/api', function(){})
    ->alias('/oldapi');
```

#### Redirect

Redireciona de forma objetiva para outras urls.

```php
$app = new ApiManager\Server\App;

$app->redirect('/path/to/be/redirected', 'https://google.com.br', 302);
```

### Roteamento

```php
// Recebe como parâmetro um boleano(true como valor padrão) 
// para informar se o router deve resolver apenas a primeira rota encontrada
// ou todas as rotas compatíveis com o método e url
$router = new ApiManager\Context\Router\Router(true);

$router->get('/myroute', function($req, $res, $args){
            $response->status(200)
                    ->json(['success' => true]);
        });
$router->post('/my-post-route', [MyController::class, 'index']);
//Só execetada se router permitir execução de todas as correspondências
$router->get('/myroute', [MyController::class, 'index']);

$app = new ApiManager\Server\App;
$app->use('/', $router);
$app->run();
```

#### Grupo de rotas

É possivel agrupar rotas por um prefixo e aplicar condições globais a todas as rotas do grupo. 

```php
$router = new ApiManager\Context\Router\Router(true);

$router->group('/mygroup', function($group){
    //Executa um callback para chamadas com método GET do grupo
    $group->map('GET', '/', function($req, $res, $args){
        $req->setBodyParam('method_get_on_group', true);    
    });

    $group->get('/myroutegroup', ['MyController', 'index']);
    $group->post('/myroutegroup', ['MyController', 'index']);
    
    //Adiciona rota com Middlaware dentro do grupo
    $group->get('/myroutegroup/{id}', ['MyController', 'index']);
    // Passando middleware global para todas as rotas do grupo
    $group->middleware(MyMiddleware::class);
});
```

#### Middlewares

#### Criando um middleware

Deve obrigatoriamente implementar a interface MiddlewareExtension. 

A função next() tem a responsabilidade de executar o próximo middleware da fila, caso não deva executar nenhum middleware após, basta omiti-lo que o fluxo do roteamento seguirá para o Controlador.

```php
<?php

use ApiManager\Extension\MiddlewareExtension;
use ApiManager\Http\Request;
use ApiManager\Http\Response;

class MyMiddleware implements MiddlewareExtension{
    
    public function handle(Request $req, Response $res, \Closure $next){
        $authorization = $req->getHeaderLine('Authorization');

        if(!$authorization){
            throw new Exception('Header Authorization não enviado.');
        }

        //Executa o próximo middleware da fila
        $next($req, $res);
    }
}
```

#### Injetando um middleware

Middlewares podem ser usados em diversos camada do roteamento.

```php
$router = new ApiManager\Context\Router\Router;

//Middleware global para todas as rotas
$router->middleware(MyMiddleware::class);

//Middleware em rota específica
$router->get('/myroute', ['MyController', 'index'], [MyMiddleware::class]);

$router->group('/mygroup', function($group){
    //Middleware global para todas as rotas do grupo
    $group->middleware(MyMiddleware::class);
    
    //Middleware em rota específica do grupo
    $group->get(
        '/myroutegroup/{id}', 
        ['MyController', 'index'], 
        [MyMiddleware::class]
    );    
});
```

### Callbacks

Todos os callbacks receberão dois objetos: Request e Response, contendo as informações de entrada no servidor e de saída para o cliente requisitante, respectivamente.

### Controladores

Os controladores devem ser implementados como funções chamáveis recebendo os argumentos: Request, Response e $args. $args contém um array indexado com todos os dados de entrada (query, body, url e middlewares).

#### Com função anônima
```php
<?php

use ApiManager\Http\Request;
use ApiManager\Http\Response;

function meuControlador(Request $req, Response $res, $args){
    $args['route_path']   = $req->originalUrl();
    $args['route_method'] = $req->httpMethod();
    
    $res->status(200)->json($args);
}
```

#### Implementando ControllerExtension
```php
<?php

use ApiManager\Extension\ControllerExtension;
use ApiManager\Http\Request;
use ApiManager\Http\Response;

class MyController implements ControllerExtension{
    
    public static function index(Request $req, Response $res, $args = []){
        $args['route_path']   = $req->originalUrl();
        $args['route_method'] = $req->httpMethod();
        
        $res->status(200)->json($args);
    }

}
```

## Requisitos
- Necessário PHP 8.0 ou superior