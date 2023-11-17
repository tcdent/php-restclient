PHP REST Client
===============
[![Total Downloads](http://poser.pugx.org/tcdent/php-restclient/downloads)](https://packagist.org/packages/tcdent/php-restclient)

https://github.com/tcdent/php-restclient  
(c) 2013-2023 Travis Dent <tcdent@gmail.com>  

# Installation
``` sh
$ composer require tcdent/php-restclient
```

## Upgrading from 0.1.x
The public API has been kept stable so upgrading should be fairly eventless. Expect deprecation warnings for uses that are no longer encouraged.

See [Backwards-incompatible changes in RestClient 2](#Backwards-incompatible-changes-in-RestClient-2) for breaking changes from 0.1.9.

# `RestClient`
The `RestClient` class is the main interface for making requests and can be used directly for fast implementation. 

``` php
$api = new RestClient(
    base_url: 'https://api.twitter.com/2', 
    headers: ['Authorization' => "Bearer $TOKEN"]
);
$response = $api->GET('tweets/search/recent', [
    'query' => '#php'
]);
// GET https://api.twitter.com/2/tweets/search/recent?query=%23php
if($response->success) {
    foreach($response as $tweet)
        echo "{$tweet->text}\n";
}
```

Skip to [`RestClient\Response`](#RestClientResponse) for explanation of the response object.

## Subclassing
Fully documented interfaces can be configured using subclassing if desired. See the GitHub implementation in the `examples/GitHub` directory for a more complete example.

``` php
namespace MyTwitter;
use RestClient\{Response, Params};
use RestClient\Attributes\Param;

class Client extends RestClient {
    use \RestClient\Attributes;

    public string $base_url = "https://api.twitter.com/2";
    public function __construct(...$args) {
        parent::__construct(...$args);
        this->headers['Authorization'] = 'Bearer '.getenv('TOKEN');
    }

    #[Param(string: 'query')]
    public function search(array|Params $params=[]) : Response {
        return $this->GET('tweets/search/recent', $params);
    }
}

$api = new Client;
$response = $api->search(['query' => '#php']);
if($response->success)
    ...
```

## Public Methods
### `new`
Instantiate a new `RestClient` object.
- (`null` | `string` | `RestClient\Resource`) **`$base_url`**
Base URL for all requests. Will be prepended to all subsequent relative URLs.
- (`null` | `array` | `RestClient\Headers`) **`$headers`**
Default headers for all requests.
- (`null` | `array` | `RestClient\Params`) **`$params`**
Default parameters for all requests.
- (`null` | `string`) **`$user_agent`**
User agent string. It's recommended to set this to identify your application.
- (`null` | `int`) **`$timeout`**
Connection timeout in seconds.
- (`null` | `array`) **`$curl_options`**
Default cURL options for all requests. See: http://php.net/manual/en/function.curl-setopt.php
- (`null` | `bool`) **`$build_indexed_queries`**
Whether to preserve indexes when converting arrays to query strings. Example: `?foo[]=1&foo[]=2` instead of `?foo[0]=1&foo[1]=2`
``` php
new RestClient(
    null | string | RestClient\Resource $base_url=NULL, 
    null | array | RestClient\Headers $headers=NULL, 
    null | array | RestClient\Params $params=NULL, 
    null | string $user_agent=NULL, 
    null | int $timeout=NULL, 
    null | array $curl_options=NULL, 
    null | bool $build_indexed_queries=NULL) : RestClient
```

### `GET` | `POST` | `PUT` | `DELETE` | `PATCH` | `HEAD` | `OPTIONS`
Shortcut methods to perform common request types. 
Setting `RestClient::$allowed_verbs` will modify the available methods.
- (`string`) **`$url`**  
URL or path to request. Will be appended to `RestClient::$base_url` if relative.
- (`array` | `string` | `RestClient\Params`) **`$params`**  
Query string parameters (in a GET request) or encodable body content. Will be merged with `RestClient::$params`.
- (`array` | `RestClient\Headers`) **`$headers`**  
Request headers. Will be merged with `RestClient::$headers`.
``` php
RestClient::GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS (
    string $url, 
    array | string | RestClient\Params $params=[], 
    array | RestClient\Headers $headers=[]) : RestClient\Response
```

### `execute`
Perform a request with any HTTP method.
- (`string`) **`$method`**
HTTP verb to perform the request with. Example: `'GET'`
- (`string`) **`$url`**
URL or path to request. Will be appended to `RestClient::$base_url` if relative.
- (`array` | `string` | `RestClient\Params`) **`$params`**
Query string parameters (in a GET request) or encodable body content. Will be merged with `RestClient::$params`.
- (`array` | `RestClient\Headers`) **`$headers`**
Request headers. Will be merged with `RestClient::$headers`.
``` php
RestClient::execute(
    string $method, 
    string $url, 
    array | string | RestClient\Params $params=[], 
    array | RestClient\Headers $headers=[]) : RestClient\Response
```

### *`@deprecated`* `set_option`
Set a default option for all requests.
- (`string`) **`$option`**
Option name. One of `'base_url'`, `'user_agent'`, `'timeout'`, `'curl_options'`, `'build_indexed_queries'`.
- (`mixed`) **`$value`**
Option value.
``` php
RestClient::set_option(string $option, mixed $value) : void
```

### `register_decoder`
Register a custom decoder function for a format.
- (`string`) **`$format`**
Format name. Example: `'xml'`
- (`callable`) **`$method`**
Decoder function. Takes one argument: the raw request body.
``` php
RestClient::register_decoder(string $format, callable $method) : void
```

### `get_decoder`
Get the decoder function for a format. Returns a `callable` or `NULL` if not found.
- (`string`) **`$format`**
Format name. Example: `'xml'`
``` php
RestClient::get_decoder(string $format) : callable
```

## Public Attributes

### `base_url`
Base URL for all requests. Will be prepended to all subsequent relative URLs.
- (`string` | `RestClient\Resource`) **`$base_url`**
``` php
string | RestClient\Resource RestClient::base_url = ""
```

### `user_agent`
User agent string. It's recommended to set this to identify your application.
- (`string`) **`$user_agent`**
``` php
string RestClient::user_agent = "PHP RestClient..."
```

### `timeout`
Connection timeout in seconds.
- (`int`) **`$timeout`**
``` php
int RestClient::timeout = 10;
```

### `headers`
Default headers for all requests.
- (`array` | `RestClient\Headers`) **`$headers`**
``` php
array | RestClient\Headers RestClient::headers = [];
```

### `params`
Default parameters for all requests.
- (`array` | `RestClient\Params`) **`$params`**
``` php
array | RestClient\Params RestClient::params = [];
```

### `handle`
cURL handle.
- (`\CurlHandle`) **`$handle`**
``` php
\CurlHandle RestClient::handle
```

### `curl_options`
Default cURL options for all requests. See: http://php.net/manual/en/function.curl-setopt.php
- (`array`) **`$curl_options`**
``` php
array RestClient::curl_options = [];
```

### `allowed_verbs`
Supported HTTP verbs.
- (`array`) **`$allowed_verbs`**
``` php
array RestClient::allowed_verbs = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
```

### `build_indexed_queries`
Whether to preserve indexes when converting arrays to query strings. Example: `?foo[]=1&foo[]=2` instead of `?foo[0]=1&foo[1]=2`
- (`bool`) **`$build_indexed_queries`**
``` php
bool RestClient::build_indexed_queries = FALSE;
```

### `format_regex`
Regular expression used to parse the response format from the Content-Type header.
- (`string`) **`$format_regex`**
``` php
string RestClient::format_regex = "/(\w+)\/(\w+)(;[.+])?/";
```

### `decoders`
Associative array of response formats and their corresponding decoder functions.
- (`array`) **`$decoders`**
``` php
array RestClient::decoders = [
    'json' => 'json_decode', 
    'php' => 'unserialize'];
```

# `RestClient\Response`
Contains information about the request and response. 
    
``` php
$api = new RestClient;
$response = $api->get('/');
```

## Determining Success
Two boolean attributes to help determine the success of a request: `success` and `fail`. `success` is `TRUE` if the response status code is between 200 and 299. `fail` is the inverse of `success`. `status_code` is also available for explicit comparisons.

``` php
if($response->success)
    echo "Success";

if($response->fail)
    echo "Request failed: $response->error";

if($response->status_code == 404)
    echo "Not found";
```

## Decoded Response Data
An attempt is made to extract the format from the response `Content-Type` header using the `format_regex`. If the format is listed in `decoders`, the response body is decoded using the provided function and is accessible via the `data` attribute. 

``` php
var_dump($response->data);
```

## Iteration
If the response data format is supported and the data can be iterated, the decoded response body can be accessed like an `array`. 

``` php
foreach($response as $key => $value)
    var_dump($value);

var_dump($response[0]);
```

## Key Access
If the response data format is supported and the data can be accessed by key, the decoded response body can be accessed like an associative `array`. 

``` php
var_dump($response['object']);
```

## Additional Information
All information from `cURL` about the request is available in the `info` attribute. See: http://php.net/manual/en/function.curl-getinfo.php for all available attributes.

``` php
var_dump($response->info->total_time);
var_dump($response->info->size_download);
```

## Request object
The `RestClient\Request` object is available as the `request` attribute for information about the request that was made. 

``` php
var_dump($response->request->method);
var_dump($response->request->url);
```

## Public Attributes

### `success`
Whether the request was successful.
- `(bool)` **`$success`**

### `fail`
Whether the request was unsuccessful.
- `(bool)` **`$fail`**

### `status_code`
HTTP status code.
- `(int)` **`$status_code`**

### `status_lines`
Array of raw HTTP response status lines.
- `(array)` **`$status_lines`**

### `response`
Raw response content including headers.
- `(string)` **`$response`**

### `body`
Response body content.
- `(string)` **`$body`**

### `data`
Decoded response data, if format is supported.
- `(mixed)` **`$data`**

### `headers`
Response headers accessible by array keys.
- `(object)` **`$headers`**

### `info`
Information about the transaction. Populated by casting `curl_info` to an object.
- `(object)` **`$info`**

### `error`
cURL error message, if applicable. Populated by calling `curl_error`.
- `(string)` **`$error`**

### `format`
Response format, if known. Example: `'json'`.
- `(string)` **`$format`**

## Public Methods
`RestClient\Response` has no public methods.

# Exceptions
All exceptions inherit from `RestClient\Exception\Base`.

## `RestClient\Exception\Base`
Base exception class.

## `RestClient\Exception\InvalidArgument`
Thrown when an invalid argument is passed to a method.

## `RestClient\Exception\BadMethodCall`
Thrown when a method is called that is not allowed.

## `RestClient\Exception\OutOfBounds`
Thrown when a parameter is outside of the expected range.

## `RestClient\Exception\Timeout`
Thrown when a request times out.


# Compatibility
__RestClient 2__ requires PHP 8.3 or above with native cURL support. It has no other dependencies.

For PHP 7.x use [0.1.9](https://github.com/tcdent/php-restclient/releases/tag/0.1.9). 

For PHP 5.x use [0.1.7](https://github.com/tcdent/php-restclient/releases/tag/0.1.7). 

## Backwards-incompatible changes in RestClient 2

- Ordering of parameters to `RestClient::execute()` has changed.
The order is now: `method`, `url`, `params`, `headers` instead of 
`url`, `method`, `params`, `headers`.
- `set_option()` has been removed.
- Removed support for HTTP basic authentication. 
    Implement yourself like this: `$this->curl_options[CURLOPT_USERPWD] = "username:password"`;
- Removed support for specifying a format as the resource extension.
This was more common in the early days of REST, but is now pretty obscure.
`options['format']` has also been removed.

## Deprecated features in RestClient 2

- `RestClient::__construct()` now expects named parameters.
- It is now preferred to access verb shortcuts as uppercase methods.
`$api->GET()` is preferred to `$api->get()`, though both are still supported and 
deprecation warning will not be raised. 
- Moved return value of `$response->decode_response()` to `$response->data`.
- Headers should be accessed by array keys instead of transformed object properties. 
`$response->headers->content_type` should be `$response->headers['Content-Type']`.
- Renamed `RestClient::parameters` to `RestClient::params`.
- `RestClientException` has been moved to `RestClient\Exception`.

## Improvements in RestClient 2

- `RestClient::execute()` now returns a `RestClient\Response` object instead of a clone of `RestClient`.
- `RestClient\Params`, `RestClient\Headers`, and `RestClient\Resource` are now fully abstracted and parse/reconstruct data on demand.
- Annotations are now supported for constraining parameters and headers in subclasses allowing for explicitly defined endpoints.
- cURL requests are now performed with a shared handle, reducing overhead.
- Tests have been expanded and improved, including a harness for backwards compatibility.
- Exceptions are now namespaced and more specific.

# Decoders
'json' and 'php' formats are configured to use the built-in `json_decode` 
and `unserialize` functions, respectively. Overrides and additional 
decoders can be specified upon instantiation, or individually afterword. 
Decoder functions take one argument: the raw request body. 

``` php
$api = new RestClient([
    'decoders' => ['xml' => fn($data) => new SimpleXMLElement($data)]
]);
```
-or-
``` php
$api = new RestClient;
$api->register_decoder('xml', fn($data) => new SimpleXMLElement($data));
```
-or-
``` php
function my_xml_decoder($data) {
    return new SimpleXMLElement($data);
}
class XMLClient extends RestClient {
    public $decoders = ['xml' => "my_xml_decoder"];
}
```

## JSON as an Array
By default, `json_decode` will return an object. To return an associative array instead, set the `assoc` option to `TRUE`:

``` php
$api = new RestClient([
    'decoders' => ['json' => fn($data) => json_decode($data, TRUE)]
]);
```

# Duplicate Headers and Parameters
When duplicate (repeated) HTTP headers are received, they are accessible via an indexed array referenced by the shared key. Duplicated headers and parameters may also be constructed in requests using the same format. 

Example response:

```
HTTP/1.1 200 OK
Content-Type: text/html
Content-Type: text/html; charset=UTF-8
```

Accessing repeated headers in the response instance:

``` php
$response = $api->get('/');
var_dump($response->headers['Content-Type']);
=> ["text/html", "text/html; charset=UTF-8"]

var_dump($response->content_type);
=> "text/html; charset=UTF-8"
```

Passing repeated headers and parameters in a request:

``` php
$response = $api->GET('/', params: [
    'foo[]' => ['bar', 'baz']
], headers: [
    'Accept' => ['text/json', 'application/json']
]);
```

Will create headers and a query string (GET) or response body (POST, etc) with the following content:

```
GET /?foo[]=bar&foo[]=baz HTTP/1.1
Accept: text/json
Accept: application/json
```

# Multiple HTTP Status Lines
Multiple status lines returned in a single response payload are supported, and available as `response_status_lines` which is an indexed array populated on the response instance.

Example response with multiple status lines:

``` 
HTTP/1.1 100 Continue

HTTP/1.1 200 OK
Cache-Control: no-cache
...
```

``` php
$response = $api->GET('/');
var_dump($response->status_lines);
=> ["HTTP/1.1 100 Continue", "HTTP/1.1 200 OK"]

var_dump($response->status_code);
=> 200
```

# JSON Verbs
`PATCH JSON` content with correct content type:

``` php
$response = $api->execute('PATCH', "http://httpbin.org/patch", 
    json_encode(['foo' => 'bar']), [
        'X-HTTP-Method-Override' => 'PATCH', 
        'Content-Type' => 'application/json-patch+json']);
```

# Testing
The test package includes a simple server script which returns debug information for verifying functionality. Start the server first, then run tests:
``` sh
$ php -S localhost:8888 tests/server.php
$ vendor/bin/phpunit tests
```

* If you specify an alternate port number or hostname to the PHP server you need to re-configure it in a `phpunit.xml` file:
``` xml
<php><var name="TEST_SERVER_URL" value="http://localhost:8888"/></php>
```
