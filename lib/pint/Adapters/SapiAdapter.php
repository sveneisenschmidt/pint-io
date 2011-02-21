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
        return array(
            200,
            array("Content-Type" => "text/html"),
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
        require($script);
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
    }
    
    /**
     *
     * @param \pint\Request $env
     * @return void
     */
    final protected function setGlobals(Request $env)
    {
        $GLOBALS = array(); // donno?!?!?!
        
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
        foreach (array("GET", "POST", "COOKIE", "FILES", "SERVER", "REQUEST") as $key) {
            if (isset($GLOBALS["_$key"])) {
                unset($GLOBALS["_$key"]);
            }
        }
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
                    $sapi->boundResponse->flush(array(
                        200,
                        array("Content-Type" => "text/html"),
                        $buffer
                    ));
                    unset($buffer);
                }
                $sapi->cleanup();
            }   
        });
    }
}
