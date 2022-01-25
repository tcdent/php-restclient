<?php

/**
 * PHP REST Client
 * https://github.com/tcdent/php-restclient
 * (c) 2013-2017 Travis Dent <tcdent@gmail.com>
 */

/**
 * @property string $url
 * @property string $content_type
 * @property int $http_code
 * @property int $header_size
 * @property int $request_size
 * @property int $filetime
 * @property int $ssl_verify_result
 * @property int $redirect_count
 * @property float $total_time
 * @property float $namelookup_time
 * @property float $connect_time
 * @property float $pretransfer_time
 * @property float $size_upload
 * @property float $size_download
 * @property float $speed_download
 * @property float $speed_upload
 * @property float $download_content_length
 * @property float $upload_content_length
 * @property float $starttransfer_time
 * @property float $redirect_time
 * @property string $redirect_url
 * @property array $certinfo
 * @property int $primary_port
 * @property string $local_ip
 * @property int $local_port
 * @property int $http_version
 * @property int $protocol
 * @property int $ssl_verifyresult
 * @property string $scheme
 * @property int $appconnect_time_us
 * @property int $connect_time_us
 * @property int $namelookup_time_us
 * @property int $pretransfer_time_us
 * @property int $redirect_time_us
 * @property int $starttransfer_time_us
 * @property int $total_time_us
 */
class Info extends stdClass {
    private $data = [];
    public function __set($name, $value){$this->data[$name] = $value;}
    public function __get($name){return $this->data[$name];}
}

class RestClientException extends Exception {}

class RestClient implements Iterator, ArrayAccess {
    
    public $options;
    public $handle; // cURL resource handle.
    
    // Populated after execution:
    public $response; // Response body.
    public $headers; // Parsed reponse header object.
    /** @var Info*/
    public $info; // Response info object.
    public $error; // Response error string.
    public $response_status_lines; // indexed array of raw HTTP response status lines.
    
    // Populated as-needed.
    public $decoded_response; // Decoded response body. 
    
    public function __construct($options=[]){
        $default_options = [
            'headers' => [], 
            'parameters' => [], 
            'curl_options' => [], 
            'build_indexed_queries' => FALSE, 
            'user_agent' => "PHP RestClient/0.1.7", 
            'base_url' => NULL, 
            'format' => NULL, 
            'format_regex' => "/(\w+)\/(\w+)(;[.+])?/",
            'decoders' => [
                'json' => 'json_decode', 
                'php' => 'unserialize'
            ], 
            'username' => NULL, 
            'password' => NULL
        ];
        
        $this->options = array_merge($default_options, $options);
        if(array_key_exists('decoders', $options))
            $this->options['decoders'] = array_merge(
                $default_options['decoders'], $options['decoders']);
    }
    
    public function set_option($key, $value){
        $this->options[$key] = $value;
    }
    
    public function register_decoder($format, $method){
        // Decoder callbacks must adhere to the following pattern:
        //   array my_decoder(string $data)
        $this->options['decoders'][$format] = $method;
    }
    
    // Iterable methods:
    public function rewind(){
        $this->decode_response();
        return reset($this->decoded_response);
    }
    
    public function current(){
        return current($this->decoded_response);
    }
    
    public function key(){
        return key($this->decoded_response);
    }
    
    public function next(){
        return next($this->decoded_response);
    }
    
    public function valid(){
        return is_array($this->decoded_response)
            && (key($this->decoded_response) !== NULL);
    }
    
    // ArrayAccess methods:
    public function offsetExists($key){
        $this->decode_response();
        return is_array($this->decoded_response)?
            isset($this->decoded_response[$key]) : isset($this->decoded_response->{$key});
    }
    
    public function offsetGet($key){
        $this->decode_response();
        if(!$this->offsetExists($key))
            return NULL;
        
        return is_array($this->decoded_response)?
            $this->decoded_response[$key] : $this->decoded_response->{$key};
    }
    
    public function offsetSet($key, $value){
        throw new RestClientException("Decoded response data is immutable.");
    }
    
    public function offsetUnset($key){
        throw new RestClientException("Decoded response data is immutable.");
    }
    
    // Request methods:
    public function get($url, $parameters=[], $headers=[]){
        return $this->execute($url, 'GET', $parameters, $headers);
    }
    
    public function post($url, $parameters=[], $headers=[]){
        return $this->execute($url, 'POST', $parameters, $headers);
    }
    
    public function put($url, $parameters=[], $headers=[]){
        return $this->execute($url, 'PUT', $parameters, $headers);
    }
    
    public function patch($url, $parameters=[], $headers=[]){
        return $this->execute($url, 'PATCH', $parameters, $headers);
    }
    
    public function delete($url, $parameters=[], $headers=[]){
        return $this->execute($url, 'DELETE', $parameters, $headers);
    }
    
    public function head($url, $parameters=[], $headers=[]){
        return $this->execute($url, 'HEAD', $parameters, $headers);
    }
    
