<?php

namespace rubidium;

class ZendAdapter extends App
{
    function process()
    {
        $this->response->write("Hi, I'm a Zend Framework app!");
    }
}
