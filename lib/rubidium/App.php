<?php

namespace rubidium;

class App
{
    protected $env, $request, $response;

    function __construct(array $env)
    {
        $this->env = $env;
        $this->request = new Request(&$this->env);
        $this->response = new Response();
    }

    function env()
    {
        return $this->env;
    }

    function request()
    {
        return $this->request();
    }

    function response()
    {
        return $this->response();
    }

    function process()
    {
        $this->response->write("Hi, I'm rubidium!");
    }

    static function call(array $env)
    {
        $app = new static($env);
        $app->process();
        return $app->response()->finish();
    }
}
