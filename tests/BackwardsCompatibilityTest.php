<?php declare(strict_types=1);

namespace RestClient;

use \RestClient;
use PHPUnit\Framework\TestCase;

// This varible can be overridden with a PHPUnit XML configuration file.
if(!isset($TEST_SERVER_URL))
    $TEST_SERVER_URL = "http://localhost:8888"; 

class RestClientTest extends TestCase {

    public function test_execute_get() : void {
        global $TEST_SERVER_URL;
        
        $api = new RestClient;
        $result = $api->execute('GET', $TEST_SERVER_URL, [
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
    
    public function test_get() : void {
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
    
    public function test_post() : void {
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
    
    public function test_put() : void {
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
    
    public function test_delete() : void {
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
    
    public function test_user_agent() : void {
        global $TEST_SERVER_URL;
        
        $api = new RestClient(array(
            'user_agent' => "RestClient Unit Test"
        ));
        $result = $api->get($TEST_SERVER_URL);
        
        $response_json = $result->decode_response();
        $this->assertEquals("RestClient Unit Test", 
            $response_json->headers->{"User-Agent"});
    }
    
    public function test_json_patch() : void {
        global $TEST_SERVER_URL;
        
        $api = new RestClient;
        $result = $api->execute('PATCH', $TEST_SERVER_URL, 
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
    
    public function test_json_post() : void {
        global $TEST_SERVER_URL;
        
        $api = new RestClient;
        $result = $api->execute('POST', $TEST_SERVER_URL, "{\"foo\":\"bar\"}",
            array('Content-Type' => 'application/json'));
        $response_json = $result->decode_response();
        
        $this->assertEquals('application/json', 
            $response_json->headers->{"Content-Type"});
        $this->assertEquals('POST', 
            $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("{\"foo\":\"bar\"}", 
            $response_json->body);
    }
    
    // public function test_multiheader_response() : void {
    //     $RESPONSE = "HTTP/1.1 200 OK\r\nContent-type: text/json\r\nContent-Type: application/json\r\n\r\nbody";
        
    //     $api = new RestClient;
    //     // bypass request execution to inject controlled response data.
    //     $api->parse_response($RESPONSE);
        
    //     $this->assertEquals(["HTTP/1.1 200 OK"], 
    //         $api->response_status_lines);
    //     $this->assertEquals((object) [
    //         'content_type' => ["text/json", "application/json"]
    //     ], $api->headers);
    //     $this->assertEquals("body", $api->response);
    // }
    
    // public function test_multistatus_response() : void {
    //     $RESPONSE = "HTTP/1.1 100 Continue\r\n\r\nHTTP/1.1 200 OK\r\nCache-Control: no-cache\r\nContent-Type: application/json\r\n\r\nbody";
        
    //     $api = new RestClient;
    //     // bypass request execution to inject controlled response data.
    //     $api->parse_response($RESPONSE);
        
    //     $this->assertEquals(["HTTP/1.1 100 Continue", "HTTP/1.1 200 OK"], 
    //         $api->response_status_lines);
    //     $this->assertEquals((object) [
    //             'cache_control' => "no-cache", 
    //             'content_type' => "application/json"
    //         ], $api->headers);
    //     $this->assertEquals("body", $api->response);
    // }
    
    // public function test_status_only_response() : void {
    //     $RESPONSE = "HTTP/1.1 100 Continue\r\n\r\n";
        
    //     $api = new RestClient;
    //     // bypass request execution to inject controlled response data.
    //     $api->parse_response($RESPONSE);
        
    //     $this->assertEquals(["HTTP/1.1 100 Continue"], 
    //         $api->response_status_lines);
    //     $this->assertEquals((object) [], $api->headers);
    //     $this->assertEquals("", $api->response);
    // }
    
    public function test_build_indexed_queries() : void {
        global $TEST_SERVER_URL;
        
        $api = new RestClient(['build_indexed_queries' => TRUE]);
        $result = $api->get($TEST_SERVER_URL, [
            'foo' => ' bar', 'baz' => 1, 'bat' => ['foo', 'bar', 'baz[12]']
        ]);
        
        $response_json = $result->decode_response();
        $this->assertEquals("foo=+bar&baz=1&bat%5B0%5D=foo&bat%5B1%5D=bar&bat%5B2%5D=baz%5B12%5D", 
            $response_json->SERVER->QUERY_STRING);
    }
    
    public function test_build_non_indexed_queries() : void {
        global $TEST_SERVER_URL;
        
        $api = new RestClient(['build_indexed_queries' => FALSE]);
        $result = $api->get($TEST_SERVER_URL, [
            'foo' => ' bar', 'baz' => 1, 'bat' => ['foo', 'bar', 'baz[12]']
        ]);
        
        $response_json = $result->decode_response();
        $this->assertEquals("foo=+bar&baz=1&bat%5B%5D=foo&bat%5B%5D=bar&bat%5B%5D=baz%5B12%5D", 
            $response_json->SERVER->QUERY_STRING);
    }

    public function test_null_base_url() : void {
        global $TEST_SERVER_URL;

        $api = new RestClient(
            ['base_url' => null]
        );
        $result = $api->get($TEST_SERVER_URL);
        $this->assertEquals(200, $result->info->http_code);
        $this->assertEquals($TEST_SERVER_URL.'/', $result->info->url);
    }

    public function test_empty_string_url() : void {
        global $TEST_SERVER_URL;

        $api = new RestClient(
            ['base_url' => $TEST_SERVER_URL]
        );
        $result = $api->get('');
        $this->assertEquals(200, $result->info->http_code);
        $this->assertEquals($TEST_SERVER_URL.'/', $result->info->url);
    }

    public function test_null_url() : void {
        global $TEST_SERVER_URL;

        $api = new RestClient(
            ['base_url' => $TEST_SERVER_URL]
        );
        $result = $api->get(null);
        $this->assertEquals(200, $result->info->http_code);
        $this->assertEquals($TEST_SERVER_URL.'/', $result->info->url);
    }

    public function test_no_url() : void {
        global $TEST_SERVER_URL;

        $api = new RestClient(
            ['base_url' => $TEST_SERVER_URL]
        );
        $result = $api->get();
        Log::debug($result);
        $this->assertEquals(200, $result->info->http_code);
        $this->assertEquals($TEST_SERVER_URL.'/', $result->info->url);
    }
}


