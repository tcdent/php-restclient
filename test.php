<?php

// Test are comprised of two components: a simple json response for testing
// interaction via the built-in PHP server, and PHPUnit test methods. 

// Test Server
// This code is only executed by the test server instance. It returns simple 
// JSON debug information for validating behavior. 
if(php_sapi_name() == 'cli-server'){
    header("Content-Type: application/json");
    die(json_encode(array(
        'SERVER' => $_SERVER, 
        'REQUEST' => $_REQUEST, 
        'POST' => $_POST, 
        'GET' => $_GET, 
        'body' => file_get_contents('php://input'), 
        'headers' => getallheaders()
    )));
}


// Unit Tests
// 

require 'restclient.php';

// This varible can be overridden with a PHPUnit XML configuration file.
if(!isset($TEST_SERVER_URL))
    $TEST_SERVER_URL = "http://localhost:8888"; 

class RestClientTest extends PHPUnit_Framework_TestCase {
    
    public function test_get(){
        global $TEST_SERVER_URL;
        
        $api = new RestClient;
        $result = $api->get($TEST_SERVER_URL, [
            'foo' => ' bar', 'baz' => 1, 'bat' => ['foo', 'bar']
        ]);
        
        $response_json = $result->decode_response();
        $this->assertEquals('GET', 
            $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1&bat%5B%5D=foo&bat%5B%5D=bar", 
            $response_json->SERVER->QUERY_STRING);
        $this->assertEquals("", 
            $response_json->body);
    }
    
    public function test_post(){
        global $TEST_SERVER_URL;
        
        $api = new RestClient;
        $result = $api->post($TEST_SERVER_URL, [
            'foo' => ' bar', 'baz' => 1, 'bat' => ['foo', 'bar']
        ]);
        
        $response_json = $result->decode_response();
        $this->assertEquals('POST', 
            $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1&bat%5B%5D=foo&bat%5B%5D=bar", 
            $response_json->body);
    }
    
    public function test_put(){
        global $TEST_SERVER_URL;
        
        $api = new RestClient;
        $result = $api->put($TEST_SERVER_URL, array(
            'foo' => ' bar', 'baz' => 1));
        
        $response_json = $result->decode_response();
        $this->assertEquals('PUT', 
            $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1", 
            $response_json->body);
    }
    
    public function test_delete(){
        global $TEST_SERVER_URL;
        
        $api = new RestClient;
        $result = $api->delete($TEST_SERVER_URL, array(
            'foo' => ' bar', 'baz' => 1));
        
        $response_json = $result->decode_response();
        $this->assertEquals('DELETE', 
            $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1", 
            $response_json->body);
    }
    
    public function test_user_agent(){
        global $TEST_SERVER_URL;
        
        $api = new RestClient(array(
            'user_agent' => "RestClient Unit Test"
        ));
        $result = $api->get($TEST_SERVER_URL);
        
        $response_json = $result->decode_response();
        $this->assertEquals("RestClient Unit Test", 
            $response_json->headers->{"User-Agent"});
    }
    
    public function test_json_patch(){
        global $TEST_SERVER_URL;
        
        $api = new RestClient;
        $result = $api->execute($TEST_SERVER_URL, 'PATCH',
            "{\"foo\":\"bar\"}",
            array(
                'X-HTTP-Method-Override' => 'PATCH', 
                'Content-Type' => 'application/json-patch+json'));
        $response_json = $result->decode_response();
        
        $this->assertEquals('application/json-patch+json', 
            $response_json->headers->{"Content-Type"});
        $this->assertEquals('PATCH', 
            $response_json->headers->{"X-HTTP-Method-Override"});
        $this->assertEquals('PATCH', 
            $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("{\"foo\":\"bar\"}", 
            $response_json->body);
    }
    
    public function test_json_post(){
        global $TEST_SERVER_URL;
        
        $api = new RestClient;
        $result = $api->post($TEST_SERVER_URL, "{\"foo\":\"bar\"}",
            array('Content-Type' => 'application/json'));
        $response_json = $result->decode_response();
        
        $this->assertEquals('application/json', 
            $response_json->headers->{"Content-Type"});
        $this->assertEquals('POST', 
            $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("{\"foo\":\"bar\"}", 
            $response_json->body);
    }
    
    public function test_multiheader_response(){
        $RESPONSE = "HTTP/1.1 200 OK\r\nContent-type: text/json\r\nContent-Type: application/json\r\n\r\nbody";
        
        $api = new RestClient;
        // bypass request execution to inject controlled response data.
        $api->parse_response($RESPONSE);
        
        $this->assertEquals(["HTTP/1.1 200 OK"], 
            $api->response_status_lines);
        $this->assertEquals((object) [
            'content_type' => ["text/json", "application/json"]
        ], $api->headers);
        $this->assertEquals("body", $api->response);
    }
    
    public function test_multistatus_response(){
        $RESPONSE = "HTTP/1.1 100 Continue\r\n\r\nHTTP/1.1 200 OK\r\nCache-Control: no-cache\r\nContent-Type: application/json\r\n\r\nbody";
        
        $api = new RestClient;
        // bypass request execution to inject controlled response data.
        $api->parse_response($RESPONSE);
        
        $this->assertEquals(["HTTP/1.1 100 Continue", "HTTP/1.1 200 OK"], 
            $api->response_status_lines);
        $this->assertEquals((object) [
                'cache_control' => "no-cache", 
                'content_type' => "application/json"
            ], $api->headers);
        $this->assertEquals("body", $api->response);
    }
    
    public function test_status_only_response(){
        $RESPONSE = "HTTP/1.1 100 Continue\r\n\r\n";
        
        $api = new RestClient;
        // bypass request execution to inject controlled response data.
        $api->parse_response($RESPONSE);
        
        $this->assertEquals(["HTTP/1.1 100 Continue"], 
            $api->response_status_lines);
        $this->assertEquals((object) [], $api->headers);
        $this->assertEquals("", $api->response);
    }
    
    public function test_build_indexed_queries(){
        global $TEST_SERVER_URL;
        
        $api = new RestClient(['build_indexed_queries' => TRUE]);
        $result = $api->get($TEST_SERVER_URL, [
            'foo' => ' bar', 'baz' => 1, 'bat' => ['foo', 'bar', 'baz[12]']
        ]);
        
        $response_json = $result->decode_response();
        $this->assertEquals("foo=+bar&baz=1&bat%5B0%5D=foo&bat%5B1%5D=bar&bat%5B2%5D=baz%5B12%5D", 
            $response_json->SERVER->QUERY_STRING);
    }
    
    public function test_build_non_indexed_queries(){
        global $TEST_SERVER_URL;
        
        $api = new RestClient;
        $result = $api->get($TEST_SERVER_URL, [
            'foo' => ' bar', 'baz' => 1, 'bat' => ['foo', 'bar', 'baz[12]']
        ]);
        
        $response_json = $result->decode_response();
        $this->assertEquals("foo=+bar&baz=1&bat%5B%5D=foo&bat%5B%5D=bar&bat%5B%5D=baz%5B12%5D", 
            $response_json->SERVER->QUERY_STRING);
    }
}


