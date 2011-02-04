<?php

namespace rubidium;

class DrupalAdapter extends App
{
    function process()
    {
        $this->response->write("Hi, I'm Drupal!");
    }
}
