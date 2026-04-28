<?php
// =============================================
// LUMINA LIBRARY — COMPLAINTS API
// GET  /complaints.php        — my complaints
// POST /complaints.php        — submit complaint
// GET  /complaints.php?all=1  — all (admin)
// PUT  /complaints.php?id=... — resolve (admin)
// =============================================

require_once 'config.php';
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {
    case 'GET':
        isset($_GET['all']) ? getAllComplaints() : getMyComplaints();
        break;
    case 'POST':
        submitComplaint($input);
        break;
    case 'PUT':
        requireAdmin();
        resolveComplaint($id);
        break;
    default:
        respond(['error' => 'Method not allowed'], 405);
}

function getMyComplaints() {
    $uid = $_SESSION['user_id'] ?? null;
    $email = $_GET['email'] ?? '';
    if (!$uid && !$email) respond(['success' => true, 'complaints' => []]);

    $db = getDB();
    $db->select_db(DB_NAME);

    if ($uid) {
        $stmt = $db->prepare("SELECT * FROM complaints WHERE user_id = ? ORDER BY submitted_at DESC");
        $stmt->bind_param('s', $uid);
    } else {
        $stmt = $db->prepare("SELECT * FROM complaints WHERE email = ? ORDER BY submitted_at DESC");
        $stmt->bind_param('s', $email);
    }
    $stmt->execute();
    $complaints = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $db->close();
    respond(['success' => true, 'complaints' => $complaints]);
}

function getAllComplaints() {
    requireAdmin();
    $db = getDB();
    $db->select_db(DB_NAME);
    $result = $db->query("SELECT * FROM complaints ORDER BY submitted_at DESC");
    $complaints = $result->fetch_all(MYSQLI_ASSOC);
    $db->close();
    respond(['success' => true, 'complaints' => $complaints]);
}

function submitComplaint($data) {
    $name  = trim($data['name']     ?? '');
    $email = trim($data['email']    ?? '');
    $cat   = trim($data['category'] ?? '');
    $msg   = trim($data['message']  ?? '');

    if (!$name || !$email || !$cat || !$msg) respond(['error' => 'All fields required'], 400);

    $db  = getDB();
    $db->select_db(DB_NAME);
    $uid = $_SESSION['user_id'] ?? null;
    $id  = 'c' . uniqid();

    $stmt = $db->prepare("INSERT INTO complaints (id, user_id, name, email, category, message) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('ssssss', $id, $uid, $name, $email, $cat, $msg);
    $stmt->execute();
    $db->close();
    respond(['success' => true, 'id' => $id, 'message' => 'Complaint submitted. We will respond within 24-48 hours.']);
}

function resolveComplaint($id) {
    if (!$id) respond(['error' => 'ID required'], 400);
    $db = getDB();
    $db->select_db(DB_NAME);
    $stmt = $db->prepare("UPDATE complaints SET status = 'resolved' WHERE id = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $db->close();
    respond(['success' => true, 'message' => 'Complaint resolved']);
}

function requireAdmin() {
    if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') respond(['error' => 'Admin required'], 403);
}
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
?>
