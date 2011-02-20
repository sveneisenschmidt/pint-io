<?php

namespace pint\Adapters;

use \pint\App\AppAbstract,
    \pint\Request,
    \pint\Response;

abstract class SapiAdapter extends AppAbstract
{
    
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
            
        ob_start();
        require($script);
        $buffer = ob_get_contents();
        ob_end_clean();
        return $buffer;
    }
    
    /**
     *
     * @param array $env
     * @return void
     */
    final public function process($env)
    {
        $this->setGlobals($env);
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
    function setGlobals(Request $env)
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
    function unsetGlobals()
    {
        foreach (array("GET", "POST", "COOKIE", "FILES", "SERVER", "REQUEST") as $key) {
            if (isset($GLOBALS["_$key"])) {
                unset($GLOBALS["_$key"]);
            }
        }
    }
}
