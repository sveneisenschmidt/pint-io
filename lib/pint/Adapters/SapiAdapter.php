<?php

namespace pint\Adapters;

use \pint\App;

class SapiAdapter extends App
{
    function process()
    {
        $this->setGlobals();
    }

    function setGlobals()
    {
        $GLOBALS["_GET"] = $this->request->params();
        $GLOBALS["_POST"] = $this->request->postParams();
        $GLOBALS["_COOKIE"] = $this->request->cookies();
        $GLOBALS["_FILES"] = array();
        foreach ($this->request->files() as $file) {
            $file->write();
            $GLOBALS["_files"] []= array(
                "name" => $file->name(),
                "type" => $file->type(),
                "size" => $file->size(),
                "tmp_name" => $file->tmpName(),
                "error" => UPLOAD_ERR_OK
            );
        }
        $REQUEST["_SERVER"] = $this->request->toArray();
        
        // @todo respect variables-order
        $REQUEST["_REQUEST"] = array_merge($GLOBALS["_COOKIE"], $GLOBALS["_POST"], $GLOBALS["_GET"]);
    }

    function unsetGlobals()
    {
        foreach (array("GET", "POST", "COOKIE", "FILES", "SERVER", "REQUEST") as $key) {
            if (isset($GLOBALS["_$key"])) {
                unset($GLOBALS["_$key"]);
            }
        }
    }
}
