<?php

namespace ApiManager\Provider;

class FileInfo{

    private $path; 
    private $dirname;
    private $extension;
    private $basename;
    private $size;
    private $dotfile;

    public function __construct(string $path){
        $info = pathinfo($path);
        
        $this->path = $path;
        $this->dirname = $info['dirname'];
        $this->extension = $info['extension'];
        $this->basename = basename($path);
        $this->size = filesize($path);
        $this->dotfile = $this->basename[0] == '.';
    }

    public function __get(string $property){
        if($property == 'content'){
            return file_get_contents($this->path);
        }

        if(property_exists($this, $property)){
            return $this->{$property};
        }
    }

}