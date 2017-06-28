<?php

// Test Server
// This code is only executed by the test server instance. It returns simple 
// JSON debug information for validating behavior. 
//var_export($_POST);
header("Content-Type: application/json");
die(json_encode(array(
    'SERVER' => $_SERVER,
    'REQUEST' => $_REQUEST,
    'POST' => $_POST,
    'GET' => $_GET,
    'body' => file_get_contents('php://input'),
    'headers' => getallheaders()
)));
