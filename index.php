<?php
// Route all requests to the public folder
define('APP_ROOT', __DIR__);

// Determine the request path
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script_name = parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH);
$base_path = substr($script_name, 0, strpos($script_name, basename(__FILE__)));

// Remove base path from request
$path = substr($request_uri, strlen($base_path));
$path = trim($path, '/');

// Remove 'public' from path if it's there
if (strpos($path, 'public/') === 0) {
    $path = substr($path, 7);
}

// If empty or just /, serve index.php
if (!$path || $path === '/') {
    $_GET['page'] = 'index';
    require_once __DIR__ . '/public/index.php';
    exit;
}

// Check if file exists in public folder
$file = __DIR__ . '/public/' . $path;

// If it's a direct file request that exists, serve it
if (file_exists($file) && is_file($file)) {
    // For PHP files, include them
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        require_once $file;
    } else {
        // For other files (CSS, JS, images), serve them directly
        // Determine MIME type
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'eot' => 'application/vnd.ms-fontobject'
        ];
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = $mime_types[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        readfile($file);
    }
    exit;
}

// If file doesn't exist, try to serve index.php with the path as a query parameter
// This allows for clean URLs like /book.php, /login.php etc.
$page = trim($path, '/');
if (strpos($page, '/') !== false) {
    // For paths like /admin/caisse, split them
    $parts = explode('/', $page);
    $page = $parts[count($parts) - 1];
}

// Remove .php extension if present
if (substr($page, -4) === '.php') {
    $page = substr($page, 0, -4);
}

// Check if the page exists as a PHP file
$page_file = __DIR__ . '/public/' . $page . '.php';
if (file_exists($page_file)) {
    require_once $page_file;
    exit;
}

// For admin routes like /admin/caisse
if (strpos($path, 'admin/') === 0) {
    $admin_page = str_replace('admin/', '', $path);
    $admin_file = __DIR__ . '/public/admin/' . $admin_page;
    if (substr($admin_file, -4) !== '.php') {
        $admin_file .= '.php';
    }
    if (file_exists($admin_file)) {
        require_once $admin_file;
        exit;
    }
}

// If nothing found, serve the 404
http_response_code(404);
echo "Path not found: {$path}";
exit;
