<?php

namespace ApiManager\Provider;

use Exception;

class Renderer{

    private $buffer;
    private $template;
    private $replacements;
    private $enabledSections;
    private $repeatSection;
    private $HTMLOutputConversion;
    
    /**
     * Constructor method
     * 
     * @param $path  HTML resource path
     */
    public function __construct(string $path)
    {
        $this->enabledSections = array();
        $this->buffer = array();
        $this->HTMLOutputConversion = true;
        $this->template = file_get_contents($path);
    }        
    
    /**
     * Creates HTML Renderer
     */
    public static function create($path, $replaces)
    {
        $html = new self($path);
        $html->enableSection('main', $replaces);
        return $html;
    }
    
    /**
     * Disable htmlspecialchars on output
     */
    public function disableHtmlConversion()
    {
        $this->HTMLOutputConversion = false;
    }
    
    /**
     * Enable a HTML section to show
     * 
     * @param $sectionName Section name
     * @param $replacements Array of replacements for this section
     * @param $repeat Define if the section is repeatable
     */
    public function enableSection($sectionName, $replacements = NULL, $repeat = FALSE)
    {
        $this->enabledSections[] = $sectionName;
        $this->replacements[$sectionName] = $replacements;
        $this->repeatSection[$sectionName] = $repeat;
    }
    
    /**
     * Diable section
     */
    public function disableSection($sectionName)
    {
        $this->enabledSections = array_diff($this->enabledSections, [$sectionName]);
        unset($this->replacements[$sectionName]);
        unset($this->repeatSection[$sectionName]);
    }
    
    /**
     * Replace the content with array of replacements
     * 
     * @param $replacements array of replacements
     * @param $content content to be replaced
     */
    private function replaceContent(&$replacements, $content)
    {
        if (is_array($replacements))
        {
            foreach ($replacements as $variable => $value)
            {
                if (is_scalar($value))
                {
                    $value_original = $value;
                    
                    if (substr($value,0,4) == 'RAW:')
                    {
                        $value = substr($value,4);
                    }
                    else if ($this->HTMLOutputConversion)
                    {
                        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');   // TAG value
                    }
                    
                    $content = str_replace('{$'.$variable.'}',  $value, $content);
                    $content = str_replace('{{'.$variable.'}}', $value, $content);
                    $content = str_replace('{$'.$variable.'|raw}',  $value_original, $content);
                    $content = str_replace('{{'.$variable.'|raw}}', $value_original, $content);
                }
                else if (is_object($value))
                {
                    if (method_exists($value, 'show'))
                    {
                        ob_start();
                        $value->show();
                        $output = ob_get_contents();
                        ob_end_clean();
                        
                        $content = str_replace('{$'.$variable.'}',  $output, $content);
                        $content = str_replace('{{'.$variable.'}}', $output, $content);
                        
                        $replacements[$variable] = 'RAW:' . $output;
                    }
                    
                    if (method_exists($value, 'getAttributes'))
                    {
                        $vars = $value->getAttributes();
                        $vars[] = $value->getPrimaryKey();
                    }
                    else if (!$value instanceof self)
                    {
                        $vars = array_keys(get_object_vars($value));
                    }
                    
                    if (isset($vars))
                    {
                        foreach ($vars as $propname)
                        {
                            if (is_scalar($variable.'->'.$propname))
                            {
                                $replace = $value->$propname;
                                if (is_scalar($replace))
                                {
                                    if ($this->HTMLOutputConversion)
                                    {
                                        $replace = htmlspecialchars($replace, ENT_QUOTES | ENT_HTML5, 'UTF-8');   // TAG value
                                    }
                                    
                                    $content = str_replace('{$'.$variable.'->'.$propname.'}',   $replace, $content);
                                    $content = str_replace('{{'.$variable.'->'.$propname.'}}',  $replace, $content);
                                }
                            }
                        }
                    }
                }
                else if (is_null($value))
                {
                    $content = str_replace('{$'.$variable.'}',  '', $content);
                    $content = str_replace('{{'.$variable.'}}', '', $content);
                }
                else if (is_array($value)) // embedded repeated section
                {
                    // there is a template for this variable
                    if (isset($this->buffer[$variable]))
                    {
                        $tpl = $this->buffer[$variable];
                        $agg = '';
                        foreach ($value as $replace)
                        {
                            $agg .= $this->replaceContent($replace, $tpl);
                        }
                        $content = str_replace('{{'.$variable.'}}', $agg, $content);
                    }
                }
            }
        }
        
        // replace some php functions
        $content = self::replaceFunctions($content);
        
        return $content;
    }
    
