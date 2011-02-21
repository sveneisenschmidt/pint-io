<?php

namespace pint\Adapters;

use \pint\App\AppAbstract,
    \pint\Request,
    \pint\Response,
    \pint\Response\BoundResponse;

abstract class SapiAdapter extends AppAbstract
{
    
    /**
     *
     * @var boolean
     */
    public $buffering = false;
    
    /**
     *
     * @var \pint\Response\BoundResponse
     */
    public $boundResponse = null;
    
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
     * @var array
     */
    public $overloads = array();
    
    /**
     *
     * @return string
     */
    abstract public function getScriptPath();
    
    /**
     *
     * @param array $env
     * @return void
     */
    final public function call($env)
    {
        $script = $this->getScriptPath();
        if(is_null($script) || !file_exists($script)) {
            return array(
                500,
                array("Content-Type" => "text/html"),
                "Given in script via ::getScriptPath does not exist: '{$script}'"
            );
        }   
        
        // Missing, error handling, cookies, session, etc
        $buffer = $this->buffer($script);
        
        $this->cleanup();
        
        
        list($code, $headers) = $this->finalResponseHeaders();
        
        return array(
            $code,
            $headers,
            $buffer
        );
    }
    
    /**
     *
     * @param string $script
     * @return string
     */
    final public function buffer($script)
    {
        list($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES, $_REQUEST) = array(
            $GLOBALS['_SERVER'], $GLOBALS['_GET'], 
            $GLOBALS['_POST']  , $GLOBALS['_COOKIE'], 
            $GLOBALS['_FILES'] , $GLOBALS['_REQUEST'] 
        );
            
        $this->buffering = true;    
        ob_start();
        @include($script);
        $buffer = ob_get_contents();
        ob_end_clean();
        $this->buffering = false;    
        return $buffer;
    }
    
    /**
     *
     * @param array $env
     * @param \pint\Socket\ChildSocket $socket
     * @return void
     */
    final public function process($env, \pint\Socket\ChildSocket $socket)
    {
        $this->setGlobals($env);
        $this->overloadFunctions();
        $this->bindResponse($socket);
        $this->registerShutdown();
    }
    
    /**
     *
     * @return void
     */
    final public function cleanup()
    {
        $this->unsetGlobals();
        $this->unsetOverloads();
    }
    
    /**
     *
     * @param \pint\Request $env
     * @return void
     */
    final protected function setGlobals(Request $env)
    {
        $GLOBALS = array('PINT_SAPI' => $this); // donno?!?!?!
        
        $GLOBALS["_SERVER"]  = $env->server();
        $GLOBALS["_GET"]     = $env->paramsGet();
        $GLOBALS["_POST"]    = $env->paramsPost();
        $GLOBALS["_COOKIE"]  = array();
        $GLOBALS["_FILES"]   = $env->files();
        $GLOBALS["_REQUEST"] = \array_merge($GLOBALS["_GET"], $GLOBALS["_POST"]) ;
    }

    /**
     *
     * @return void
     */
    final protected function unsetOverloads()
    {
        foreach (array("_GET", "_POST", "_COOKIE", "_FILES", "_SERVER", "_REQUEST", 'PINT_SAPI') as $key) {
            if (isset($GLOBALS["$key"])) {
                unset($GLOBALS["$key"]);
            }
        }
    }
  
            
    
    /**
     *
     * @return void
     */
    final protected function overloadFunctions()
    {
        $sapi = $this;
        $toOverload = @include(__DIR__ .'/SapiAdapter/SapiAdapterFunctions.php');
        
        foreach($toOverload as $new => $old) {
            @\runkit_function_rename($old, 'php_' . $old);  
            @\runkit_function_rename($new, $old);  
        }
        
        $this->overloads = $toOverload;
    }

    /**
     *
     * @return void
     */
    final protected function unsetGlobals()
    {
        foreach($this->overloads as $new => $old) {
            @\runkit_function_remove($new);
            @\runkit_function_rename('php_' . $old, $old);
        }
    }

    /**
     *
     * @return void
     */
    final public function pushResponseHeader($headerString)
    {
        $headers = @\http_parse_headers($headerString) ?: array();
        
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
     * @param \pint\Socket\ChildSocket $socket
     * @return void
     */
    final protected function bindResponse(\pint\Socket\ChildSocket $socket) 
    {
        $this->boundResponse = BoundResponse::bind($socket);         
    }

    /**
     *
     * @return void
     */
    final protected function registerShutdown()
    {
        $sapi = $this;
        
        \register_shutdown_function(function() use($sapi) {
            if($sapi->buffering === true) {
                $buffer = ob_get_contents();
                ob_end_clean();
                
                if(!is_null($sapi->boundResponse)) {
                    list($code, $headers) = $sapi->finalResponseHeaders();
                    $sapi->boundResponse->flush(array(
                        $code,
                        $headers,
                        $buffer
                    ));
                    unset($buffer, $code, $headers);
                }
                $sapi->cleanup();
            } 
        });
    }

    /**
     *
     * @return array
     */
    final public function finalResponseHeaders()
    {
        $headers = $this->responseHeaders();        
        $code = $headers['Response Code'];
        unset($headers['Response Code'], $headers['Response Status']);
        
        return array($code, $headers);
    }
}