    public function execute($url, $method='GET', $parameters=[], $headers=[]){
        $client = clone $this;
        $client->url = $url;
        $client->handle = curl_init();
        $curlopt = [
            CURLOPT_HEADER => TRUE, 
            CURLOPT_RETURNTRANSFER => TRUE, 
            CURLOPT_USERAGENT => $client->options['user_agent']
        ];
        
        if($client->options['username'] && $client->options['password'])
            $curlopt[CURLOPT_USERPWD] = sprintf("%s:%s", 
                $client->options['username'], $client->options['password']);
        
        if(count($client->options['headers']) || count($headers)){
            $curlopt[CURLOPT_HTTPHEADER] = [];
            $headers = array_merge($client->options['headers'], $headers);
            foreach($headers as $key => $values){
                foreach(is_array($values)? $values : [$values] as $value){
                    $curlopt[CURLOPT_HTTPHEADER][] = sprintf("%s:%s", $key, $value);
                }
            }
        }
        
        if($client->options['format'])
            $client->url .= '.'.$client->options['format'];
        
        // Allow passing parameters as a pre-encoded string (or something that
        // allows casting to a string). Parameters passed as strings will not be
        // merged with parameters specified in the default options.
        if(is_array($parameters)){
            $parameters = array_merge($client->options['parameters'], $parameters);
            $parameters_string = http_build_query($parameters);
            
            // http_build_query automatically adds an array index to repeated
            // parameters which is not desirable on most systems. This hack
            // reverts "key[0]=foo&key[1]=bar" to "key[]=foo&key[]=bar"
            if(!$client->options['build_indexed_queries'])
                $parameters_string = preg_replace(
                    "/%5B[0-9]+%5D=/simU", "%5B%5D=", $parameters_string);
        }
        else
            $parameters_string = (string) $parameters;
        
        if(strtoupper($method) == 'POST'){
            $curlopt[CURLOPT_POST] = TRUE;
            $curlopt[CURLOPT_POSTFIELDS] = $parameters_string;
        }
        elseif(strtoupper($method) != 'GET'){
            $curlopt[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
            $curlopt[CURLOPT_POSTFIELDS] = $parameters_string;
        }
        elseif($parameters_string){
            $client->url .= strpos($client->url, '?')? '&' : '?';
            $client->url .= $parameters_string;
        }
        
        if($client->options['base_url']){
            if($client->url[0] != '/' && substr($client->options['base_url'], -1) != '/')
                $client->url = '/' . $client->url;
            $client->url = $client->options['base_url'] . $client->url;
        }
        $curlopt[CURLOPT_URL] = $client->url;
        
        if($client->options['curl_options']){
            // array_merge would reset our numeric keys.
            foreach($client->options['curl_options'] as $key => $value){
                $curlopt[$key] = $value;
            }
        }
        curl_setopt_array($client->handle, $curlopt);
        
        $client->parse_response(curl_exec($client->handle));
        $client->info = $this->wrapInfo(curl_getinfo($client->handle));
        $client->error = curl_error($client->handle);
        
        curl_close($client->handle);
        return $client;
    }
    
    private function wrapInfo($info) {
        $res = new Info();
        
        if (!$info || !is_array($info)) return $res;
        
        foreach ($info as $key => $value) {
            $res->$key = $value;
        }
        
        return $res;
    }
    
    public function parse_response($response){
        $headers = [];
        $this->response_status_lines = [];
        $line = strtok($response, "\n");
        do {
            if(strlen(trim($line)) == 0){
                // Since we tokenize on \n, use the remaining \r to detect empty lines.
                if(count($headers) > 0) break; // Must be the newline after headers, move on to response body
            }
            elseif(strpos($line, 'HTTP') === 0){
                // One or more HTTP status lines
                $this->response_status_lines[] = trim($line);
            }
            else { 
                // Has to be a header
                list($key, $value) = explode(':', $line, 2);
                $key = trim(strtolower(str_replace('-', '_', $key)));
                $value = trim($value);
                
                if(empty($headers[$key]))
                    $headers[$key] = $value;
                elseif(is_array($headers[$key]))
                    $headers[$key][] = $value;
                else
                    $headers[$key] = [$headers[$key], $value];
            }
        } while($line = strtok("\n"));
        
        $this->headers = (object) $headers;
        $this->response = strtok("");
    }
    
    public function get_response_format(){
        if(!$this->response)
            throw new RestClientException(
                "A response must exist before it can be decoded.");
        
        // User-defined format. 
        if(!empty($this->options['format']))
            return $this->options['format'];
        
        // Extract format from response content-type header. 
        if(!empty($this->headers->content_type))
        if(preg_match($this->options['format_regex'], $this->headers->content_type, $matches))
            return $matches[2];
        
        throw new RestClientException(
            "Response format could not be determined.");
    }
    
    public function decode_response(){
        if(empty($this->decoded_response)){
            $format = $this->get_response_format();
            if(!array_key_exists($format, $this->options['decoders']))
                throw new RestClientException("'${format}' is not a supported ".
                    "format, register a decoder to handle this response.");
            
            $this->decoded_response = call_user_func(
                $this->options['decoders'][$format], $this->response);
        }
        
        return $this->decoded_response;
    }
}


