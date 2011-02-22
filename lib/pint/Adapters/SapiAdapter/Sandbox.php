<?php

namespace pint\Adapters\SapiAdapter;

class Sandbox
{
    /**
     *
     * @var array
     */
    protected $globals = array(
        '_GET' => array(),    
        '_POST' => array(),    
        '_FILES' => array(),    
        '_SERVER' => array(),    
        '_COOKIE' => array(),    
    );
        
    /**
     *
     * @var boolean
     */
    protected $error = false;
        
    /**
     *
     * @var array
     */
    protected $bind = array();
        
    /**
     *
     * @var string
     */
    protected $output = "";
        
    public function __construct(array $globals = array())
    { 
        if(!empty($globals)) {
            $this->globals = $globals;
        }        
    }
        
    public function run($script)
    { 
        if(is_array($this->bind) || function_exist($this->bind)) {
            \register_shutdown_function(array($this, 'handleOutput'));
            \set_error_handler(array($this, 'handleError')); 
            \set_exception_handler(array($this, 'handleError')); 
        }  
        
        ob_start();
        @require($script); 
        $buffer = ob_get_contents();
        for($c = ob_get_level(); $c > 0; $c--) {
            ob_end_clean();
        }   
        
        return $this->buffer = $buffer;
    }
        
    public function handleOutput()
    { 
        if($this->error === false) {
            for($c = ob_get_level(); $c > 0; $c--) {
                ob_end_clean();
            }   
            \call_user_func($this->bind, $this->buffer);
        }        
    }
        
    public function handleError($error)
    { 
        $this->error = true;
        \restore_error_handler();
        \restore_exception_handler();
        for($c = ob_get_level(); $c > 0; $c--) {
            ob_end_clean();
        }   
        
        if(is_int($error)) {
            $error = error_get_last();
            if(is_null($error) || empty($error)) {
                $error = func_get_args();
            }
            $buffer = vsprintf('(%s) %s in %s on line %s', $error);
        } else
        if(is_object($error)) {
            $buffer = '<pre>' . $error->__toString() . '</pre>';
        }
        
        \call_user_func($this->bind, $buffer);
    }
        
    public function bind($class, $method)
    { 
        $this->bind = array($class, $method);
    }
        
    public function doDie()
    { 
        die();
    }
}
