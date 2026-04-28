<?php
// =============================================
// LUMINA LIBRARY — BOOKS API
// GET    /books.php           — list all books
// GET    /books.php?id=...    — single book
// POST   /books.php           — add book (admin)
// DELETE /books.php?id=...    — remove book (admin)
// =============================================

require_once 'config.php';
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {
    case 'GET':
        $id ? getBook($id) : getBooks();
        break;
    case 'POST':
        requireAdmin();
        addBook($input);
        break;
    case 'DELETE':
        requireAdmin();
        deleteBook($id);
        break;
    default:
        respond(['error' => 'Method not allowed'], 405);
}

// ── GET ALL BOOKS ──
function getBooks() {
    $db = getDB();
    $db->select_db(DB_NAME);
    $search   = $_GET['search']   ?? '';
    $category = $_GET['category'] ?? '';

    $sql = "SELECT * FROM books WHERE 1=1";
    $params = [];
    $types  = '';

    if ($search) {
        $sql .= " AND (title LIKE ? OR author LIKE ?)";
        $s = "%$search%";
        $params[] = $s; $params[] = $s;
        $types .= 'ss';
    }
    if ($category) {
        $sql .= " AND category = ?";
        $params[] = $category;
        $types .= 's';
    }
    $sql .= " ORDER BY added_at DESC";

    $stmt = $db->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $db->close();
    respond(['success' => true, 'books' => $books]);
}

// ── GET SINGLE BOOK ──
function getBook($id) {
    $db = getDB();
    $db->select_db(DB_NAME);
    $stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    $db->close();
    if (!$book) respond(['error' => 'Book not found'], 404);
    respond(['success' => true, 'book' => $book]);
}

// ── ADD BOOK (Admin only) ──
function addBook($data) {
    $title  = trim($data['title']  ?? '');
    $author = trim($data['author'] ?? '');
    $cat    = trim($data['category']    ?? 'Fiction');
    $color  = trim($data['color']       ?? '#2c3e50');
    $desc   = trim($data['description'] ?? '');
    $link   = trim($data['link']        ?? '#');

    if (!$title || !$author) respond(['error' => 'Title and author required'], 400);

    $db = getDB();
    $db->select_db(DB_NAME);
    $id   = 'b' . uniqid();
    $stmt = $db->prepare("INSERT INTO books (id, title, author, category, color, description, link) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param('sssssss', $id, $title, $author, $cat, $color, $desc, $link);
    $stmt->execute();
    $db->close();
    respond(['success' => true, 'id' => $id, 'message' => 'Book added successfully']);
}

// ── DELETE BOOK (Admin only) ──
function deleteBook($id) {
    if (!$id) respond(['error' => 'Book ID required'], 400);
    $db = getDB();
    $db->select_db(DB_NAME);
    // Remove associated borrows
    $stmt = $db->prepare("DELETE FROM borrows WHERE book_id = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    // Remove book
    $stmt = $db->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $db->close();
    respond(['success' => true, 'message' => 'Book removed']);
}

// ── AUTH HELPERS ──
function requireAdmin() {
    if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        respond(['error' => 'Admin access required'], 403);
    }
}

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
?>
