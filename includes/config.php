<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'auction_site');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set the base URL for your project
$base_url = '/auction-site/'; // Update this to match your project's root URL

// Add this line after setting $base_url
$asset_url = 'http://yourdomain.com/auction-site/'; // Replace with your actual domain and path
