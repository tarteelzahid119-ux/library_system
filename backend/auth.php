<?php
// =============================================
// LUMINA LIBRARY — AUTH API
// Endpoints: /backend/auth.php?action=...
// Actions: register, login, logout, me
// =============================================

require_once 'config.php';
require_once 'db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $action ?: ($input['action'] ?? '');

switch ($action) {
    case 'register': handleRegister($input); break;
    case 'login':    handleLogin($input);    break;
    case 'logout':   handleLogout();         break;
    case 'me':       handleMe();             break;
    default:         respond(['error' => 'Unknown action'], 400);
}

// ── REGISTER ──
function handleRegister($data) {
    $name  = trim($data['name']  ?? '');
    $email = strtolower(trim($data['email'] ?? ''));
    $pass  = $data['password'] ?? '';

    if (!$name || !$email || !$pass)       respond(['error' => 'All fields required'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(['error' => 'Invalid email'], 400);
    if (strlen($pass) < 6)                 respond(['error' => 'Password min 6 chars'], 400);

    $db = getDB();
    $db->select_db(DB_NAME);

    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) respond(['error' => 'Email already registered'], 409);

    $id   = 'u' . uniqid();
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (id, name, email, password, role) VALUES (?, ?, ?, ?, 'user')");
    $stmt->bind_param('ssss', $id, $name, $email, $hash);
    $stmt->execute();
    $db->close();

    respond(['success' => true, 'message' => 'Account created successfully']);
}

// ── LOGIN ──
function handleLogin($data) {
    $email = strtolower(trim($data['email'] ?? ''));
    $pass  = $data['password'] ?? '';

    if (!$email || !$pass) respond(['error' => 'Email and password required'], 400);

    $db = getDB();
    $db->select_db(DB_NAME);

    // Auto-check and block overdue users
    checkAndBlockOverdueUsers($db);

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($pass, $user['password'])) {
        respond(['error' => 'Invalid credentials'], 401);
    }
    if ($user['blocked']) {
        respond(['error' => 'Account blocked due to overdue books. Contact admin.'], 403);
    }

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $db->close();

    unset($user['password']);
    respond(['success' => true, 'user' => $user]);
}

// ── LOGOUT ──
function handleLogout() {
    session_destroy();
    respond(['success' => true]);
}

// ── ME ──
function handleMe() {
    if (empty($_SESSION['user_id'])) respond(['error' => 'Not authenticated'], 401);
    $db = getDB();
    $db->select_db(DB_NAME);
    $stmt = $db->prepare("SELECT id, name, email, role, blocked, joined_at FROM users WHERE id = ?");
    $stmt->bind_param('s', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $db->close();
    if (!$user) respond(['error' => 'User not found'], 404);
    respond(['success' => true, 'user' => $user]);
}

// ── BLOCK OVERDUE USERS ──
function checkAndBlockOverdueUsers($db) {
    $graceCutoff = date('Y-m-d H:i:s', strtotime('-' . (BORROW_DAYS + GRACE_DAYS) . ' days'));
    $db->query("UPDATE users SET blocked = 1 WHERE id IN (
        SELECT DISTINCT user_id FROM borrows 
        WHERE returned = 0 AND borrowed_at < '$graceCutoff'
    )");
}

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
?>
