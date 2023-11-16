<?php declare(strict_types=1);

namespace RestClient;

use \RestClient;
use PHPUnit\Framework\TestCase;

// This varible can be overridden with a PHPUnit XML configuration file.
if(!isset($TEST_SERVER_URL))
    $TEST_SERVER_URL = "http://localhost:8888"; 

class CoreTest extends TestCase {
    public function test_execute_get() : void {
        global $TEST_SERVER_URL;
        
        $api = new RestClient;
        $response = $api->execute('GET', $TEST_SERVER_URL, [
            'foo' => ' bar', 'baz' => 1, 'bat' => ['foo', 'bar']
        ]);
        // $this->assertEquals(TRUE,
        //     $response->success);
        $this->assertEquals('GET', 
            $response->data->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1&bat%5B%5D=foo&bat%5B%5D=bar", 
            $response->data->SERVER->QUERY_STRING);
        $this->assertEquals("", 
            $response->data->body);
    }
    
    public function test_get() : void {
        global $TEST_SERVER_URL;
        
        $api = new RestClient;
        $response = $api->get($TEST_SERVER_URL, [
            'foo' => ' bar', 'baz' => 1, 'bat' => ['foo', 'bar']
        ]);
        
        $this->assertEquals('GET', 
            $response->data->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1&bat%5B%5D=foo&bat%5B%5D=bar", 
            $response->data->SERVER->QUERY_STRING);
        $this->assertEquals("", 
            $response->data->body);
    }
    
    public function test_post() : void {
        global $TEST_SERVER_URL;
        
        $api = new RestClient;
        $response = $api->post($TEST_SERVER_URL, [
            'foo' => ' bar', 'baz' => 1, 'bat' => ['foo', 'bar']
        ]);
        
        $this->assertEquals('POST', 
            $response->data->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1&bat%5B%5D=foo&bat%5B%5D=bar", 
            $response->data->body);
    }
    
    public function test_put() : void {
        global $TEST_SERVER_URL;
        
        $api = new RestClient;
        $response = $api->put($TEST_SERVER_URL, [
            'foo' => ' bar', 'baz' => 1
        ]);
        
        $this->assertEquals('PUT', 
            $response->data->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1", 
            $response->data->body);
    }
    
    public function test_delete() : void {
        global $TEST_SERVER_URL;
        
        $api = new RestClient;
        $response = $api->delete($TEST_SERVER_URL, [
            'foo' => ' bar', 'baz' => 1
        ]);
        
        $this->assertEquals('DELETE', 
            $response->data->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1", 
            $response->data->body);
    }
}