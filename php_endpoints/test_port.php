<?php
// Test script to check what port XAMPP is running on
echo "XAMPP Server Information:\n";
echo "Server Name: " . $_SERVER['SERVER_NAME'] . "\n";
echo "Server Port: " . $_SERVER['SERVER_PORT'] . "\n";
echo "HTTP Host: " . $_SERVER['HTTP_HOST'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Full URL: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "\n";
?>








