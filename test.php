<?php

// Unit Tests

if (!class_exists('RestClient')) {
    require 'restclient.php';
}

class RestClientTest extends PHPUnit_Framework_TestCase {

    const TEST_SERVER_URL = "http://localhost:8888/server.php";
    const TEST_REDIRECT_URL = "http://localhost:8888/redirect.php";
        
    protected function callService($method, $url, $params) {
        $api = new RestClient;
        $result = $api->$method($url, $params);
        if (in_array($result->info->http_code, [301, 302, 303, 307, 308])) {
            $result = $this->callService($method, $result->info->redirect_url, $params);
        }
        return $result;
    }
    
    public function test_get() {
        $result = $this->callService('get', static::TEST_SERVER_URL, [
            'foo' => ' bar', 'baz' => 1, 'bat' => ['foo', 'bar']
        ]);
        
        $response_json = $result->decode_response();
        $this->assertEquals('GET', $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1&bat%5B0%5D=foo&bat%5B1%5D=bar", $response_json->SERVER->QUERY_STRING);
        $this->assertEquals("", $response_json->body);
    }
    
    public function test_get_redirect() {
        $result = $this->callService('get', static::TEST_REDIRECT_URL, [
            'foo' => ' bar', 'baz' => 1, 'bat' => ['foo', 'bar']
        ]);
        
        $response_json = $result->decode_response();
        $this->assertEquals('GET', $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1&bat%5B0%5D=foo&bat%5B1%5D=bar", $response_json->SERVER->QUERY_STRING);
        $this->assertEquals("", $response_json->body);
    }

    public function test_post() {
        $result = $this->callService('post', static::TEST_SERVER_URL, [
            'foo' => ' bar', 'baz' => 1, 'bat' => ['foo', 'bar']
        ]);

        $response_json = $result->decode_response();
        $this->assertEquals('POST', $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1&bat%5B0%5D=foo&bat%5B1%5D=bar", $response_json->body);
    }
    
    public function test_post_redirect() {
        $result = $this->callService('post', static::TEST_REDIRECT_URL, [
            'foo' => ' bar', 'baz' => 1, 'bat' => ['foo', 'bar']
        ]);
        
        $response_json = $result->decode_response();
        $this->assertEquals('POST', $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1&bat%5B0%5D=foo&bat%5B1%5D=bar", $response_json->body);
    }

    public function test_put() {
        $result = $this->callService('put', static::TEST_SERVER_URL, ['foo' => ' bar', 'baz' => 1]);
        
        $response_json = $result->decode_response();
        $this->assertEquals('PUT', $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1", $response_json->body);
    }
    
    public function test_put_redirect() {
        $result = $this->callService('put', static::TEST_REDIRECT_URL, ['foo' => ' bar', 'baz' => 1]);
                
        $response_json = $result->decode_response();
        $this->assertEquals('PUT', $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1", $response_json->body);
    }

    public function test_delete() {
        $result = $this->callService('delete', static::TEST_SERVER_URL, ['foo' => ' bar', 'baz' => 1]);
        
        $response_json = $result->decode_response();
        $this->assertEquals('DELETE', $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1", $response_json->body);
    }

    public function test_delete_redirect() {
        $result = $this->callService('delete', static::TEST_REDIRECT_URL, ['foo' => ' bar', 'baz' => 1]);
        
        $response_json = $result->decode_response();
        $this->assertEquals('DELETE', $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("foo=+bar&baz=1", $response_json->body);
    }

    public function test_user_agent() {
        $api = new RestClient(array(
            'user_agent' => "RestClient Unit Test"
        ));
        $result = $api->get(static::TEST_SERVER_URL);

        $response_json = $result->decode_response();
        $this->assertEquals("RestClient Unit Test", $response_json->headers->{"User-Agent"});
    }

    public function test_json_patch() {
        $api = new RestClient;
        $result = $api->execute(static::TEST_SERVER_URL, 'PATCH', "{\"foo\":\"bar\"}", array(
            'X-HTTP-Method-Override' => 'PATCH',
            'Content-Type' => 'application/json-patch+json'));
        $response_json = $result->decode_response();

        $this->assertEquals('application/json-patch+json', $response_json->headers->{"Content-Type"});
        $this->assertEquals('PATCH', $response_json->headers->{"X-HTTP-Method-Override"});
        $this->assertEquals('PATCH', $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("{\"foo\":\"bar\"}", $response_json->body);
    }

    public function test_json_post() {
        $api = new RestClient;
        $result = $api->post(static::TEST_SERVER_URL, "{\"foo\":\"bar\"}", array('Content-Type' => 'application/json'));
        $response_json = $result->decode_response();

        $this->assertEquals('application/json', $response_json->headers->{"Content-Type"});
        $this->assertEquals('POST', $response_json->SERVER->REQUEST_METHOD);
        $this->assertEquals("{\"foo\":\"bar\"}", $response_json->body);
    }

    public function test_multiheader_response() {
        $RESPONSE = "HTTP/1.1 200 OK\r\nContent-type: text/json\r\nContent-Type: application/json\r\n\r\nbody";

        $api = new RestClient;
        // bypass request execution to inject controlled response data.
        $api->parse_response($RESPONSE);

        $this->assertEquals(["HTTP/1.1 200 OK"], $api->response_status_lines);
        $this->assertEquals((object) [
                    'content_type' => ["text/json", "application/json"]
                ], $api->headers);
        $this->assertEquals("body", $api->response);
    }

    public function test_multistatus_response() {
        $RESPONSE = "HTTP/1.1 100 Continue\r\n\r\nHTTP/1.1 200 OK\r\nCache-Control: no-cache\r\nContent-Type: application/json\r\n\r\nbody";

        $api = new RestClient;
        // bypass request execution to inject controlled response data.
        $api->parse_response($RESPONSE);

        $this->assertEquals(["HTTP/1.1 100 Continue", "HTTP/1.1 200 OK"], $api->response_status_lines);
        $this->assertEquals((object) [
                    'cache_control' => "no-cache",
                    'content_type' => "application/json"
                ], $api->headers);
        $this->assertEquals("body", $api->response);
    }

    public function test_status_only_response() {
        $RESPONSE = "HTTP/1.1 100 Continue\r\n\r\n";

        $api = new RestClient;
        // bypass request execution to inject controlled response data.
        $api->parse_response($RESPONSE);

        $this->assertEquals(["HTTP/1.1 100 Continue"], $api->response_status_lines);
        $this->assertEquals((object) [], $api->headers);
        $this->assertEquals("", $api->response);
    }

}
