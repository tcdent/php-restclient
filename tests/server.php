<?php

require('vendor/autoload.php');

use \RestClient\Log;

/**
 * Test Server
 * This code is only executed by the test server instance. It returns simple 
 * JSON debug information for validating behavior. 
 */

if(php_sapi_name() != 'cli-server'){
    print("This script is intended to be run by the PHP CLI server.\n");
    print("Run with `php -S localhost:8888 tests/server.php`\n");
    die();
}

header("Content-Type: application/json");
$response = json_encode([
    'SERVER' => $_SERVER, 
    'REQUEST' => $_REQUEST, 
    'POST' => $_POST, 
    'GET' => $_GET, 
    'body' => file_get_contents('php://input'), 
    'headers' => getallheaders()
]);
log::debug('tests/server.php', $response);
die($response);
