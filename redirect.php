<?php

// Test server for redirect
header('HTTP/1.1 301');
header('connection: Keep-Alive');
header('content_length: 0');
header('location: http://localhost:8888/server.php');
die;
