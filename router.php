<?php
// Simple router for PHP built-in server
$requestUri = $_SERVER['REQUEST_URI'];

// If the request is for /taller/tallerApi, route to tallerApi/index.php
if (strpos($requestUri, '/taller/tallerApi') === 0) {
    // Remove /taller/tallerApi prefix
    $_SERVER['REQUEST_URI'] = substr($requestUri, strlen('/taller/tallerApi'));
    // Change script name
    $_SERVER['SCRIPT_NAME'] = '/taller/tallerApi/index.php';
    $_SERVER['PHP_SELF'] = '/taller/tallerApi/index.php';
    // Include the API index
    require_once 'tallerApi/index.php';
} else {
    // Serve static files
    $file = __DIR__ . $requestUri;
    if (file_exists($file) && !is_dir($file)) {
        // Set content type based on extension
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $contentTypes = [
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
        ];
        if (isset($contentTypes[$ext])) {
            header('Content-Type: ' . $contentTypes[$ext]);
        }
        readfile($file);
    } else {
        // Fallback to index.html
        header('Content-Type: text/html');
        readfile(__DIR__ . '/index.html');
    }
}
?>