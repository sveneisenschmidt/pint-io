<?php

namespace tests\lib\pint;

use \pint\Request,
    \pint\Exception;


class Request_CreateTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * Setup
     * 
     */
    public function setUp() 
    { 
        $this->input_headers = array(
            'request'       => 'GET / HTTP/1.1',
            'host'          => 'Host: testhost:3000',
            'agent'         => 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:2.0b11) Gecko/20100101 Firefox/4.0b11',
            'accept'        => 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,* / *;q=0.8',
            'accept-lang'   => 'Accept-Language: de-de,de;q=0.8,en-us;q=0.5,en;q=0.3',
            'accept-enc'    => 'Accept-Encoding: gzip, deflate',
            'accept-char'   => 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'keep-alive'    => 'Keep-Alive: 115',
            'connection'    => 'Connection: keep-alive',
            'cache'         => 'Cache-Control: max-age=0'
        );
        
        $this->input_body = array(
            "\r\n\r\n"
        );
    }
    
    
    /**
     * Teardown
     * 
     */
    public function tearDown() {}
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function NewInstanceOnCreate()
    {
        $input  = \implode("\r\n", $this->input_headers);
        $input .= \implode('', $this->input_body);
        $request = \pint\Request::parse($input, array());
        
        $this->assertInstanceOf('\pint\Request', $request);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     * @expectedException \pint\Exception
     */
    public function ThrowsExceptionOnEmptyInput()
    {
       \pint\Request::parse('');
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function HeadersSuccesfullyParsed()
    {
        $input  = \implode("\r\n", $this->input_headers);
        $input .= \implode('', $this->input_body);
        
        $request = \pint\Request::parse($input);
        $this->assertNotEmpty($request['headers']);
        $this->assertNotEmpty($request['method']);
        $this->assertNotEmpty($request['uri']);
        $this->assertNotEmpty($request['version']);
        
        $headers = $request->headers();
        $keys    = array(
            'Host', 'User-Agent', 'Accept', 'Accept-Language', 'Accept-Encoding', 
            'Accept-Charset', 'Keep-Alive', 'Connection', 'Cache-Control'
        );
        
        foreach($keys as $key) {
            $this->assertArrayHasKey($key, $headers);
        }
        
        return $request;
    }
    
    
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function InvalidHeaders_MissingMethodUri()
    {
        $incompleteHeaders = $this->input_headers;
        unset($incompleteHeaders['request']);
        
        $input  = \implode("\r\n", $incompleteHeaders);
        $input .= \implode('', $this->input_body);
        
        $request = \pint\Request::parse($input);
        
        $this->assertTrue($request->haserror());
        $this->assertEquals($request['headers'], array());
        $this->assertEquals($request['method'],  null);
        $this->assertEquals($request['uri'],     null);
        $this->assertEquals($request['version'], null);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function InvalidHeaders_InvalidHeadRequestFromRequestLine()
    {
        $defectHeaders = $this->input_headers;
        $defectHeaders['request'] = 'GET / HTTP/1.1 asdas asd asd';
        
        $input  = \implode("\r\n", $defectHeaders);
        $input .= \implode('', $this->input_body);

        $request = \pint\Request::parse($input);
        
        $this->assertTrue($request->haserror());
        $this->assertEquals($request['headers'], array());
        $this->assertEquals($request['method'],  null);
        $this->assertEquals($request['uri'],     null);
        $this->assertEquals($request['version'], null);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     * @expectedException Exception
     */
    public function StaticMethod_ParseHeadersFailure()
    {
        \pint\Request::parseHeaders(new \pint\Request(), '');
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function StaticMethod_ParseHeadersSuccess()
    {
        $input  = \implode("\r\n", $this->input_headers);
        $input .= \implode('', $this->input_body);
        
        \pint\Request::parseHeaders(new \pint\Request(), $input);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function StaticMethod_ParseRequestLineSuccess()
    {
        $input  = \implode("\r\n", $this->input_headers);
        $input .= \implode('', $this->input_body);
        
        \pint\Request::parseRequestLine(new \pint\Request(), $input);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     * @expectedException Exception
     */
    public function StaticMethod_ParseRequestLineFailure()
    {
        $defectHeaders = $this->input_headers;
        $defectHeaders['request'] = 'GREET / PHHT/99.x12';
        
        $input  = \implode("\r\n", $defectHeaders);
        $input .= \implode('', $this->input_body);
        
        \pint\Request::parseRequestLine(new \pint\Request(), $input);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     * @depends HeadersSuccesfullyParsed
     */
    public function StaticMethod_ValidateContentTypeSuccess(\pint\Request $request)
    {   
        \pint\Request::validateContentType($request);
        
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     * @depends HeadersSuccesfullyParsed
     * @expectedException Exception
     */
    public function StaticMethod_ValidateContentTypeFailureInvalidMethod(\pint\Request $request)
    {   
        $headers = $request->headers();
        $request['method'] = 'DELETE';
        $headers['Content-Type']  = 'application/json';
        $request['headers'] = $headers;
        
        \pint\Request::validateContentType($request);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     * @depends HeadersSuccesfullyParsed
     * @expectedException Exception
     */
    public function StaticMethod_ValidateContentTypeFailureInvalidContentType(\pint\Request $request)
    {   
        $headers = $request->headers();
        $request['method'] = 'POST';
        unset($headers['Content-Type']);
        $request['headers'] = $headers;
        
         \pint\Request::validateContentType($request);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     * @depends HeadersSuccesfullyParsed
     */
    public function HeaderGetsFiltered(\pint\Request $request)
    {  
        $headers = $request->headers();
        
        $this->assertFalse(\array_key_exists('Request Method', $headers));
        $this->assertFalse(\array_key_exists('Request Url', $headers));
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function FilterSupport_DefaultFilters()
    {
        $request = $this->getMockClass('\pint\Request', array('callFilter'), array(), 'RequestMock', false);
        $request::staticExpects($this->exactly(3))
                ->method('callFilter')
                ->will($this->returnValue(null));        
        
        $input  = \implode("\r\n", $this->input_headers);
        $input .= \implode('', $this->input_body);
        
        $instance = $request::parse($input);
        $this->assertInstanceOf('\pint\Request', $instance);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function FilterSupport_CustomFilter()
    {
        $request = $this->getMockClass('\pint\Request', array('callFilter'), array(), 'RequestMock', false);
        $request::staticExpects($this->exactly(1))
                ->method('callFilter')
                ->will($this->returnValue('custom'));     
        
        $instance = $request::parse('some input text', array(
            function() { return 'custom'; }
        ));
        $this->assertInstanceOf('\pint\Request', $instance);
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function FilterSupport_CustomFilterThrowsException()
    {
        $message = 'something went wrong!';
        $request = $this->getMockClass('\pint\Request', array('callFilter'), array(), 'RequestMock', false);
        $request::staticExpects($this->exactly(1))
                ->method('callFilter')
                ->will($this->throwException(new \pint\Exception($message)));     
        
        $instance = $request::parse('some input text', array(
            function() {}
        ));
        
        $this->assertInstanceOf('\pint\Request', $instance);
        $this->assertTrue($instance->haserror());
        $this->assertEquals($message, $instance->errormsg());
    }
    
    /**
     * 
     * @test
     * @runInSeparateProcess
     */
    public function FilterSupport_FilterNameSplit()
    {
        $request = $this->getMockClass('\pint\Request', array('callFilter'), array(), 'RequestMock', false);
        $request::staticExpects($this->exactly(1))
                ->method('callFilter')
                ->will($this->returnValue('custom'));     
        
        $instance = $request::parse('some input text', array(
            '\tests\lib\pint\Request_CreateTest::Helper_FilterSupport_FilterNameSplit'
        ));
        
        $this->assertInstanceOf('\pint\Request', $instance);
        $this->assertFalse($instance->haserror());
    }
    
    /**
     * 
     * @return string
     */
    public static function Helper_FilterSupport_FilterNameSplit()
    {
        return 'helper_filtername_split';
    }
    
    
    
}
