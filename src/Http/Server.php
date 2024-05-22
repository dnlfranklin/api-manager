<?php

namespace ApiManager\Http;

use ApiManager\Http\Path;
use ApiManager\Http\Data;
use ApiManager\Http\Route;
use ApiManager\Log\LogData;
use ApiManager\Provider\ErrorHandler;
use ApiManager\Provider\ExceptionHandler;
use ApiManager\Provider\Log;
use ApiManager\Provider\Middleware;
use ApiManager\Provider\Response;
use ApiManager\Provider\RoutesGroup;

class ServerException extends \Exception{} 

class Server {
    
    private $http_path;
    private $http_data;
    private $routes;
    private $middlewares;
    private $empty_route;
    private $notfound_route;
    private $exception_handler;
    private $use_request_class_method_param;
    private $is_static;
    private $log;
    private $start_time;

    public function __construct(Path $http_path, $id = null){
        $this->http_path = $http_path;
        $this->http_data = new Data($id, $this->http_path->getPathPrefix());
        $this->routes = [];
        $this->middlewares = [];
        $this->use_request_class_method_param = false;
        $this->is_static = false;
        $this->start_time = microtime(true);
    }

    public function setRoutes(RoutesGroup $routes_group){
        $this->routes = $routes_group->getRoutes();
    }    

    public function setEmptyRoute(Route $route){
        $this->empty_route = $route;
    }

    public function setNotfoundRoute(Route $route){
        $this->notfound_route = $route;
    }

    public function setExceptionHandler( ExceptionHandler $exception_handler ){
        $this->exception_handler = $exception_handler;
    }

    public function setErrorHandler( ErrorHandler $error_handler ){
        $error_handler = get_class($error_handler)."::handle";
        set_error_handler($error_handler);
    }

    public function appendMiddleware(Middleware $middleware){
        $this->middlewares[] = $middleware;
    }        

    public function enableRequestClassMethodParam(bool $option){
        $this->use_request_class_method_param = $option;
    }

    public function isStatic(){
        $this->is_static = true;    
    }

    public function enableLogRequest(Log $log){
        $this->log = $log;        
    }

    public function setCustomRedirect(
        string $route_or_regex, 
        string $redirect_url, 
        string $alias_replace_for_route_in_redirect_url = null,
        Array $http_methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
    ){
        $current_method = $this->http_data->getRequestMethod();
        $current_route = $this->http_path->getPathSufix();

        if( !in_array($current_method, $http_methods) ){
            return;
        }

        if( trim($route_or_regex, '/') == $current_route || preg_match("/{$route_or_regex}/", $current_route) ){
            if( !empty($alias_replace_for_route_in_redirect_url) ){
                $redirect_url = str_replace($alias_replace_for_route_in_redirect_url, $current_route, $redirect_url);
            }

            die( header("location: ".$redirect_url) );
        }           
    }

    private function validateRoutePath(Route $route){
        $http_data = $this->http_data;
        
        $route_key = trim($route->getEndpoint(), '/');
        $route_path = trim($this->http_path->getPathSufix(), '/');
        
        $route_key_peaces = explode('/', $route_key);
        $route_path_peaces = explode('/', $route_path);
        $query_params = [];

        if( count($route_key_peaces) != count($route_path_peaces) ){            
            return null;
        }
        
        if( !empty($route->getHttpMethod()) && 
            strtolower($route->getHttpMethod()) != strtolower($http_data->getRequestMethod()) 
        ){
            return null;   
        }

        foreach( $route_path_peaces as $route_path_level_key => $route_path_level_value ){
            $route_key_level_value = $route_key_peaces[$route_path_level_key];

            if( substr($route_key_level_value, 0, 1) == '{' && substr($route_key_level_value, -1) == '}'  ){
                if( empty($route_path_level_value) ){
                    throw new ServerException('Invalid URL parameters.', 400);
                }
                $query_params[ substr($route_key_level_value, 1, -1) ] = $route_path_level_value; 
                continue;
            }
            
            if( $route_path_level_value != $route_key_level_value ){
                return null;
            }    
        } 
        
        return $query_params;
    }

