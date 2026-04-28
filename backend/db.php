<?php
// =============================================
// LUMINA LIBRARY — DB CONNECTION HELPER
// =============================================

function getDB() {
    static $conn = null;
    if ($conn && $conn->ping()) return $conn;

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
        exit;
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}
?>
