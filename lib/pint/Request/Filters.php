<?php

namespace pint\Request;

use \pint\Exception;


class Filters
{
    /**
     *
     * @param \pint\Request $request
     * @param string $input
     * @return void
     */
    static function parseHeaders(\pint\Request $request, $input)
    {
        $raw   = @\http_parse_headers($input);
        
        if($raw === false ||
           !\array_key_exists('Request Method', $raw) ||
           !\array_key_exists('Request Url', $raw)    
        ) {
            throw new \pint\Exception('Could not parse headers!');
        }
        
        $request->offsetSet('REQUEST_METHOD', $raw['Request Method']);
        $request->offsetSet('REQUEST_URI',    $raw['Request Url']);
        
        unset($raw['Request Method'], $raw['Request Url']);
        
        foreach ($raw as $key => $value)
        {
            $key = \preg_replace("#[^a-z]+#i", "_", $key);
            $request->offsetSet('HTTP_' . \strtoupper($key), $key);
        }
    }
    
    /**
     *
     * @param \pint\Request $request
     * @param string $input
     * @return void
     */
    static function parseRequestLine(\pint\Request $request, $input)
    {
        $lines = \explode("\r\n", $input);
        \preg_match("#^(?P<method>GET|HEAD|POST|PUT|OPTIONS|DELETE)\s+(?P<uri>[^\s]+)\s+HTTP/(?P<version>1\.\d)$#U", trim($lines[0]), $matches);
        if(!\array_key_exists('version', $matches)) {
            throw new \pint\Exception('Could not parse rrequest line!');
        }
        
        $request->offsetSet('HTTP_VERSION', 'HTTP/' . trim($matches['version']));
        unset($matches);
    }
    
    /**
     *
     * @param \pint\Request $request
     * @return void
     */
    static function validateContentType(\pint\Request $request)
    {
        if(\array_key_exists('Content-Type', $request) && !\in_array($request['REQUEST_METHOD'], array('POST', 'PUT'))) {
            throw new \pint\Exception('Conten-Type header is set but wrong HTTP method, epxected POST or PUT');
        }
        
        if(\in_array($request['REQUEST_METHOD'], array('POST', 'PUT')) && !\array_key_exists('Content-Type', $request)) {
            throw new \pint\Exception('Conten-Type header is not set but is needed by POST or PUT requests.');
        }
    }
    
    /**
     *
     * @param \pint\Request $request
     * @return void
     */
    static function createServerEnv(\pint\Request $request)
    {
        $request->offsetSet('SERVER_SOFTWARE',  'pint/0.0.0');
        $request->offsetSet('SERVER_PROTOCOL',  'HTTP/1.1');
        $request->offsetSet('SERVER_NAME',      'pint.io');
        $request->offsetSet('SERVER_PORT',      3000);
    }
    
    
    
    
    
    
}
