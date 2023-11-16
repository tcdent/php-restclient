<?php declare(strict_types=1);

namespace RestClient;

if(!function_exists('curl_version'))
    throw new Exception\Fatal("cURL is not available on this system.");

const VERSION = "0.2.0";
define('RestClient\CURL_VERSION', curl_version()['version']);
define('RestClient\USER_AGENT', sprintf(
    "PHP RestClient/%s (%s) PHP/%s curl/%s", 
    VERSION, PHP_OS, PHP_VERSION, CURL_VERSION));
