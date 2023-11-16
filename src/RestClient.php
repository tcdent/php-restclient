<?php declare(strict_types=1);

 use RestClient\Log;
 use RestClient\Attributes;
 use RestClient\{ Params, Headers, Resource };
 use RestClient\{ Request, Response };
 use RestClient\Exception;

class RestClientException extends Exception\Base {
    public function __construct($message, $code=0, $previous=NULL) {
        trigger_error("RestClient: RestClientException is deprecated, use RestClient\Exception instead", E_USER_DEPRECATED);
        parent::__construct($message, $code, $previous);
    }
}

/**
 * PHP REST client 2
 * @version 0.2.0
 * @param string|Resource $base_url: Base URL for all requests
 * @param string $user_agent: User agent string
 * @param int $timeout: Connection timeout in seconds
 * @param Headers $headers: Default headers for all requests
 * @param array|Params $params: Default parameters for all requests
 * @param CurlHandle $handle: cURL handle
 * @param array $curl_options: Default cURL options for all requests
 * @param array $allowed_verbs: Supported HTTP verbs
 * @param bool $build_indexed_queries: Whether to preserve indexes
 *     when converting arrays to query strings. default: FALSE
 *     e.g. `?foo[]=1&foo[]=2` instead of `?foo[0]=1&foo[1]=2`
 * @param string $format_regex: Regular expression used to parse the response
 *    format from the Content-Type header.
 * @param array $decoders: Associative array of response formats and their
 *   corresponding decoder functions. 
 * @method Response get(string $url, array|string $params=[], array $headers=[])
 * @method Response post(string $url, array|string $params=[], array $headers=[])
 * @method Response put(string $url, array|string $params=[], array $headers=[])
 * @method Response delete(string $url, array|string $params=[], array $headers=[])
 * @method Response patch(string $url, array|string $params=[], array $headers=[])
 * @method Response head(string $url, array|string $params=[], array $headers=[])
 * @method Response options(string $url, array|string $params=[], array $headers=[])
 * @method Response execute(string $method, string $url, array|string $params=[], array $headers=[])
 * @method void set_option(string $key, mixed $value)
 * @method void register_decoder(string $format, callable $method)
 * @method callable get_decoder(string $format)
 */
class RestClient {
    use Attributes;

    public string $user_agent = \RestClient\USER_AGENT;
    public int $timeout = 10;
    public string|Resource $base_url = "";
    public array|Headers $headers = [];
    public array|Params $params = [];
    public CurlHandle $handle;
    public array $curl_options = [];
    public array $allowed_verbs = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
    public bool $build_indexed_queries = FALSE;
    public string $format_regex = "/(\w+)\/(\w+)(;[.+])?/";
    public array $decoders = [
        'text' => 'strval', 
        'json' => 'json_decode', 
        'php' => 'unserialize'
    ];
    const _option_keys = ['base_url', 'headers', 'params', 'user_agent', 
        'timeout', 'curl_options', 'build_indexed_queries'];