    /**
     * Show the HTML and the enabled sections
     */
    public function show()
    {
        $opened_sections = array();
        $sections_stack = array('main');
        $array_content = array();
        
        if ($this->template)
        {
            $content = $this->template;
                        
            $array_content = preg_split('/\n|\r\n?/', $content);
            $sectionName = null;
            
            // iterate line by line
            foreach ($array_content as $line)
            {
                $line_clear = trim($line);
                $line_clear = str_replace("\n", '', $line_clear);
                $line_clear = str_replace("\r", '', $line_clear);
                $delimiter  = FALSE;
                
                // detect section start
                if ( (substr($line_clear, 0,5)=='<!--[') AND (substr($line_clear, -4) == ']-->') AND (substr($line_clear, 0,6)!=='<!--[/') )
                {
                    $previousSection = $sectionName;
                    $sectionName = substr($line_clear, 5, strpos($line_clear, ']-->')-5);
                    $sections_stack[] = $sectionName;
                    $this->buffer[$sectionName] = '';
                    $opened_sections[$sectionName] = TRUE;
                    $delimiter  = TRUE;
                    
                    $found = self::recursiveKeyArraySearch($previousSection, $this->replacements);
                    
                    // turns section repeatable if it occurs inside parent section
                    if (isset($this->replacements[$previousSection][$sectionName]) OR
                        isset($this->replacements[$previousSection][0][$sectionName]) OR
                        isset($found[$sectionName]) OR
                        isset($found[0][$sectionName]) )
                    {
                        $this->repeatSection[$sectionName] = TRUE;
                    }
                    
                    // section inherits replacements from parent session
                    if (isset($this->replacements[$previousSection][$sectionName]) && is_array($this->replacements[$previousSection][$sectionName]))
                    {
                        $this->replacements[$sectionName] = $this->replacements[$previousSection][$sectionName];
                    }
                }
                // detect section end
                else if ( (substr($line_clear, 0,6)=='<!--[/') )
                {
                    $delimiter  = TRUE;
                    $sectionName = substr($line_clear, 6, strpos($line_clear, ']-->')-6);
                    $opened_sections[$sectionName] = FALSE;
                    
                    array_pop($sections_stack);
                    $previousSection = end($sections_stack);
                    
                    // embbed current section as a variable inside the parent section
                    if (isset($this->repeatSection[$previousSection]) AND $this->repeatSection[$previousSection])
                    {
                        $this->buffer[$previousSection] .= '{{'.$sectionName.'}}';
                    }
                    else
                    {
                        // if the section is repeatable and the parent is not (else), process replaces recursively
                        if ((isset($this->repeatSection[$sectionName]) AND $this->repeatSection[$sectionName]))
                        {
                            $processed = '';
                            // if the section is repeatable, repeat the content according to its replacements
                            if (isset($this->replacements[$sectionName]))
                            {
                                foreach ($this->replacements[$sectionName] as $iteration_replacement)
                                {
                                    $processed .= $this->replaceContent($iteration_replacement, $this->buffer[$sectionName]);
                                }
                                self::processAttribution($processed, $this->replacements);
                                print $processed;
                                $processed = '';
                            }
                        }
                    }
                    
                    $sectionName = end($sections_stack);
                }
                else if (in_array($sectionName, $this->enabledSections)) // if the section is enabled
                {
                    if (!$this->repeatSection[$sectionName]) // not repeatable, just echo
                    {
                        // print the line with the replacements
                        if (isset($this->replacements[$sectionName]))
                        {
                            print $this->replaceContent($this->replacements[$sectionName], $line . "\n");
                        }
                        else
                        {
                            print $line . "\n";
                        }
                    }

                }
                
                if (!$delimiter)
                {
                    if (!isset($sectionName))
                    {
                        $sectionName = 'main';
                        if (empty($this->buffer[$sectionName]))
                        {
                            $this->buffer[$sectionName] = '';
                        }
                    }
                    
                    $this->buffer[$sectionName] .= $line . "\n";
                }
            }
        }
        
        // check for unclosed sections
        if ($opened_sections)
        {
            foreach ($opened_sections as $section => $opened)
            {
                if ($opened)
                {
                    throw new Exception('The section '.$section.' was not closed properly');
                }
            }
        }
    }
    
