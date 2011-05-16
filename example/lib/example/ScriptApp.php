<?php

namespace example;

class ScriptApp extends \pint\Adapters\SapiAdapter
{
    function getScriptPath()
    {
        return __DIR__ . '/ScriptApp/script.php';
        // return '/home/sven/Arbeitsplatz/Projekte/geeklove/web/app.php';
    }
}
