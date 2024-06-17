<?php

namespace ApiManager\Http;

class Response{

    private $body;
    

    public function headersSent():bool {
        return headers_sent();
    }
    
    public function getHeaders():Array {
        return headers_list();    
    }    

    public function getCode():int {
        $code = http_response_code();

        return is_bool($code) ? 200 : $code;
    }

    public function getBodyContent():?string {
        return $this->body;
    }

    public function status(int $code):self {
        http_response_code($code);

        return $this;
    }

    public function append(string $field, string $value):self {
        header("{$field}: $value", false);

        return $this;
    }    

    public function set(string $field, string $value):self {
        header("{$field}: $value");

        return $this;
    }

    public function get(string $field){
        foreach($this->getHeaders() as $header){
            list($header_field, $header_value) = explode(':', $header);

            if(strtolower($field) == strtolower($header_field)){
                return trim($header_value);
            }
        }
    }     
    
    public function type(string $type):self {
        $mime = \ApiManager\Provider\Mime::get($type);

        $this->set('Content-Type', $mime ? $mime : $type);
        
        return $this;
    }

    public function links(Array $links):self {
        $items = [];
        
        foreach($links as $rel => $link){
            $items[] = "<{$link}>; rel=\"{$rel}\"";
        }

        $this->set('Link', implode(', ', $items));

        return $this;
    }    

    public function send(mixed $body = null):self {
        if(!$body){
            return $this;
        }
        
        if(is_array($body)){
            $this->json($body);
        }
        else{
            $this->echo($body);
        }

        return $this;
    }

    public function sendStatus(int $statusCode):self {
        $this->status($statusCode);
        
        $this->echo(\ApiManager\Provider\HttpCode::getMessage($statusCode));

        return $this;
    }

    public function json(mixed $body, int $flags = 0):self {
        $this->type('json');
        
        $this->echo(json_encode($body, $flags));

        return $this;
    }
    
    public function end(mixed $body = null){
        $this->send($body);

        exit;
    }    
    
    public function render(string $html_path, Array $data_source = [], callable $error = null){
        try{
            if(!file_exists($html_path)){
                throw new \ApiManager\Exception\FileNotFoundException("File({$html_path}) not found");
            }

            $template = new \ApiManager\Provider\Renderer($html_path);
            $template->disableHtmlConversion();

            foreach($data_source as $data){
                $section = $data['section'] ?? null;
                $replacements = isset($data['replacements']) && is_array($data['replacements']) ? $data['replacements'] : null;
                $repeatable = isset($data['repeatable']) && is_bool($data['repeatable']) ? $data['repeatable'] : false;
                
                if($section){
                    $template->enableSection($section, $replacements, $repeatable);
                }                                      
            }

            $this->send($template->getContents());
        }
        catch(\Throwable $e){
            if($error){
                $error($e);
            }
            else{
                throw $e;
            }
        }
    }

    public function attachment(string $filename = null, bool $inline = false){
        $disposition = $inline ? 'inline' : 'attachment';
        
        if($filename){
            if(file_exists($filename)){
                $file = new \ApiManager\Provider\FileInfo($filename);  

                $this->type($file->extension); 

                $disposition.= '; filename='.$file->basename;                
            }
            else{
                $disposition.= '; filename='.$filename;
            }
        }

        $this->set('Content-Disposition', $disposition);
    }

    public function download(string $path, string $filename = null, callable $exception_handler = null){
        try{
            if(!file_exists($path)){
                throw new \ApiManager\Exception\FileNotFoundException("File({$filename}) not found");
            }

            $file = new \ApiManager\Provider\FileInfo($path);
            
            if(!$filename){
                $filename = $file->basename;
            }

            $this->set('Content-Disposition', "attachment; filename={$filename}");
            $this->set("Content-Length", $file->size);
            $this->type($file->extension);
            $this->send($file->content);
        }            
        catch(\Throwable $e){
            if($exception_handler){
                call_user_func($exception_handler, $this, $e);
                
                return;
            }    
            
            throw $e;
        }
    }    

    public function cookie(string $name, mixed $value, Array $options = []){
        if(is_array($value)){
            $value = json_encode($value);
        }

        $expires  = isset($options['expires']) && is_int($options['expires']) ? $options['expires'] : 0;
        $path     = isset($options['path']) && is_string($options['path']) ? $options['path'] : '';
        $domain   = isset($options['domain']) && is_string($options['domain']) ? $options['domain'] : '';
        $secure   = isset($options['secure']) && is_bool($options['secure']) ? $options['secure'] : false;
        $httponly = isset($options['httponly']) && is_bool($options['httponly']) ? $options['httponly'] : false;

        setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
    }

    public function clearCookie(string $name){
        setcookie($name, "", time() - 3600);
    }    

    public function redirect(string $path, int $statusCode = 302){
        if(!$this->headersSent()){
            if($path[0] == '/'){
                $req = new Request;
                $path = $req->protocol().'://'.$req->serverName().$path;
            }
            
            header("Location: {$path}", true, $statusCode);
        }
    }
    
    private function echo(string $content){
        $this->body.= $content;
        
        echo $content;
    }

}