<?php

namespace ApiManager\Context;

use ApiManager\Extension\ContextExtension;
use ApiManager\Http\Path;
use ApiManager\Http\Request;
use ApiManager\Http\Response;

class ServeStatic implements ContextExtension{

    private $dotfiles  = 'ignore';
    private $extensions = [];
    private $lastmodified = true; 
    private $headers = []; 
    private $expires = 0;
    private $cachecontrol = 'must-revalidate, post-check=0, pre-check=0';
    private $indexfile = true;  

    public function __construct(private string $rootdir){}

    public function setDotFiles(string $option){
        if(in_array($option, ['allow', 'deny', 'ignore'])){
            $this->dotfiles = $option;
        }

        return $this;
    }

    public function setExtensions(string ...$extensions){
        $this->extensions = $extensions;

        return $this;
    }

    public function disableLastModified(){
        $this->lastmodified = false;

        return $this;
    }

    public function setHeader(string $key, string $value){
        $this->headers[$key] = $value;

        return $this;
    }

    public function setExpires(string|int $expires){
        $this->expires = $expires;

        return $this;
    }

    public function setCacheControl(string $cachecontrol){
        $this->cachecontrol = $cachecontrol;

        return $this;
    }

    public function disableIndex(){
        $this->indexfile = false;

        return $this;
    }

    public function process(Request $req, Response $res){
        if(!is_dir($this->rootdir)){
            throw new \ApiManager\Exception\FileNotFoundException('Static directory not found');
        }        

        $filename = Path::trim($this->rootdir).'/'.Path::trim($req->getPath());
        
        if(!file_exists($filename)){
            throw new \ApiManager\Exception\FileNotFoundException('The file is unavailable or does not exist');
        }
        
        $is_index = false;
        if(
            $this->indexfile && 
            is_dir($filename) && 
            file_exists($filename.'/index.html')
        ){
            $filename = $filename.'/index.html';
            $is_index = true;
        }

        if(is_file($filename)){
            $file = new \ApiManager\Provider\FileInfo($filename);  
            
            if($file->dotfile){
                if($this->dotfiles == 'ignore'){
                    $res->status(404)->end();
                }
                
                if($this->dotfiles == 'deny'){
                    $res->status(403)->end();
                }
            }

            if(
                !empty($this->extensions) && 
                !in_array($file->extension, $this->extensions) && 
                !$is_index)
            {
                $res->status(403)->end();    
            }

            foreach($this->headers as $key => $value){
                $res->set($key, $value);
            }

            if($this->lastmodified){
                $last_modified_time = filemtime($filename);
                $res->set("Last-Modified", gmdate("D, d M Y H:i:s", $last_modified_time)." GMT");    
            }

            $res->set("Content-disposition", "inline; filename=\"{$file->basename}\"");
            $res->set("Content-Length", $file->size);
            $res->set("Content-Transfer-Encoding", "binary");
            $res->set("Expires", $this->expires);
            $res->set("Cache-Control", $this->cachecontrol);           
            $res->type($file->extension);
            $res->end(file_get_contents($filename));            
        }

        $res->status(404)->end();
    }

}