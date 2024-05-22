<?php

namespace ApiManager\Http;

use ApiManager\Provider\RoutesGroup;

class Path {

  private $path;
  private $default_routes;
  private $prefixes = [];

  public function __construct($path, RoutesGroup $routes = null){
    $this->path = trim($path, '/');
    $this->default_routes = empty($routes) ? new RoutesGroup : $routes;
  }

  public function getPath(){
    return $this->path;
  }

  public function setPath($path){
    $this->path = trim($path, '/');  
  } 

  public function addCustomPrefix($prefix, RoutesGroup|null $routes = null){    
    $prefix = trim($prefix, '/');
    $this->prefixes[$prefix] = empty($routes) ? $this->default_routes : $routes; 
  }  

  public function getPathPart($prefix_sufix){
    $prefix = null;
    $sufix = $this->path;        
    
    foreach( $this->prefixes as $item => $routes ){
        $itembar = $item.'/';        
        $len = strlen($itembar);

        if( substr($this->path, 0, $len) == $itembar || $this->path == $item ){
            $prefix = $item;
            $sufix = substr($this->path, $len);  
        }        
    }
    
    return $prefix_sufix == 'prefix' ? $prefix : trim($sufix, '/');
  }

  public function getPathPrefix($with_bar = false){
    $prefix = $this->getPathPart('prefix');
    return $with_bar ? $prefix.'/' : $prefix;
  }

  public function getPathSufix($with_bar = false){
    $sufix = $this->getPathPart('sufix'); 
    return $with_bar ? '/'.$sufix : $sufix;   
  }

  public function getPathRoutes(){
    $prefix = $this->getPathPrefix();
    
    $group = empty($prefix) ? $this->default_routes : $this->prefixes[$prefix];

    return $group->getRoutes();
  }

}