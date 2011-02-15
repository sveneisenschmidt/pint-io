<?php

namespace pint;

class LithiumAdapter extends App
{
    function process()
    {
        $this->response->write("Hi, I'm a Lithium app!");
    }
}