    /**
     * @param string|Resource $base_url: Base URL for all requests
     * @param string $user_agent: User agent string
     * @param int $timeout: Connection timeout in seconds
     * @param array|Headers $headers: Default headers for all requests
     * @param array|Params $params: Default parameters for all requests
     * @param array $curl_options: Default cURL options for all requests
     * @param bool $build_indexed_queries: Whether to preserve indexes
     *    when converting arrays to query strings. default: FALSE
     *   e.g. `?foo[]=1&foo[]=2` instead of `?foo[0]=1&foo[1]=2`
     */
    public function __construct(
        null|array|string|Resource $base_url=NULL, 
        null|array|Headers $headers=NULL, 
        null|array|Params $params=NULL, 
        null|string $user_agent=NULL, 
        null|int $timeout=NULL, 
        null|array $curl_options=NULL, 
        null|bool $build_indexed_queries=NULL,
    ) {
        if(is_array($base_url)) {
            [$args, $base_url] = [$base_url, NULL];
            trigger_error("RestClient: Passing an array to `new` is deprecated, use named parameters instead", E_USER_DEPRECATED);
            extract(array_filter($args, function($k) {
                return in_array($k, self::_option_keys);
            }, ARRAY_FILTER_USE_KEY));
        }

        $this->user_agent = $user_agent ?? $this->user_agent;
        $this->timeout = $timeout ?? $this->timeout;
        $this->curl_options = $curl_options ?? $this->curl_options;
        $this->build_indexed_queries = $build_indexed_queries ?? $this->build_indexed_queries;

        if($base_url) {
            $this->base_url = $base_url;
        }
        if(!$this->base_url instanceof Resource) {
            $this->base_url = new Resource($this->base_url ?? '', 
                QUERY_INDEXES: $this->build_indexed_queries);
        }

        if(!$this->headers instanceof Headers)
            $this->headers = new Headers($this->headers ?? []);
        if($headers) {
            if(!$headers instanceof Headers)
                $headers = new Headers($headers);
            $this->headers = $this->headers->merge($headers);
        }

        if(!$this->params instanceof Params)
            $this->params = new Params($this->params ?? [], 
                INDEXES: $this->build_indexed_queries);
        if($params) {
            if(!$params instanceof Params)
                $params = new Params($params);
            $this->params = $this->params->merge($params);
        }
        
        // https://www.php.net/manual/en/function.curl-init.php#128394
        // share curl_init handle between requests?
        $this->handle = curl_init();
    }
    /**
     * Property-like aliases.
     * @deprecated  `parameters` is deprecated, use `params` instead.
     */
    public function __get($key){
        switch($key) {
            case 'parameters':
                trigger_error("RestClient: `parameters` is deprecated, use `params`", E_USER_DEPRECATED);
                return $this->params;
        }
    }
    /**
     * `set_option`
     * Set a RestClient option. 
     * @deprecated Use named parameters in the constructor or access instance attributes directly.
     * @param string $key: Option name
     * @param mixed $value: Option value
     */
    public function set_option($key, $value) : void {
        trigger_error("RestClient: set_option() has been deprecated, set options via the constructor or on a subclass.", E_USER_DEPRECATED);
        if(!in_array($key, self::_option_keys))
        throw new Exception\OutOfBounds("'{$key}' is not a valid option.");
        $this->$key = $value;
    }
    /**
     * `register_decoder`
     * Register a custom decoder for a response format.
     * @param string $format: Response format to register. Example: 'json'
     * @param callable $method: Callable to decode the response. Can either be a 
     * function name (as a string) or a lambda function (as a closure).
     * @return void
     */
    public function register_decoder(string $format, callable $method) : void {
        $this->decoders[$format] = $method;
    }
    /**
     * `get_decoder`
     * Get the decoder for a response format.
     * @param string $format: Response format to register. Example: 'json'
     * @return callable
     */
    public function get_decoder(string $format) : callable {
        if(empty($this->decoders[$format]))
            throw new Exception\OutOfBounds("No decoder for format '{$format}'");
        $decoder = $this->decoders[$format];
        log::debug("using decoder `{$decoder}` for format `{$format}`");
        if(is_string($decoder) && function_exists($decoder)){
            return function(...$args) use ($decoder) {
                return $decoder(...$args);
            };
        }
        return $decoder;
    }
    /**
     * `__call`
     * Magic method to allow calling RestClient->GET(), RestClient->POST(), etc.
     * By default, GET, POST, PUT, DELETE, PATCH, HEAD, and OPTIONS are available.
     * Set RestClient->allowed_verbs to change the available methods.
     * Upper or lower case method names will resolve to the same verb.
     * @param string $method: HTTP verb to use in the request.
     * @param array $args: Arguments to pass to RestClient::execute().
     * @return Response
     */
    public function __call(string $method, array $args): Response {
        $method = strtoupper($method);
        if(!in_array($method, $this->allowed_verbs))
            throw new Exception\BadMethodCall("Call to undefined method RestClient::$method");
        return $this->execute(
            method: $method, 
            url: $args[0] ?? $args['url'] ?? NULL, 
            params: $args[1] ?? $args['params'] ?? [], 
            headers: $args[2] ?? $args['headers'] ?? []
        );
    }
    /**
     * `execute`
     * Execute a request.
     * @param string $method: HTTP verb to use in the request. Validity is not enforced.
     * @param null|string|Resource $url: URL to request. Will be prefixed with the base_url.
     * @param string|array|Params $params: Parameters to pass with the request. Will be 
     * merged with the default parameters, and passed as a query string for GET requests or
     * as a POSTFIELDS for all other requests.
     * @param array|Headers $headers: Headers to pass with the request. If array or Params 
     * is passed, it will be merged with the default headers. If any other type is passed,
     * it will be cast to a string and used alone.
     * @return Response
     */
    public function execute(string $method='GET', null|string $url=NULL, string|array|Params $params=[], array|Headers $headers=[]) : Response {
        $method = strtoupper($method);
        $url = $this->base_url->merge(new Resource($url ?? ''));
        
        // Allow passing parameters as a pre-encoded string (or something that
        // allows casting to a string). Parameters passed as strings will not be
        // merged with parameters specified in the default options.
        if(is_array($params))
            $params = new Params($params);
        if($params instanceof Params)
            $params = $this->params->merge($params);
        else
            $params = (string) $params;

        if(is_array($headers))
            $headers = new Headers($headers);
        $headers = $this->headers->merge($headers);
        
        $request = new Request(
            method: $method, 
            url: $url, 
            params: $params, 
            headers: $headers);
        log::debug("$method $url");
        return $this($request);
    }
    /**
     * `__invoke`
     * Execute a request.
     * @param Request $request: Request object to execute.
     * @return Response
     */
    public function __invoke(Request $request) : Response {
        $curlopt = [
            CURLOPT_TIMEOUT => $this->timeout, 
            CURLOPT_HEADER => TRUE, 
            CURLOPT_RETURNTRANSFER => TRUE, 
            CURLOPT_USERAGENT => $this->user_agent
        ];
        if(count($request->headers))
            $curlopt[CURLOPT_HTTPHEADER] = (array) $request->headers;
        if($request->method == 'POST'){
            $curlopt[CURLOPT_POST] = TRUE;
            $curlopt[CURLOPT_POSTFIELDS] = (string) $request->params;
        }
        elseif($request->method != 'GET'){
            $curlopt[CURLOPT_CUSTOMREQUEST] = $request->method;
            $curlopt[CURLOPT_POSTFIELDS] = (string) $request->params;
        }
        elseif(count($request->params))
            $request->url->query = $request->url->query->merge($request->params);
        
        $curlopt[CURLOPT_URL] = (string) $request->url;
        if($this->curl_options){ // array_merge would reset CURLOPT keys.
            foreach($this->curl_options as $key => $value){
                $curlopt[$key] = $value;
            }
        }
        log::debug("curl options: \n", $curlopt);
        curl_setopt_array($this->handle, $curlopt);
        $response = new Response(
            request: $request, 
            response: curl_exec($this->handle), 
            error: curl_error($this->handle), 
            info: curl_getinfo($this->handle));
        $response->parse();
        $response->decode($this->get_decoder($response->format));
        curl_reset($this->handle);
        return $response;
    }
}
