<?php
// =============================================
// LUMINA LIBRARY — BACKEND CONFIG
// =============================================
// Database configuration (MySQL)
// To use this backend, set up a MySQL database
// and update the credentials below.
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lumina_library');
define('DB_PORT', 3306);

define('BORROW_DAYS', 10);     // Initial borrow period
define('GRACE_DAYS',  10);     // Grace period before blocking
define('FINE_PER_DAY', 50);    // Fine per overdue day (currency units)

define('APP_NAME', 'Lumina Library');
define('APP_URL',  'http://localhost/library/frontend');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CORS headers for API endpoints
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