    /**
     * Static search in memory structure
     */
    public static function recursiveKeyArraySearch($needle,$haystack)
    {
        if ($haystack)
        {
            foreach($haystack as $key=>$value)
            {
                if($needle === $key)
                {
                    return $value;
                }
                else if (is_array($value) && self::recursiveKeyArraySearch($needle,$value) !== false)
                {
                    return self::recursiveKeyArraySearch($needle,$value);
                }
            }
        }
        return false;
    }
    
    /**
     * Returns the HTML content as a string
     */
    public function getContents()
    {
        ob_start();
        $this->show();
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    /**
     * Replace a string with object properties within {pattern}
     * @param $content String with pattern
     * @param $object  Any object
     */
    public static function replace($content, $object, $cast = null, $replace_methods = false)
    {
        if ($replace_methods)
        {
            // replace methods
            $methods = get_class_methods($object);
            if ($methods)
            {
                foreach ($methods as $method)
                {
                    if (stristr($content, "{$method}()") !== FALSE)
                    {
                        $content = str_replace('{'.$method.'()}', $object->$method(), $content);
                    }
                }
            }
        }
        
        if (preg_match_all('/\{(.*?)\}/', $content, $matches) )
        {
            foreach ($matches[0] as $match)
            {
                $property = substr($match, 1, -1);
                
                if (strpos($property, '->') !== FALSE)
                {
                    $parts = explode('->', $property);
                    $container = $object;
                    foreach ($parts as $part)
                    {
                        if (is_object($container))
                        {
                            $result = $container->$part;
                            $container = $result;
                        }
                        else
                        {
                            throw new Exception('Trying to access a non-existent property '.$property);
                        }
                    }
                    $value = $result;
                }
                else
                {
                    $value    = $object->$property;
                }
                
                if ($cast)
                {
                    settype($value, $cast);
                }
                
                $content  = str_replace($match, (string) $value, $content);
            }
        }
        
        return $content;
    }
    
    /**
     * Evaluate math expression
     */
    public static function evaluateExpression($expression)
    {
        
        $expression = str_replace('+', ' + ', $expression);
        $expression = str_replace('-', ' - ', $expression);
        $expression = str_replace('*', ' * ', $expression);
        $expression = str_replace('/', ' / ', $expression);
        $expression = str_replace('(', ' ( ', $expression);
        $expression = str_replace(')', ' ) ', $expression);
        
        // fix sintax for operator followed by signal
        foreach (['+', '-', '*', '/'] as $operator)
        {
            foreach (['+', '-'] as $signal)
            {
                $expression = str_replace(" {$operator} {$signal} ", " {$operator} {$signal}", $expression);
                $expression = str_replace(" {$operator}  {$signal} ", " {$operator} {$signal}", $expression);
                $expression = str_replace(" {$operator}   {$signal} ", " {$operator} {$signal}", $expression);
            }
        }
        
        return $expression;
    }
    
    /**
     * replace some php functions
     */
    public static function replaceFunctions($content)
    {
        if ( (strpos($content, 'date_format') === false) AND (strpos($content, 'number_format') === false) AND (strpos($content, 'evaluate') === false) )
        {
            return $content;
        }
        
        preg_match_all('/evaluate\(([-+\/\d\.\s\(\))*]*)\)/', $content, $matches3);
        
        if (count($matches3)>0)
        {
            foreach ($matches3[0] as $key => $value)
            {
                $raw        = $matches3[0][$key];
                $expression = $matches3[1][$key];
                
                $result = self::evaluateExpression($expression);
                $content = str_replace($raw, $result, $content);
            }
        }
        
        $date_masks = [];
        $date_masks[] = '/date_format\(([0-9]{4}-[0-9]{2}-[0-9]{2}),\s*\'([A-z_\/\-0-9\s\:\,\.]*)\'\)/'; // 2018-10-08, mask
        $date_masks[] = '/date_format\(([0-9]{4}-[0-9]{2}-[0-9]{2}\s[0-9]{2}:[0-9]{2}:[0-9]{2}),\s*\'([A-z_\/\-0-9\s\:\.\,]*)\'\)/'; // 2018-10-08 10:12:13, mask
        $date_masks[] = '/date_format\(([0-9]{4}-[0-9]{2}-[0-9]{2}\s[0-9]{2}:[0-9]{2}:[0-9]{2}\.[0-9]+),\s*\'([A-z_\/\-0-9\s\:\.\,]*)\'\)/'; // 2018-10-08 10:12:13.17505, mask
        $date_masks[] = '/date_format\((\s*),\s*\'([A-z_\/\-0-9\s\:\.\,]*)\'\)/'; // empty, mask
        
        foreach ($date_masks as $date_mask)
        {
            preg_match_all($date_mask, $content, $matches1);
            
            if (count($matches1)>0)
            {
                foreach ($matches1[0] as $key => $value)
                {
                    $raw    = $matches1[0][$key];
                    $date   = $matches1[1][$key];
                    $mask   = $matches1[2][$key];
                    
                    if (!empty(trim($date)))
                    {
                        $content = str_replace($raw, date_format(date_create($date), $mask), $content);
                    }
                    else
                    {
                        $content = str_replace($raw, '', $content);
                        
                    }
                }
            }
        }
        
        preg_match_all('/number_format\(\s*([\d+\.\d]*)\s*,\s*([0-9])+\s*,\s*\'(\,*\.*)\'\s*,\s*\'(\,*\.*)\'\s*\)/', $content, $matches2);
        
        if (count($matches2)>0)
        {
            foreach ($matches2[0] as $key => $value)
            {
                $raw      = $matches2[0][$key];
                $number   = $matches2[1][$key];
                $decimals = $matches2[2][$key];
                $dec_sep  = $matches2[3][$key];
                $tho_sep  = $matches2[4][$key];
                
                if (!empty(trim($number)) || $number == '0')
                {
                    $content  = str_replace($raw, number_format($number, $decimals, $dec_sep, $tho_sep), $content);
                }
                else
                {
                    $content  = str_replace($raw, '', $content);
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Process variable attribution
     * @param $content Template content
     * @param $replacements Template variable replacements
     */
    public static function processAttribution($content, &$replacements)
    {
        $masks = [];
        $masks[] = '/\{\%\s*set\s*([A-z_]*)\s*\+=\s*([-+\/\d\.\s\(\))*]*) \%\}/';
        $masks[] = '/\{\%\s*set\s*([A-z_]*)\s*=\s*([-+\/\d\.\s\(\))*]*) \%\}/';
        
        foreach ($masks as $mask_key => $mask)
        {
            preg_match_all($mask, $content, $matches1);
            
            if (count($matches1)>0)
            {
                foreach ($matches1[0] as $key => $value)
                {
                    $variable   = $matches1[1][$key];
                    $expression = $matches1[2][$key];
                    
                    if ($mask_key == 0)
                    {
                        if (!isset($replacements['main'][$variable]))
                        {
                            $replacements['main'][$variable] = 0;
                        }
                        $replacements['main'][$variable] += (float) self::evaluateExpression($expression);
                    }
                    else if ($mask_key == 1)
                    {
                        $replacements['main'][$variable] = (float) self::evaluateExpression($expression);
                    }
                }
            }
        }        
    }

    
    
}
