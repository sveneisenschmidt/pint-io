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
        
        $request->offsetSet('method',  $raw['Request Method']);
        $request->offsetSet('uri',     $raw['Request Url']);
        
        unset($raw['Request Method'], $raw['Request Url']);
        $request->offsetSet('headers', $raw);
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
        
        $request->offsetSet('version', $matches['version']);
        unset($matches);
    }
    
    /**
     *
     * @param \pint\Request $request
     * @return void
     */
    static function validateContentType(\pint\Request $request)
    {
        $headers = $request->headers();
        $method  = $request->method();
        
        if(\array_key_exists('Content-Type', $headers) && !\in_array($method, array('POST', 'PUT'))) {
            throw new \pint\Exception('Conten-Type header is set but wrong HTTP method, epxected POST or PUT');
        }
        
        if(\in_array($method, array('POST', 'PUT')) && !\array_key_exists('Content-Type', $headers)) {
            throw new \pint\Exception('Conten-Type header is not set but is needed by POST or PUT requests.');
        }
    }
}
