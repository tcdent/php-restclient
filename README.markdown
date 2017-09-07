PHP REST Client
===============
https://github.com/tcdent/php-restclient  
(c) 2013-2017 Travis Dent <tcdent@gmail.com>  

Installation
-----------
``` sh
$ php composer.phar require tcdent/php-restclient
```


Basic Usage
-----------
``` php
$api = new RestClient([
    'base_url' => "https://api.twitter.com/1.1", 
    'format' => "json", 
     // https://dev.twitter.com/docs/auth/application-only-auth
    'headers' => ['Authorization' => 'Bearer '.OAUTH_BEARER], 
]);
$result = $api->get("search/tweets", ['q' => "#php"]);
// GET http://api.twitter.com/1.1/search/tweets.json?q=%23php
if($result->info->http_code == 200)
    var_dump($result->decode_response());
```


Configurable Options
--------------------
`headers` - An associative array of HTTP headers and values to be included in every request.  
`parameters` - An associative array of URL or body parameters to be included in every request.  
`curl_options` - cURL options to apply to every request; anything defined here: https://secure.php.net/manual/en/function.curl-setopt.php. These will override any automatically generated values.  
`build_indexed_queries` `(bool)` - `http_build_query` automatically adds an array index to repeated parameters which is not desirable on most systems. Use this option to enable the default behavior. Defaults to `FALSE`.
`user_agent` - User agent string to use in requests.  
`base_url` - URL to use for the base of each request.  
`format` - Format string is appended to resource on request (extension), and used to determine which decoder to use on response; a request URL like "api.twitter.com/1.1/statuses/user_timeline.json" would be expected to return well-formed JSON.  
`format_regex` - Pattern to extract format from response Content-Type header, used to determine which decoder to use on response.  
`decoders` - Associative array of format decoders. See ["Direct Iteration and Response Decoding"](#direct-iteration-and-response-decoding).  
`username` - Username to use for HTTP basic authentication. Requires `password`.  
`password` - Password to use for HTTP basic authentication. Requires `username`.  

Options can be set upon instantiation, or individually afterword:

``` php
$api = new RestClient([
    'format' => "json", 
    'user_agent' => "my-application/0.1"
]);
```
-or-

``` php
$api = new RestClient;
$api->set_option('format', "json");
$api->set_option('user_agent', "my-application/0.1");
```


Standard Verbs
--------------
Four HTTP verbs are implemented as convenience methods: `get()`, `post()`, `put()` and `delete()`. Each accepts three arguments:  

`url` `(string)` - URL of the resource you are requesting. Will be prepended with the value of the `base_url` option, if it has been configured. Will be appended with the value of the `format` option, if it has been configured.  

`parameters` `(string), (array)` - String or associative array to be appended to the URL in `GET` requests and passed in the request body on all others. If an array is passed it will be encoded into a query string. A nested, indexed `array` is passed for parameters with multiple values and will be iterated to populate duplicate keys See: ["Duplicate Headers and Parameters"](#duplicate-headers-and-parameters)

`headers` `(array)` - An associative array of headers to include with the request. A nested, indexed `array` is passed for parameters with multiple values and will be iterated to populate duplicate keys See: ["Duplicate Headers and Parameters"](#duplicate-headers-and-parameters)


Other Verbs
-----------
You can make a request using any verb by calling `execute()` directly, which accepts four arguments: `url`, `method`, `parameters` and `headers`. All arguments expect the same values as in the convenience methods, with the exception of the additional `method` argument:

`method` `(string)` - HTTP verb to perform the request with. 


Response Details
----------------
After making a request with one of the HTTP verb methods, or `execute`, the returned instance will have the following data populated:

`response` `(string)`- The raw response body content. See ["Direct Iteration and Response Decoding"](#direct-iteration-and-response-decoding) for ways to parse and access this data.

`headers` `(object)` - An object with all of the response headers populated. Indexes are transformed to `snake_case` for access. Duplicate headers are available as an indexed array under the shared key.
``` php
$response->headers->content_type;
$response->headers->x_powered_by;
```

`info` `(object)` - An object with information about the transaction. Populated by casting `curl_info` to an object. See PHP documentation for more info: http://php.net/manual/en/function.curl-getinfo.php Available attributes are: 

    url, content_type, http_code, header_size, request_size, filetime, 
    ssl_verify_result, redirect_count, total_time, namelookup_time, connect_time, 
    pretransfer_time, size_upload, size_download, speed_download, speed_upload, 
    download_content_length, upload_content_length, starttransfer_time, redirect_time, 
    certinfo, primary_ip, primary_port, local_ip, local_port, redirect_url  

`error` `(string)` - cURL error message, if applicable.

`response_status_lines` `(array)` - Indexed array of raw HTTP response status lines. See: ["Multiple HTTP Status Lines"](#multiple-http-status-lines).


Direct Iteration and Response Decoding
--------------------------------------
If the the response data format is supported, the response will be decoded 
and accessible by iterating over the returned instance. When the `format` 
option is set, it will be used to select the decoder. If no `format` option 
is provided, an attempt is made to extract it from the response `Content-Type` 
header. This pattern is configurable with the `format_regex` option.

``` php
$api = new RestClient([
    'base_url' => "http://vimeo.com/api/v2", 
    'format' => "php"
]);
$result = $api->get("tcdent/info");
// GET http://vimeo.com/api/v2/tcdent/info.php
foreach($result as $key => $value)
    var_dump($value);
```

Reading via ArrayAccess has been implemented, too:

``` php
var_dump($result['id']);
```

To access the decoded response as an array, call `decode_response()`.

'json' and 'php' formats are configured to use the built-in `json_decode` 
and `unserialize` functions, respectively. Overrides and additional 
decoders can be specified upon instantiation, or individually afterword. 
Decoder functions take one argument: the raw request body. Lambdas and functions created with `create_function` work, too. 

``` php
function my_xml_decoder($data){
    new SimpleXMLElement($data);
}

$api = new RestClient([
    'format' => "xml", 
    'decoders' => ['xml' => "my_xml_decoder"]
]);
```

-or-

``` php
$api = new RestClient;
$api->set_option('format', "xml");
$api->register_decoder('xml', "my_xml_decoder");
```

Or, using a lambda; this particular example allows you to receive decoded JSON data as an array.

``` php
$api->register_decoder('json', function($data){
    return json_decode($data, TRUE);
});
```


Duplicate Headers and Parameters
--------------------------------
When duplicate (repeated) HTTP headers are received, they are accessible via an indexed array referenced by the shared key. Duplicated headers and parameters may also be constructed in requests using the same format. 

Example (unlikely) response:

```
HTTP/1.1 200 OK
Content-Type: text/html
Content-Type: text/html; charset=UTF-8
```

Accessing repeated headers in the response instance:

``` php
$result = $api->get('/');
var_dump($result->headers->content_type);

=> ["text/html", "text/html; charset=UTF-8"]
```

Passing repeated headers and parameters in a request:

``` php
$result = $api->get('/', [
    'foo[]' => ['bar', 'baz']
], [
    'Accept' => ['text/json', 'application/json']
]);
```

Will create headers and a query string (GET) or response body (POST, etc) with the following content:

```
GET /?foo[]=bar&foo[]=baz HTTP/1.1
Accept: text/json
Accept: application/json
```


Multiple HTTP Status Lines
--------------------------
Multiple status lines returned in a single response payload are supported, and available as `response_status_lines` which is an indexed array populated on the response instance.

Example response with multiple status lines (truncated):

``` 
HTTP/1.1 100 Continue

HTTP/1.1 200 OK
Cache-Control: no-cache
...
```

``` php
$result = $api->get('/');
var_dump($result->response_status_lines);

=> ["HTTP/1.1 100 Continue", "HTTP/1.1 200 OK"]
```


JSON Verbs
----------
This library will never validate or construct `PATCH JSON` content, but it can be configured to communicate well-formed data.

`PATCH JSON` content with correct content type:

``` php
$result = $api->execute("http://httpbin.org/patch", 'PATCH',
    json_encode([foo' => 'bar']), [
        'X-HTTP-Method-Override' => 'PATCH', 
        'Content-Type' => 'application/json-patch+json']);
```

Tests
-----
The test package includes a simple server script which returns debug information for verifying functionality. Start the server first, then run tests:

``` sh
$ php -S localhost:8888 test.php
$ phpunit test
```

* Requires PHP > 5.5.7 in order for `getallheaders` data to populate.
* If you specify an alternate port number or hostname to the PHP server you need to re-configure it in your `phpunit.xml` file:

``` xml
<php><var name="TEST_SERVER_URL" value="http://localhost:8888"/></php>
```


