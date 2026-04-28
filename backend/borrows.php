<?php
// =============================================
// LUMINA LIBRARY — BORROWS API
// GET  /borrows.php           — my borrows
// POST /borrows.php           — borrow a book
// PUT  /borrows.php?id=...    — return a book
// GET  /borrows.php?all=1     — all borrows (admin)
// =============================================

require_once 'config.php';
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {
    case 'GET':
        isset($_GET['all']) ? getAllBorrows() : getMyBorrows();
        break;
    case 'POST':
        borrowBook($input);
        break;
    case 'PUT':
        returnBook($id);
        break;
    default:
        respond(['error' => 'Method not allowed'], 405);
}

// ── GET MY BORROWS ──
function getMyBorrows() {
    requireLogin();
    $db = getDB();
    $db->select_db(DB_NAME);
    $uid  = $_SESSION['user_id'];
    $stmt = $db->prepare("
        SELECT br.*, b.title, b.author, b.category, b.color, b.link 
        FROM borrows br 
        JOIN books b ON br.book_id = b.id 
        WHERE br.user_id = ? 
        ORDER BY br.borrowed_at DESC
    ");
    $stmt->bind_param('s', $uid);
    $stmt->execute();
    $borrows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $db->close();

    // Enrich with computed status
    foreach ($borrows as &$borrow) {
        $borrow['status_info'] = computeStatus($borrow);
    }
    respond(['success' => true, 'borrows' => $borrows]);
}

// ── GET ALL BORROWS (Admin) ──
function getAllBorrows() {
    requireAdmin();
    $db = getDB();
    $db->select_db(DB_NAME);
    $result = $db->query("
        SELECT br.*, b.title, b.author, u.name as user_name, u.email as user_email
        FROM borrows br
        JOIN books b  ON br.book_id = b.id
        JOIN users u  ON br.user_id = u.id
        ORDER BY br.borrowed_at DESC
    ");
    $borrows = $result->fetch_all(MYSQLI_ASSOC);
    $db->close();
    respond(['success' => true, 'borrows' => $borrows]);
}

// ── BORROW BOOK ──
function borrowBook($data) {
    requireLogin();
    $uid    = $_SESSION['user_id'];
    $bookId = trim($data['book_id'] ?? '');
    if (!$bookId) respond(['error' => 'Book ID required'], 400);

    $db = getDB();
    $db->select_db(DB_NAME);

    // Check user not blocked
    $stmt = $db->prepare("SELECT blocked FROM users WHERE id = ?");
    $stmt->bind_param('s', $uid);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user['blocked']) respond(['error' => 'Account blocked. Return overdue books or contact admin.'], 403);

    // Check book exists
    $stmt = $db->prepare("SELECT id FROM books WHERE id = ?");
    $stmt->bind_param('s', $bookId);
    $stmt->execute();
    if (!$stmt->get_result()->num_rows) respond(['error' => 'Book not found'], 404);

    // Check not already borrowed
    $stmt = $db->prepare("SELECT id FROM borrows WHERE user_id = ? AND book_id = ? AND returned = 0");
    $stmt->bind_param('ss', $uid, $bookId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) respond(['error' => 'Already borrowed this book'], 409);

    $id   = 'br' . uniqid();
    $stmt = $db->prepare("INSERT INTO borrows (id, user_id, book_id) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $id, $uid, $bookId);
    $stmt->execute();
    $db->close();

    respond(['success' => true, 'borrow_id' => $id, 'message' => 'Book borrowed! Return within 10 days.']);
}

// ── RETURN BOOK ──
function returnBook($borrowId) {
    requireLogin();
    if (!$borrowId) respond(['error' => 'Borrow ID required'], 400);
    $uid = $_SESSION['user_id'];

    $db = getDB();
    $db->select_db(DB_NAME);

    $stmt = $db->prepare("SELECT * FROM borrows WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ss', $borrowId, $uid);
    $stmt->execute();
    $borrow = $stmt->get_result()->fetch_assoc();

    if (!$borrow)          respond(['error' => 'Borrow record not found'], 404);
    if ($borrow['returned']) respond(['error' => 'Book already returned'], 409);

    $status      = computeStatus($borrow);
    $fine        = $status['fine'];
    $now         = date('Y-m-d H:i:s');

    $stmt = $db->prepare("UPDATE borrows SET returned = 1, returned_at = ?, fine = ? WHERE id = ?");
    $stmt->bind_param('sds', $now, $fine, $borrowId);
    $stmt->execute();

    // Check if user can be unblocked
    $stmt = $db->prepare("
        SELECT COUNT(*) as cnt FROM borrows br
        JOIN (SELECT DATEDIFF(NOW(), borrowed_at) as days, id FROM borrows WHERE user_id = ? AND returned = 0) sub ON sub.id = br.id
        WHERE sub.days > ?
    ");
    $totalDays = BORROW_DAYS + GRACE_DAYS;
    $stmt->bind_param('si', $uid, $totalDays);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row['cnt'] == 0) {
        $stmt = $db->prepare("UPDATE users SET blocked = 0 WHERE id = ?");
        $stmt->bind_param('s', $uid);
        $stmt->execute();
    }

    $db->close();
    $msg = $fine > 0 ? "Book returned! Fine: $fine units. Please contact admin to settle." : 'Book returned successfully!';
    respond(['success' => true, 'fine' => $fine, 'message' => $msg]);
}

// ── COMPUTE STATUS ──
function computeStatus($borrow) {
    if ($borrow['returned']) return ['label' => 'Returned', 'class' => 'returned', 'fine' => (float)$borrow['fine']];

    $start       = new DateTime($borrow['borrowed_at']);
    $now         = new DateTime();
    $daysElapsed = $start->diff($now)->days;

    if ($daysElapsed <= BORROW_DAYS) {
        return ['label' => (BORROW_DAYS - $daysElapsed) . ' day(s) left', 'class' => 'ok', 'fine' => 0];
    }
    $overdueDays = $daysElapsed - BORROW_DAYS;
    $fine        = $overdueDays * FINE_PER_DAY;

    if ($overdueDays <= GRACE_DAYS) {
        return ['label' => 'Grace: ' . (GRACE_DAYS - $overdueDays) . ' day(s) left', 'class' => 'grace', 'fine' => $fine];
    }
    return ['label' => 'OVERDUE — Account at risk', 'class' => 'overdue', 'fine' => $fine, 'should_block' => true];
}

// ── AUTH HELPERS ──
function requireLogin() {
    if (empty($_SESSION['user_id'])) respond(['error' => 'Login required'], 401);
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
