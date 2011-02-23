<?php

use pint\Adapters\SapiAdapter\Functions\AlreadyExistsException;

if(!function_exists('sapi_header')) {
    /**
     *
     * @param string $header
     * @return void
     */
    function sapi_header($header)
    {
        $GLOBALS['_SANDBOX']->pushResponseHeader($header);
    }
} else {
    throw new AlreadyExistsException('Function does already exist!');
}
    
if(!function_exists('sapi_headers_list')) {
    /**
     *
     * @return array
     */
    function sapi_headers_list()
    {
        $GLOBALS['_SANDBOX']->responseHeaders();    
    }
} else {
    throw new AlreadyExistsException('Function does already exist!');
}
    
if(!function_exists('sapi_headers_sent')) {
    /**
     *
     * @return boolean
     */
    function sapi_headers_sent()
    {
        // return false;        
    }
} else {
    throw new AlreadyExistsException('Function does already exist!');
}

return array(
    'sapi_header'         => 'header',
    'sapi_headers_list'   => 'headers_list',
    'sapi_headers_sent'   => 'headers_sent'
);

