<?php

namespace ApiManager\Http;

class Path{

    public static function trim(string $path):string {
        return trim($path, '/');  
    }
    
    public static function concat(string ...$paths):string {
        $concat = '';
    
        foreach($paths as $path){
            $concat.= '/';
            $concat.= self::trim($path);
        }  
    
        return $concat;
    }      
    
    public static function hasPrefixPath(string $path_prefix, string $path_target):bool {
        $path_prefix = self::trim($path_prefix);
        $path_target = self::trim($path_target);
        

        return str_starts_with($path_target, $path_prefix);
    }

    public static function removePrefix(string $path_prefix, string $path_target):string {
        $path_prefix = self::trim($path_prefix);
        $path_target_format = self::trim($path_target);        

        if($path_prefix == '' || !str_starts_with($path_target_format, $path_prefix)){
            return $path_target;
        }

        return substr($path_target_format, strlen($path_prefix));        
    }

}