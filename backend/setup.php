<?php
// =============================================
// LUMINA LIBRARY — DATABASE SETUP
// Run this file ONCE to create tables and seed data
// Visit: http://localhost/library/backend/setup.php
// =============================================

require_once 'config.php';

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
    }
    return $conn;
}

$conn = getDB();

// Create database
$conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db(DB_NAME);

// ── USERS TABLE ──
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id          VARCHAR(50) PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','user') DEFAULT 'user',
    blocked     TINYINT(1) DEFAULT 0,
    joined_at   DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// ── BOOKS TABLE ──
$conn->query("CREATE TABLE IF NOT EXISTS books (
    id          VARCHAR(50) PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    author      VARCHAR(150) NOT NULL,
    category    VARCHAR(80)  NOT NULL,
    color       VARCHAR(20)  DEFAULT '#2c3e50',
    description TEXT,
    link        VARCHAR(500) DEFAULT '#',
    added_at    DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// ── BORROWS TABLE ──
$conn->query("CREATE TABLE IF NOT EXISTS borrows (
    id          VARCHAR(50) PRIMARY KEY,
    user_id     VARCHAR(50) NOT NULL,
    book_id     VARCHAR(50) NOT NULL,
    borrowed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    returned    TINYINT(1) DEFAULT 0,
    returned_at DATETIME NULL,
    fine        DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
)");

// ── COMPLAINTS TABLE ──
$conn->query("CREATE TABLE IF NOT EXISTS complaints (
    id           VARCHAR(50) PRIMARY KEY,
    user_id      VARCHAR(50) NULL,
    name         VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL,
    category     VARCHAR(80)  NOT NULL,
    message      TEXT NOT NULL,
    status       ENUM('open','resolved') DEFAULT 'open',
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// ── SEED ADMIN ──
$adminPass = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("INSERT IGNORE INTO users (id, name, email, password, role) VALUES 
    ('admin', 'Admin', 'admin@library.com', '$adminPass', 'admin')");

// ── SEED BOOKS ──
$books = [
    ['b1','The Great Gatsby','F. Scott Fitzgerald','Fiction','#1a5276','A story of wealth, love, and the American Dream set in the Jazz Age of the 1920s.','https://www.gutenberg.org/files/64317/64317-h/64317-h.htm'],
    ['b2','A Brief History of Time','Stephen Hawking','Science','#1a3a5c','Hawking explores the nature of space and time, black holes, and the origin of the universe.','#'],
    ['b3','1984','George Orwell','Fiction','#2c3e50','A chilling dystopian novel about totalitarianism, surveillance, and the destruction of truth.','https://www.gutenberg.org/ebooks/61'],
    ['b4','Sapiens','Yuval Noah Harari','History','#7d6608','A sweeping narrative of humankind from the Stone Age to the twenty-first century.','#'],
    ['b5','The Republic','Plato','Philosophy','#4a235a','One of the most influential works in philosophy, exploring justice, the ideal state, and the soul.','https://www.gutenberg.org/files/1497/1497-h/1497-h.htm'],
    ['b6','Steve Jobs','Walter Isaacson','Biography','#922b21','The exclusive biography of Apple co-founder Steve Jobs, based on 40 interviews.','#'],
    ['b7','Clean Code','Robert C. Martin','Technology','#1e8449','A handbook of agile software craftsmanship — essential reading for every developer.','#'],
    ['b8','Pride and Prejudice','Jane Austen','Fiction','#784212','A witty exploration of love, class, and marriage in 19th-century England.','https://www.gutenberg.org/files/1342/1342-h/1342-h.htm'],
];

foreach ($books as $b) {
    $id = $conn->real_escape_string($b[0]);
    $title = $conn->real_escape_string($b[1]);
    $author = $conn->real_escape_string($b[2]);
    $cat = $conn->real_escape_string($b[3]);
    $color = $conn->real_escape_string($b[4]);
    $desc = $conn->real_escape_string($b[5]);
    $link = $conn->real_escape_string($b[6]);
    $conn->query("INSERT IGNORE INTO books (id,title,author,category,color,description,link) 
                  VALUES ('$id','$title','$author','$cat','$color','$desc','$link')");
}

$conn->close();
echo json_encode(['success' => true, 'message' => 'Database setup complete! Tables created and seed data inserted.']);
?>
