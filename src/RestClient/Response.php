<?php declare(strict_types=1);

namespace RestClient;

use \RestClient;
use \RestClient\Exception;
use \RestClient\Request;
use \RestClient\Headers;
use \Countable;
use \ArrayAccess;
use \Iterator;

/**
 * `DataAccess`
 * Array and iterator access to $data.
 */
abstract class DataAccess implements ArrayAccess, Iterator {
    public mixed $data;
    
    public function rewind() : void {
        reset($this->data);
    }
    public function current() : mixed {
        return current($this->data);
    }
    public function key() : mixed {
        return key($this->data);
    }
    public function next() : void {
        next($this->data);
    }
    public function valid() : bool {
        return key($this->data) !== NULL;
    }
    public function offsetExists($key) : bool {
        return isset($this->data[$key]);
    }
    public function offsetGet($key) : mixed {
        return $this->data[$key];
    }
    public function offsetSet($key, $value) : void {
        throw new Exception\OutOfBounds("Response data is immutable.");
    }
    public function offsetUnset($key) : void {
        throw new Exception\OutOfBounds("Response data is immutable.");
    }
}

/**
 * `Response`
 * Represents a response from the server.
 * 
 * @property RestClient $client: The RestClient instance that made the request.
 * @property string $response: The raw response string, with headers.
 * @property array $status_lines: The HTTP status line(s) from the response.
 * @property Headers $headers: The HTTP headers from the response.
 * @property string $body: The response body.
 * @property mixed $data: The decoded response body.
 * @property mixed $info: cURL info.
 * @property bool $success: Whether the request was successful.
 * @property bool $fail: Whether the request failed.
 * @property string $error: The cURL error message, if any.
 * @property int $status_code: The HTTP status code.
 * @property string $content_type: The Content-Type header.
 * @property string $format: The response format.
 */
class Response extends DataAccess {
    public Request $request;
    public string $response;
    public array $status_lines;
    public int $status_code;
    public Headers $headers;
    public string $content_type;
    public ?string $body;
    public mixed $data;
    public object $info;
    public string $error;
    public bool $success;
    public bool $fail;

    /**
     * @param Request $request: The request that generated this response.
     * @param string|bool $response: The raw response string, with headers.
     * @param string $error: The cURL error message, if any.
     * @param array $info: cURL info.
     */
    public function __construct(Request $request, string|bool $response, string $error, array $info) {
        $this->request = $request;
        $this->response = $response? $response : '';
        $this->error = $error;
        $this->info = (object) $info;
        $this->status_code = $this->info->http_code;
        $this->success = ($this->status_code >= 200 && $this->status_code < 300);
        $this->fail = !$this->success;
        $this->headers = new Headers();
        $this->status_lines = [];
    }
    /**
     * Property-like aliases.
     * @deprecated `response_status_lines` is deprecated, use `status_lines`.
     * `format` is a convenience property for the response format.
     */
    public function __get(string $key) {
        switch($key) {
            case 'response_status_lines':
                trigger_error('`response_status_lines` is deprecated, use `status_lines`', E_USER_DEPRECATED);
                return $this->status_lines;
            case 'format':
                return $this->format();
        }
    }
    /**
     * `parse`
     * Parse the response into headers, status lines, and body. Sets: `$this->body`, 
     * `$this->headers`, `$this->status_lines`, and `$this->content_type`.
     * @return void
     */
    public function parse() : void {
        // Since we tokenize on \n, use the remaining \r to detect empty lines.
        // `break` Must be the newline after headers, move on to response body
        // If the line contains "HTTP" it must be one or more HTTP status lines
        // Anything that doesn't meet the above criteria has to be a header
        $line = strtok($this->response, "\n");
        if($line) {
            do {
                if(strlen(trim($line)) == 0) {
                    if(count($this->headers) > 0) break;
                    continue;
                }
                elseif(strpos($line, 'HTTP') === 0)
                    $this->status_lines[] = trim($line);
                else {
                    [$key, $value] = explode(':', $line, 2);
                    $this->headers[$key] = trim($value);
                }
            } while($line = strtok("\n"));
        }
        $body = strtok('');
        $this->body = $body? $body : '';
        $cont = $this->headers['Content-Type'] ?? '';
        $this->content_type = is_array($cont)? $cont[-1] : $cont;
        strtok('', ''); // free memory
    }
    /**
     * `decode`
     * Decode the response body. Sets: `$this->data`.
     * @param callable $decoder: The decoder function.
     * @return void
     */
    public function decode(callable $decoder) : void {
        $this->data = $decoder($this->body);
    }
    /**
     * `decode_response`
     * Decode the response body and return the result.
     * @deprecated Use `data` property.
     * @return mixed
     */
    public function decode_response() : mixed {
        trigger_error('`decode_response()` is deprecated, use `data` property', E_USER_DEPRECATED);
        return $this->data;
    }
    /**
     * `format`
     * Return the format of the response's content type.
     * Example: "Content-Type: application/json" -> json
     * @return string
     */
    private function format() : string {
        /**
         * Take an HTTP Content-Type header and return the format.
         * application/json -> json
         * text/html -> html
         * text/html; charset=utf-8 -> html
         * text/plain;charset=UTF-8 -> plain
         * application/xml; charset=utf-8 -> xml
         */
        $re = '/(\w+)\/(\w+)(;[.+])?/';
        if(preg_match($re, $this->content_type, $match))
            return $match[2];
        return 'text';
    }
}
