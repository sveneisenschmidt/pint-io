<?php

namespace pint\Adapters\SapiAdapter;

use \pint\Exception,
    \pint\Adapters\SapiAdapter\Functions\AlreadyExistsException;

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
    public $responseHeaders = array(
        "Response Code"   => 200,
        "Response Status" => 'OK',
        "Content-Type"    => 'text/html' 
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
     * @var array
     */
    protected $functions = array();
        
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
        if(!is_array($this->bind)) {
            throw new Exception('Missing required bind!');  
        } 
        
        $this->registerSapiFunctions();
        \register_shutdown_function(array($this, 'handleOutput'));
        \set_error_handler(array($this, 'handleError')); 
        \set_exception_handler(array($this, 'handleError')); 
        
        $GLOBALS["_SERVER"]  = $this->globals['_SERVER'];
        $GLOBALS["_GET"]     = $this->globals['_GET'];
        $GLOBALS["_POST"]    = $this->globals['_POST'];
        $GLOBALS["_COOKIE"]  = array();
        $GLOBALS["_FILES"]   = $this->globals['_FILES'];
        $GLOBALS["_REQUEST"] = \array_merge($GLOBALS["_GET"], $GLOBALS["_POST"]) ;
        $GLOBALS['_SANDBOX'] = $this;
        
        list($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES, $_REQUEST) = array(
            $GLOBALS['_SERVER'], $GLOBALS['_GET'], 
            $GLOBALS['_POST']  , $GLOBALS['_COOKIE'], 
            $GLOBALS['_FILES'] , $GLOBALS['_REQUEST']
        );
        
        ob_start();
        @require($script); 
        $buffer = ob_get_contents();
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
        $buffer = ob_get_contents();
        for($c = ob_get_level(); $c > 0; $c--) {
            ob_end_clean();
        }  
            
        \call_user_func_array($this->bind, array($this->finalResponseHeaders(), $buffer));
        $this->cleanup();
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
        } else {
            $buffer = 'unkown error';
        }
        
        \call_user_func_array($this->bind, array(array(), $buffer));
        $this->cleanup();
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

    /**
     *
     * @return array
     */
    public function finalResponseHeaders()
    {
        $headers = $this->responseHeaders();        
        $code = $headers['Response Code'];
        unset($headers['Response Code'], $headers['Response Status']);
        
        return array($code, $headers);
    }

    /**
     *
     * @return void
     */
    final public function pushResponseHeader($headerString)
    {
        $headers = @\http_parse_headers($headerString);
        if($headers === false || is_null($headers)) {
            $headers = array();
        }
        
        $this->responseHeaders = array_merge(
            $this->responseHeaders, $headers    
        );
    }

    /**
     *
     * @return void
     */
    final public function responseHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     *
     * @return void
     */
    final public function cleanup()
    {
        $this->unregisterSapiFunctions();
    }
    
    // SAPI stuff

    /**
     *
     * @return void
     */
    final public function registerSapiFunctions()
    {
        $this->functions = require(__DIR__ . '/Functions.php');
        
        // registerFunctions; 
        try {
            $this->functions = require(__DIR__ . '/Functions.php');   
        } catch(AlreadyExistsException $exception) {
            $this->unregisterSapiFunctions();
        }
        
        foreach($this->functions as $sapiFunc => $nativeFunc) {
            if(function_exists($nativeFunc)) {
                \runkit_function_remove($nativeFunc);
            }
            
            if(!function_exists('native_' . $nativeFunc) && function_exists($nativeFunc)) {
                // das backup der nativen function exisitert noch nicht und wird nun hier erstellt
                // zusätzlich löschen wir die original function
                \runkit_function_rename($nativeFunc, 'native_' . $nativeFunc);
            }  
            \runkit_function_rename($sapiFunc, $nativeFunc);
        }
        
    }

    /**
     *
     * @return void
     */
    final public function unregisterSapiFunctions()
    {
        
    }
    
    
    
    
    
    
}
