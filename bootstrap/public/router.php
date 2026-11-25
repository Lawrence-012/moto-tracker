<?php
/**
 * Router for PHP built-in server
 * 
 * Handles routing for the PHP development server.
 * Routes /vehicles paths to Laravel to avoid directory conflicts.
 */

// Get the request URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = urldecode($uri);

// Remove leading slash for comparison
$path = ltrim($uri, '/');

// Handle /vehicles route explicitly - route to Laravel
if ($path === 'vehicles' || strpos($path, 'vehicles/') === 0) {
    // Only serve actual files from vehicles directory, not directory listings
    $fullPath = __DIR__ . '/' . $path;
    if (file_exists($fullPath) && is_file($fullPath)) {
        return false; // Serve the static file
    }
    // Route to Laravel for /vehicles route handling
    require_once __DIR__ . '/index.php';
    return true;
}

// For all other requests: serve static files if they exist
if ($path !== '' && file_exists(__DIR__ . '/' . $path) && is_file(__DIR__ . '/' . $path)) {
    return false; // Serve the static file
}

// Route everything else to Laravel
require_once __DIR__ . '/index.php';