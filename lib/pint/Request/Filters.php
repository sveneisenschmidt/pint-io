<?php

namespace pint\Request;

use \pint\Exception;


class Filters
{
    /**
     *
     * @param \pint\Request $request
     * @param array $input
     * @param array $config
     * @return void
     */
    static function parseHeaders(\pint\Request $request, $input, array $config = array())
    {
        $raw   = @\http_parse_headers($input[0]);
        
        if($raw === false ||
           !\array_key_exists('Request Method', $raw) ||
           !\array_key_exists('Request Url', $raw)    
        ) {
            throw new \pint\Exception('Could not parse headers!');
        }
        
        $request->offsetSet('REQUEST_METHOD', $raw['Request Method']);
        $request->offsetSet('REQUEST_URI',    $raw['Request Url']);
        
        unset($raw['Request Method'], $raw['Request Url']);
        
        foreach ($raw as $key => $value) {
            $key = \preg_replace("#[^a-z]+#i", "_", $key);
            $request->offsetSet('HTTP_' . \strtoupper($key), $value);
        }
    }
    
    /**
     *
     * @param \pint\Request $request
     * @param array $input
     * @param array $config
     * @return void
     */
    static function parseRequestLine(\pint\Request $request, $input, array $config = array())
    {
        $lines = \explode("\r\n", $input[0]);
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
     * @param array $input
     * @param array $config
     * @return void
     */
    static function validateContentType(\pint\Request $request, $input, array $config = array())
    {
        $method         = $request['REQUEST_METHOD'];
        $contenttypeset = isset($request['HTTP_CONTENT_TYPE']) && !empty($request['HTTP_CONTENT_TYPE']); 
        
        if($contenttypeset && is_array($request['HTTP_CONTENT_TYPE'])) {
            $request['HTTP_CONTENT_TYPE'] = $request['HTTP_CONTENT_TYPE'][0];
        }
        
        if($method == 'POST' || $method == 'PUT') {
            if(!$contenttypeset) {
                throw new \pint\Exception('POST or PUT request but not HTTP_CONTENT_TYPE header set');
            }
        } else {
            if($contenttypeset) {
                throw new \pint\Exception('No POST or PUT request but HTTP_CCONTENT_TYPE header set');
            }
        }
    }
    
    /**
     *
     * @param \pint\Request $request
     * @param array $input
     * @param array $config
     * @return void
     */
    static function createServerEnv(\pint\Request $request, $input, array $config = array())
    {
        list($host, $port) = explode(':', $config['listen']);
        
        $hostname   = gethostname();
        // list($addr) = gethostbynamel($hostname);
        
        $request->offsetSet('SERVER_SOFTWARE',  'pint/0.0.0');
        $request->offsetSet('SERVER_PROTOCOL',  'HTTP/1.1');
        $request->offsetSet('SERVER_NAME',      $hostname);
        $request->offsetSet('SERVER_ADDR',      $host);
        $request->offsetSet('SERVER_PORT',      $port);
    }
    
    /**
     *
     * @param \pint\Request $request
     * @param array $input
     * @param array $config
     * @return void
     */
    static function createPathInfoEnv(\pint\Request $request, $input, array $config = array())
    {
        $parts = \explode('?', $request['REQUEST_URI']);
        
        $request['PATH_INFO']    = isset($parts[0]) ? $parts[0]    : $request['REQUEST_URI'];
        if(isset($parts[1])) {
            $request['QUERY_STRING'] = $parts[1];
        }
    }
}
