<?php

namespace pint\Adapters;

use \pint\App\AppAbstract,
    \pint\Adapters\SapiAdapter\Sandbox,
    \pint\Request,
    \pint\Response,
    \pint\Response\BoundResponse;
/**
 *
 * Dying is our latest fashion
 */
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
        if(!file_exists($script)) {
            return Response::internalServerError("File: {$script} does not exist!");
        }
        
        $sandbox = new Sandbox($this->globals);
        $sandbox->bind($this, 'output');
        
        $content = $sandbox->run($script); 
        list($code, $headers) = $sandbox->finalResponseHeaders();
        
        print_r($code);
        print_r($headers);
        
        
        return array(
            200,
            array('Content-Type' => 'text/html'),
            $buffer
        );
    }
    
    /**
     *
     * @param array $env
     * @param \pint\Socket\ChildSocket $socket
     * @return void
     */
    final public function process($env, \pint\Socket\ChildSocket $socket)
    {
        $this->globals       = $this->getGlobals($env);
        $this->boundResponse = BoundResponse::bind($socket);
    }

    
    /**
     *
     * @return void
     */
    final public function output($headers, $string) 
    {
        $this->boundResponse->flush(array(
            200,
            array('Content-Type' => 'text/html'),
            $string
        ));
    }

    
    /**
     *
     * @return void
     */
    final public function cleanup() {}
    
    /**
     *
     * @param \pint\Request $env
     * @return void
     */
    final protected function getGlobals(Request $env)
    {
        return array(
            "_SERVER"  => $env->server(),
            "_GET"     => $env->paramsGet(),
            "_POST"    => $env->paramsPost(),
            "_COOKIE"  => array(),
            "_FILES"   => $env->files(),
            "_REQUEST" => \array_merge($env->paramsGet(), $env->paramsPost()),
        );
    }
    
}
