<?php

/**
 * Usage:
 * 
 * $api = new RestClient(
 *     'base_url' => "http://api.twitter.com/1/", 
 *     'format' => "json"
 * );
 * $result = $api->get("statuses/public_timeline");
 * if($result->info->http_code < 400)
 *     json_decode($result->response);
 * 
 * Configurable Options:
 *     headers      - An associative array of HTTP headers and values to be 
 *                    included in every request.
 *     curl_options - cURL options to apply to every request. These will
 *                    override any internally generated values.
 *     user_agent   - User agent string.
 *     base_url     - URL to use for the base of each request. 
 *     format       - Format to append to resource. 
 *     username     - Username to use for basic authentication. Requires password.
 *     password     - Password to use for basic authentication. Requires username.
 * 
 * Options can be set upon instantiation, or individually afterword:
 *
 * $api = new RestClient(array(
 *     'format' => "json", 
 *     'user_agent' => "my-application/0.1"
 * ));
 * 
 * -or-
 * 
 * $api = new RestClient;
 * $api->set_option('format', "json");
 * $api->set_option('user_agent', "my-application/0.1");
 */

class RestClient {
    
    public $options;
    public $handle; // cURL resource handle.
    
    // Populated after execution:
    public $response; // Response body.
    public $headers; // Parsed reponse header object.
    public $info; // Response info object.
    public $error; // Response error string.
    
    public function __construct($options=array()){
        $this->options = array_merge(array(
            'headers' => array(), 
            'curl_options' => array(), 
            'user_agent' => "PHP RestClient/0.1", 
            'base_url' => NULL, 
            'format' => NULL, 
            'username' => NULL, 
            'password' => NULL
        ), $options);
    }
    
    public function set_option($key, $value){
        $this->options[$key] = $value;
    }
    
    public function get($url, $parameters=array(), $headers=array()){
        return $this->execute($url, 'GET', $parameters, $headers);
    }
    
    public function post($url, $parameters=array(), $headers=array()){
        return $this->execute($url, 'POST', $parameters, $headers);
    }
    
    public function put($url, $parameters=array(), $headers=array()){
        $parameters['_method'] = "PUT";
        return $this->post($url, $parameters, $headers);
    }
    
    public function delete($url, $parameters=array(), $headers=array()){
        $parameters['_method'] = "DELETE";
        return $this->post($url, $parameters, $headers);
    }
    
    public function format_query($parameters, $primary='=', $secondary='&'){
        $query = "";
        foreach($parameters as $key => $value){
            $pair = array(urlencode($key), urlencode($value));
            $query .= implode($primary, $pair) . $secondary;
        }
        return rtrim($query, $secondary);
    }
    
    public function parse_response($response){
        $headers = array();
        $http_ver = strtok($response, "\n");
        
        while($line = strtok("\n")){
            if(strlen(trim($line)) == 0) break;
            
            list($key, $value) = explode(':', $line, 2);
            $key = trim(strtolower(str_replace('-', '_', $key)));
            $value = trim($value);
            if(empty($headers[$key])){
                $headers[$key] = $value;
            }
            elseif(is_array($headers[$key])){
                $headers[$key][] = $value;
            }
            else {
                $headers[$key] = array($headers[$key], $value);
            }
        }
        
        $this->headers = (object) $headers;
        $this->response = strtok("");
    }
    
    public function execute($url, $method='GET', $parameters=array(), $headers=array()){
        $client = clone $this;
        $client->url = $url;
        $client->handle = curl_init();
        $curlopt = array(
            CURLOPT_HEADER => TRUE, 
            CURLOPT_RETURNTRANSFER => TRUE, 
            CURLOPT_USERAGENT => $client->options['user_agent']
        );
        
        if($client->options['username'] && $client->options['password'])
            $curlopt[CURLOPT_USERPWD] = sprintf("%s:%s", 
                $client->options['username'], $client->options['password']);
        
        if(count($client->options['headers']) || count($headers)){
            $curlopt[CURLOPT_HTTPHEADER] = array();
            $headers = array_merge($client->options['headers'], $headers);
            foreach($headers as $key => $value){
                $curlopt[CURLOPT_HTTPHEADER][] = sprintf("%s:%s", $key, $value);
            }
        }
        
        if($client->options['format'])
            $client->url .= '.'.$client->options['format'];
        
        if(strtoupper($method) == 'POST'){
            $curlopt[CURLOPT_POST] = TRUE;
            $curlopt[CURLOPT_POSTFIELDS] = $client->format_query($parameters);
        }
        elseif(count($parameters)){
            $client->url .= strpos($client->url, '?')? '&' : '?';
            $client->url .= $client->format_query($parameters);
        }
        
        if($client->options['base_url']){
            if($client->url[0] != '/' || substr($client->options['base_url'], -1) != '/')
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
        $client->info = (object) curl_getinfo($client->handle);
        $client->error = curl_error($client->handle);
        
        curl_close($client->handle);
        return $client;
    }
}

?>