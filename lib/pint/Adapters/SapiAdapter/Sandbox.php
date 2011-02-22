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
    protected $buffer = "";
        
    /**
     *
     * @var string
     */
    protected $output = "";
        
    /**
     *
     * @param array $globals
     */
    public function __construct(array $globals = array())
    { 
        if(!empty($globals)) {
            $this->globals = $globals;
        }        
    }
        
    /**
     *
     * @param string $script
     * @return string
     */
    public function run($script)
    { 
        if(is_array($this->bind) || function_exist($this->bind)) {
            \register_shutdown_function(array($this, 'handleOutput'));
            \set_error_handler(array($this, 'handleError')); 
            \set_exception_handler(array($this, 'handleError')); 
        }  
        
        $GLOBALS["_SERVER"]  = $this->globals['_SERVER'];
        $GLOBALS["_GET"]     = $this->globals['_GET'];
        $GLOBALS["_POST"]    = $this->globals['_POST'];
        $GLOBALS["_COOKIE"]  = array();
        $GLOBALS["_FILES"]   = $this->globals['_FILES'];
        $GLOBALS["_REQUEST"] = \array_merge($GLOBALS["_GET"], $GLOBALS["_POST"]) ;
        
        list($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES, $_REQUEST) = array(
            $GLOBALS['_SERVER'], $GLOBALS['_GET'], 
            $GLOBALS['_POST']  , $GLOBALS['_COOKIE'], 
            $GLOBALS['_FILES'] , $GLOBALS['_REQUEST'] 
        );
        
        ob_start();
        @require($script); 
        $buffer .= ob_get_contents();
        for($c = ob_get_level(); $c > 0; $c--) {
            ob_end_clean();
        }   
        
        return $this->buffer = $buffer;
    }
        
    /**
     *
     * @return void
     */
    public function handleOutput()
    { 
        if($this->error === false) {
            for($c = ob_get_level(); $c > 0; $c--) {
                ob_end_clean();
            }   
            \call_user_func($this->bind, $this->buffer);
        }        
    }
        
    /**
     *
     * @param int|Exception $error
     * @return void
     */
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
        
    /**
     *
     * @param object $class
     * @param string $method
     * @return void
     */
    public function bind($class, $method)
    { 
        $this->bind = array($class, $method);
    }
}
