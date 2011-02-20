<?php

namespace example;

class ScriptApp extends \pint\Adapters\SapiAdapter
{
    function getScriptPath()
    {
        return __DIR__ . '/script.php';
    }
}
