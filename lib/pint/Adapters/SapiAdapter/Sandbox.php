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
            // \register_shutdown_function(array($this, 'handleOutput'));
            // \set_error_handler(array($this, 'handleError')); 
            // \set_exception_handler(array($this, 'handleError')); 
        }  
        
        $this->pid = pcntl_fork();
        
        if(is_int($this->pid)) {
            ob_implicit_flush(false);
            for($c = ob_get_level(); $c > 0; $c--) {
                ob_end_clean();
            }   
        
            ob_start();
            require($script); 
            $buffer = ob_get_contents();
            for($c = ob_get_level(); $c > 0; $c--) {
                ob_end_clean();
            }   
        
            return $buffer;
        } else {
            return 'can not fork!';
        }
    }
        
    public function handleOutput()
    { 
        for($c = ob_get_level(); $c > 0; $c--) {
            ob_end_clean();
        }   
        
        \call_user_func($this->bind, $this->buffer);
    }
        
    public function handleError($e)
    { 
        for($c = ob_get_level(); $c > 0; $c--) {
            ob_end_clean();
        }    
        
        if(!$error = error_get_last()) {
            $buffer = 'Unknown error.';
        } else {
            $buffer = \vsprintf('(%s) %s in %s on line %s', $error);
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
