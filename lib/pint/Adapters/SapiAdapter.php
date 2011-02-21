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

        list($code, $headers) = $this->finalResponseHeaders();
        $this->cleanup();
        
        return array( $code, $headers, $buffer);
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
        $buffer = "";
        ob_start();
        try {
            @require($script); 
        } catch (\Exception $exception) {
            print \pint\Adapters\SapiAdapter::formatException($exception);
        }
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
        // $this->unsetOverloads();
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
    final protected function unsetGlobals()
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
                if(is_array($error = error_get_last())) {
                    print \pint\Adapters\SapiAdapter::formatError($error);
                }
                
                $buffer = ob_get_contents();  
                ob_end_clean();
                
                if(is_null($sapi->boundResponse)) {
                    $msg = 'Missing bound Response!';
                    print 'Error: ' . $msg;
                    throw new Exception($msg);    
                }
                
                list($code, $headers) = $sapi->finalResponseHeaders();
                $sapi->boundResponse->flush(array($code,$headers,$buffer));
                unset($buffer, $code, $headers);
                
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
    
    /**
     * @param array $error
     * @return string
     */
    public static function formatError(array $error)
    {
        switch((int) $error['type']) {
            case 4:
                return "Parse error: {$error['message']} in file {$error['file']} on line {$error['line']}";
            break;
                        
            default:
                return 'pint.IO could not detect error type';
        } 
    }
    
    /**
     * @param Exception $exception
     * @return string
     */
    public static function formatException(\Exception $exception)
    {
        // lars could append here his nifty exception formatter
        return '<pre>' . $exception->__toString() . '</pre>';   
    }
    
}
