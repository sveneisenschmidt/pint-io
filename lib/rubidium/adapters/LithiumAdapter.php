<?php

namespace rubidium;

class LithiumAdapter extends App
{
    function process()
    {
        $this->response->write("Hi, I'm a Lithium app!");
    }
}