    private function getRouteByPath(){
        foreach( $this->routes as $route ){
            $validate_route_params = $this->validateRoutePath($route);          
            
            if( is_array($validate_route_params) ){
                $this->http_data->setQueryParams($validate_route_params);
                return $route;
            }
        }   
        
        return null;
    }

    private function getRouteByController(){
        if( !$this->use_request_class_method_param ){
            return null;        
        }

        $request = $this->http_data->getBodyParams();
        
        if( empty($request['class']) || empty($request['method']) ){
            return null;
        }
        
        $class = $request['class'];
        $method = $request['method'];

        foreach( $this->routes as $route ){
            if( strtolower($route->getHttpMethod()) != strtolower($this->http_data->getRequestMethod()) ) {
                continue;
            }
            
            if( !empty($route->getMiddlewares()) ){
                continue;
            }         
            
            if( $route->getController() == $class && $route->getMethod() == $method ){
                return $route;
            }                
        }    
        
        return null;
    }

    private function checkRoute(){        
        $route_path = $this->getRouteByPath();   
        if( $route_path instanceof Route ){
            return $route_path;
        }           

        $route_controller = $this->getRouteByController();
        if($route_controller instanceof Route){
            return $route_controller;   
        }

        if( empty($this->http_path->getPathSufix()) && !empty($this->empty_route) ){
            return $this->empty_route;
        }
        
        return $this->notfound_route;
    }

    public function browse(Response $response){  
        $send_to_service = false;
        
        try{
            // Percorre os middlewares na camada de servidor/HTTP
            foreach($this->middlewares as $middleware){
                $middleware->setHttpData($this->http_data);
                $middleware->process();
                
                $this->http_data = $middleware->getHttpData();
            }   
            
            if($this->is_static){
                $send_to_service = true;

                $response->setServerHttpData($this->http_data);
                $response->exec(null, null);    
            }
            else {
                if(empty($this->routes)){
                    $this->routes = $this->http_path->getPathRoutes();
                }

                $route = $this->checkRoute();
                
                if(!$route instanceof Route){
                    throw new ServerException('URL não encontrada ou método não permitido', 404);
                }

                // Percorre os middlewares das rotas
                foreach($route->getMiddlewares() as $middleware){
                    $middleware->setHttpData($this->http_data);
                    $middleware->process();

                    $this->http_data = $middleware->getHttpData();                
                }

                $send_to_service = true;
    
                $response->setServerHttpData($this->http_data);
                $response->exec($route->getController(), $route->getMethod());                
            }
        } catch(ServerException $e){
            $response->send($e->getCode(), $e->getMessage());
        } catch(\Throwable $e){
            $code = (int) $e->getCode();
            $message = $e->getMessage();
            
            if( !empty($this->exception_handler) ){
                $this->exception_handler->handle($e);
                
                if(!empty($this->exception_handler->getCode())){
                    $code = $this->exception_handler->getCode();
                }

                if(!empty($this->exception_handler->getMessage())){
                    $message = $this->exception_handler->getMessage();
                }
            } 
                        
            $response->send($code, $message);
        }
        finally{
            if($this->log){
                $end_time = microtime(true);
                $response_execution_time = ($end_time - $this->start_time);

                $logdata = new LogData;
                $logdata->parse($this->http_data);
                $logdata->parse($response);
                $logdata->response_execution_time = $response_execution_time;
                $logdata->send_to_service = $send_to_service;

                $this->log->register($logdata); 
            }
        }
    }

    public static function newStaticServer(Path $path, Response $response, Array $middlewares = [], Log $log = null){
        $server = new self($path);
        $server->isStatic();
        
        foreach($middlewares as $middleware){
            if( $middleware instanceof Middleware ){
                $server->appendMiddleware($middleware);    
            }
        }
        
        if($log){
            $server->enableLogRequest($log);
        }

        $server->browse($response);
    }

}