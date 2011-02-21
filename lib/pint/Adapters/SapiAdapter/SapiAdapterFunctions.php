<?php

if(!function_exists('sapi_header')) {
    /**
     *
     * @param string $header
     * @return void
     */
    function sapi_header($header)
    {
        $GLOBALS['PINT_SAPI']->pushResponseHeader($header);
    }
}
    
if(!function_exists('sapi_headers_list')) {
    /**
     *
     * @return array
     */
    function sapi_headers_list()
    {
        return $GLOBALS['PINT_SAPI']->responseHeaders();        
    }
}
    
if(!function_exists('sapi_headers_sent')) {
    /**
     *
     * @return boolean
     */
    function sapi_headers_sent()
    {
        return false;        
    }
}

return array(
    'sapi_header'         => 'header',
    'sapi_headers_list'   => 'headers_list',
    'sapi_headers_sent'   => 'headers_sent'
);

