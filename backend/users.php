<?php
// =============================================
// LUMINA LIBRARY — USERS API (Admin only)
// GET /users.php              — list all users
// PUT /users.php?id=...&action=block|unblock
// =============================================

require_once 'config.php';
require_once 'db.php';

requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {
    case 'GET':
        listUsers();
        break;
    case 'PUT':
        toggleBlock($id, $action);
        break;
    default:
        respond(['error' => 'Method not allowed'], 405);
}

function listUsers() {
    $db = getDB();
    $result = $db->query("
        SELECT u.id, u.name, u.email, u.role, u.blocked, u.joined_at,
               COUNT(CASE WHEN b.returned = 0 THEN 1 END) as active_borrows
        FROM users u
        LEFT JOIN borrows b ON u.id = b.user_id
        WHERE u.role = 'user'
        GROUP BY u.id
        ORDER BY u.joined_at DESC
    ");
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $db->close();
    respond(['success' => true, 'users' => $users]);
}

function toggleBlock($id, $action) {
    if (!$id) respond(['error' => 'User ID required'], 400);
    $blocked = $action === 'block' ? 1 : 0;
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET blocked = ? WHERE id = ? AND role = 'user'");
    $stmt->bind_param('is', $blocked, $id);
    $stmt->execute();
    $db->close();
    respond(['success' => true, 'message' => $action === 'block' ? 'User blocked' : 'User unblocked']);
}

function requireAdmin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
}

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
?>
