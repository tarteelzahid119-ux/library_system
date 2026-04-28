# 📚 Lumina Library — Setup Guide

## Project Structure
```
library/
├── frontend/           # All HTML, CSS, JS (open directly in browser)
│   ├── index.html      # Home page
│   ├── books.html      # Browse all books
│   ├── login.html      # Login page
│   ├── register.html   # Register page
│   ├── borrowed.html   # My borrowed books
│   ├── complaint.html  # Submit complaints
│   ├── admin.html      # Admin panel
│   ├── css/
│   │   └── style.css   # Complete stylesheet
│   └── js/
│       └── app.js      # Full frontend logic (localStorage)
└── backend/            # PHP API (optional, needs PHP + MySQL)
    ├── config.php      # DB configuration
    ├── db.php          # Database connection helper
    ├── setup.php       # One-time DB setup
    ├── auth.php        # Register / Login / Logout API
    ├── books.php       # Books CRUD API
    ├── borrows.php     # Borrow / Return API
    ├── complaints.php  # Complaints API
    └── users.php       # User management API (admin)
```

---

## 🚀 Option A: Run Frontend Only (Recommended for Quick Start)

The frontend works **completely standalone** using `localStorage` — no server needed!

1. Open `frontend/index.html` in any modern browser
2. Done! Everything works out of the box.

**Demo Credentials:**
- Admin: `admin@library.com` / `admin123`
- Or register a new user account

---

## 🖥️ Option B: Full Stack (PHP + MySQL)

### Requirements
- PHP 7.4+ (with MySQLi extension)
- MySQL 5.7+ or MariaDB 10+
- Apache or Nginx web server

### Steps

1. **Copy the entire `library/` folder to your web root:**
   ```
   /var/www/html/library/     (Linux/Apache)
   C:\xampp\htdocs\library\   (XAMPP on Windows)
   ```

2. **Update database credentials in `backend/config.php`:**
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'lumina_library');
   ```

3. **Run the database setup (once):**
   Visit `http://localhost/library/backend/setup.php`
   You should see: `{"success":true,"message":"Database setup complete!"}`

4. **Open the frontend:**
   Visit `http://localhost/library/frontend/index.html`

---

## ✨ Features

| Feature | Description |
|---|---|
| 🔐 Login / Register | Secure user authentication |
| 👁️ Browse without login | Anyone can view and read books |
| 📖 Read books | Opens book in new tab/browser |
| 📦 Borrow | Users borrow books (logged in) |
| ↩️ Return | Return with fine calculation |
| ⏰ Fine system | PKR 50/day after 10-day period |
| 🚫 Grace period | 10 extra days before account block |
| 🔒 Auto-block | Account blocked after grace period |
| ➕ Admin: Add book | Admin adds books with cover color |
| 🗑️ Admin: Remove book | Admin deletes books |
| 👥 Admin: Manage users | Block / unblock users manually |
| 📋 Admin: Complaints | View and resolve complaints |
| 📝 Complaints | Any user/guest can submit complaints |

---

## 📋 Fine Policy

```
Day 0–10:    Borrow period — no fine
Day 11–20:   Grace period — fine accumulates (50 units/day)
Day 21+:     Account BLOCKED automatically
             User must contact admin to resolve
```

---

## 🔑 Admin Panel

- URL: `frontend/admin.html`
- Login: `admin@library.com` / `admin123`
- Can: Add books, remove books, manage users, view/resolve complaints

---

## 🌐 Backend API Endpoints

| Endpoint | Method | Description |
|---|---|---|
| `/backend/auth.php?action=register` | POST | Register |
| `/backend/auth.php?action=login` | POST | Login |
| `/backend/auth.php?action=logout` | POST | Logout |
| `/backend/books.php` | GET | List books |
| `/backend/books.php` | POST | Add book (admin) |
| `/backend/books.php?id=...` | DELETE | Remove book (admin) |
| `/backend/borrows.php` | GET | My borrows |
| `/backend/borrows.php` | POST | Borrow book |
| `/backend/borrows.php?id=...` | PUT | Return book |
| `/backend/complaints.php` | POST | Submit complaint |
| `/backend/users.php` | GET | List users (admin) |
| `/backend/users.php?id=...&action=block` | PUT | Block user (admin) |

---

## 🎨 Design

- **Font:** Playfair Display (headings) + DM Sans (body)
- **Palette:** Deep navy + gold accent + cream background
- **Responsive:** Works on mobile, tablet, and desktop
- **Animations:** Smooth page transitions and card hovers

---

*Lumina Library — Built with HTML, CSS, JavaScript, and PHP*
