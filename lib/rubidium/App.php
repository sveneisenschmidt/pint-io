<?php

namespace rubidium;

class App
{
    protected $env, $request, $response;

    function call(array $env)
    {
        $this->env = $env;
        $this->request = new Request($this->env);
        $this->response = new Response();

        $this->process();

        return $this->response->finish();
    }

    function env()
    {
        return $this->env;
    }

    function request()
    {
        return $this->request;
    }

    function response()
    {
        return $this->response;
    }

    function process()
    {
        $this->response->write("Hi, I'm rubidium!");
    }
}
