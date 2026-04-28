/* =============================================
   LUMINA LIBRARY — APP.JS
   Full client-side Library Management System
   Data stored in localStorage (simulates DB)
   ============================================= */

// ── INITIAL DATA SETUP ──────────────────────────
function initData() {
  // Admin account
  if (!getUsers().length) {
    const users = [
      { id: 'admin', name: 'Admin', email: 'admin@library.com', password: 'admin123', role: 'admin', blocked: false, joinedAt: new Date().toISOString() }
    ];
    localStorage.setItem('ll_users', JSON.stringify(users));
  }

  // Default books
  if (!getBooks().length) {
    const books = [
      { id: 'b1', title: 'The Great Gatsby', author: 'F. Scott Fitzgerald', category: 'Fiction', color: '#1a5276', desc: 'A story of wealth, love, and the American Dream set in the Jazz Age of the 1920s.', link: 'https://www.gutenberg.org/files/64317/64317-h/64317-h.htm', addedAt: new Date().toISOString() },
      { id: 'b2', title: 'A Brief History of Time', author: 'Stephen Hawking', category: 'Science', color: '#1a3a5c', desc: 'Hawking explores the nature of space and time, black holes, and the origin of the universe.', link: 'https://www.ucc.ie/archive/hdsp/A_Brief_History_of_Time_-_Stephen_Hawking.pdf', addedAt: new Date().toISOString() },
      { id: 'b3', title: '1984', author: 'George Orwell', category: 'Fiction', color: '#2c3e50', desc: 'A chilling dystopian novel about totalitarianism, surveillance, and the destruction of truth.', link: 'https://www.gutenberg.org/ebooks/61', addedAt: new Date().toISOString() },
      { id: 'b4', title: 'Sapiens', author: 'Yuval Noah Harari', category: 'History', color: '#7d6608', desc: 'A sweeping narrative of humankind from the Stone Age to the twenty-first century.', link: '#', addedAt: new Date().toISOString() },
      { id: 'b5', title: 'The Republic', author: 'Plato', category: 'Philosophy', color: '#4a235a', desc: 'One of the most influential works in philosophy, exploring justice, the ideal state, and the soul.', link: 'https://www.gutenberg.org/files/1497/1497-h/1497-h.htm', addedAt: new Date().toISOString() },
      { id: 'b6', title: 'Steve Jobs', author: 'Walter Isaacson', category: 'Biography', color: '#922b21', desc: 'The exclusive biography of Apple co-founder Steve Jobs, based on 40 interviews.', link: '#', addedAt: new Date().toISOString() },
      { id: 'b7', title: 'Clean Code', author: 'Robert C. Martin', category: 'Technology', color: '#1e8449', desc: 'A handbook of agile software craftsmanship — essential reading for every developer.', link: '#', addedAt: new Date().toISOString() },
      { id: 'b8', title: 'Pride and Prejudice', author: 'Jane Austen', category: 'Fiction', color: '#784212', desc: 'A witty exploration of love, class, and marriage in 19th-century England.', link: 'https://www.gutenberg.org/files/1342/1342-h/1342-h.htm', addedAt: new Date().toISOString() },
      { id: 'b9', title: 'The Selfish Gene', author: 'Richard Dawkins', category: 'Science', color: '#1a5276', desc: 'Dawkins presents a gene-centric view of evolution that changed how we understand life.', link: '#', addedAt: new Date().toISOString() },
      { id: 'b10', title: 'Meditations', author: 'Marcus Aurelius', category: 'Philosophy', color: '#4a235a', desc: 'Personal writings of the Roman emperor — a guide to Stoic philosophy and inner peace.', link: 'https://www.gutenberg.org/files/55317/55317-h/55317-h.htm', addedAt: new Date().toISOString() },
      { id: 'b11', title: 'Guns, Germs and Steel', author: 'Jared Diamond', category: 'History', color: '#6e2f1a', desc: 'Why did some civilizations conquer others? An epic account of the fates of human societies.', link: '#', addedAt: new Date().toISOString() },
      { id: 'b12', title: 'Elon Musk', author: 'Walter Isaacson', category: 'Biography', color: '#17202a', desc: 'The story of the man building the future — Tesla, SpaceX, and beyond.', link: '#', addedAt: new Date().toISOString() },
    ];
    localStorage.setItem('ll_books', JSON.stringify(books));
  }

  if (!getBorrows().length) localStorage.setItem('ll_borrows', JSON.stringify([]));
  if (!getComplaints().length) localStorage.setItem('ll_complaints', JSON.stringify([]));
}

// ── DATA HELPERS ──────────────────────────────────
function getUsers()     { return JSON.parse(localStorage.getItem('ll_users') || '[]'); }
function getBooks()     { return JSON.parse(localStorage.getItem('ll_books') || '[]'); }
function getBorrows()   { return JSON.parse(localStorage.getItem('ll_borrows') || '[]'); }
function getComplaints(){ return JSON.parse(localStorage.getItem('ll_complaints') || '[]'); }
function saveUsers(d)   { localStorage.setItem('ll_users', JSON.stringify(d)); }
function saveBooks(d)   { localStorage.setItem('ll_books', JSON.stringify(d)); }
function saveBorrows(d) { localStorage.setItem('ll_borrows', JSON.stringify(d)); }
function saveComplaints(d){ localStorage.setItem('ll_complaints', JSON.stringify(d)); }

function currentUser() {
  const s = localStorage.getItem('ll_session');
  if (!s) return null;
  const session = JSON.parse(s);
  return getUsers().find(u => u.id === session.userId) || null;
}

// ── FINE / STATUS LOGIC ──────────────────────────
const BORROW_DAYS = 10;
const GRACE_DAYS  = 10;
const FINE_PER_DAY = 50; // currency units

function getBorrowStatus(borrow) {
  if (borrow.returned) return { label: 'Returned', class: 'returned', fine: 0, icon: 'fa-check-circle' };
  const now   = new Date();
  const start = new Date(borrow.borrowedAt);
  const daysElapsed = Math.floor((now - start) / (1000 * 60 * 60 * 24));

  if (daysElapsed <= BORROW_DAYS) {
    const left = BORROW_DAYS - daysElapsed;
    return { label: `${left} day(s) left`, class: 'ok', fine: 0, icon: 'fa-clock', daysElapsed };
  }
  const overdueDays = daysElapsed - BORROW_DAYS;
  if (overdueDays <= GRACE_DAYS) {
    const fine = overdueDays * FINE_PER_DAY;
    return { label: `Grace period: ${GRACE_DAYS - overdueDays} day(s) left`, class: 'grace', fine, overdueDays, icon: 'fa-exclamation-triangle', daysElapsed };
  }
  // Critical — block user
  const fine = overdueDays * FINE_PER_DAY;
  return { label: 'OVERDUE — Account at risk', class: 'overdue', fine, overdueDays, shouldBlock: true, icon: 'fa-ban', daysElapsed };
}

function checkAndBlockUsers() {
  const borrows = getBorrows();
  const users   = getUsers();
  let changed   = false;

  borrows.forEach(borrow => {
    if (borrow.returned) return;
    const status = getBorrowStatus(borrow);
    if (status.shouldBlock) {
      const idx = users.findIndex(u => u.id === borrow.userId);
      if (idx !== -1 && !users[idx].blocked) {
        users[idx].blocked = true;
        changed = true;
      }
    }
  });

  if (changed) saveUsers(users);
}

// ── AUTH ─────────────────────────────────────────
function register() {
  const name  = document.getElementById('regName').value.trim();
  const email = document.getElementById('regEmail').value.trim().toLowerCase();
  const pass  = document.getElementById('regPassword').value;
  const msg   = document.getElementById('authMsg');

  if (!name || !email || !pass) return showMsg(msg, 'Please fill in all fields.', 'error');
  if (pass.length < 6) return showMsg(msg, 'Password must be at least 6 characters.', 'error');

  const users = getUsers();
  if (users.find(u => u.email === email)) return showMsg(msg, 'An account with this email already exists.', 'error');

  const user = { id: 'u' + Date.now(), name, email, password: pass, role: 'user', blocked: false, joinedAt: new Date().toISOString() };
  users.push(user);
  saveUsers(users);
  showMsg(msg, 'Account created! Redirecting to login...', 'success');
  setTimeout(() => window.location.href = 'login.html', 1500);
}

function login() {
  const email = document.getElementById('loginEmail').value.trim().toLowerCase();
  const pass  = document.getElementById('loginPassword').value;
  const msg   = document.getElementById('authMsg');

  checkAndBlockUsers();
  const users = getUsers();
  const user  = users.find(u => u.email === email && u.password === pass);

  if (!user) return showMsg(msg, 'Invalid email or password.', 'error');
  if (user.blocked) return showMsg(msg, 'Your account is blocked due to overdue books. Please contact the admin.', 'error');

  localStorage.setItem('ll_session', JSON.stringify({ userId: user.id }));
  showMsg(msg, `Welcome back, ${user.name}! Redirecting...`, 'success');
  setTimeout(() => window.location.href = user.role === 'admin' ? 'admin.html' : 'index.html', 1200);
}

function logout() {
  localStorage.removeItem('ll_session');
  window.location.href = 'index.html';
}

function requireLogin() {
  if (!currentUser()) {
    window.location.href = 'login.html';
  }
}

function requireAdmin() {
  const u = currentUser();
  if (!u || u.role !== 'admin') {
    window.location.href = 'login.html';
  }
}

// ── NAVBAR UPDATE ────────────────────────────────
function updateNav() {
  checkAndBlockUsers();
  const u = currentUser();
  const navAuth  = document.getElementById('navAuth');
  const navUser  = document.getElementById('navUser');
  const nameEl   = document.getElementById('userNameDisplay');
  const myBooks  = document.getElementById('myBooksLink');
  const adminNav = document.getElementById('adminNavItem');

  if (u) {
    if (navAuth) navAuth.style.display = 'none';
    if (navUser) navUser.style.display = 'flex';
    if (nameEl)  nameEl.textContent = u.name;
    if (myBooks) myBooks.style.display = 'block';
    if (adminNav && u.role === 'admin') adminNav.style.display = 'block';
  } else {
    if (navAuth) navAuth.style.display = 'flex';
    if (navUser) navUser.style.display = 'none';
  }

  // Hamburger
  const ham = document.getElementById('hamburger');
  const links = document.getElementById('navLinks');
  if (ham && links) {
    ham.onclick = () => links.classList.toggle('open');
  }

  // Scroll shadow
  window.addEventListener('scroll', () => {
    const nb = document.getElementById('navbar');
    if (nb) nb.classList.toggle('scrolled', window.scrollY > 10);
  });
}

// ── BOOK RENDERING ───────────────────────────────
function bookIcon(cat) {
  const icons = { Fiction: 'fa-book', Science: 'fa-flask', History: 'fa-landmark', Philosophy: 'fa-brain', Technology: 'fa-laptop-code', Biography: 'fa-user-tie' };
  return icons[cat] || 'fa-book';
}

function createBookCard(book, onclick) {
  const div = document.createElement('div');
  div.className = 'book-card';
  div.innerHTML = `
    <div class="book-cover" style="background:${book.color}">
      <div class="book-spine"></div>
      <i class="fas ${bookIcon(book.category)}"></i>
    </div>
    <div class="book-info">
      <div class="book-title">${book.title}</div>
      <div class="book-author">${book.author}</div>
      <span class="book-cat">${book.category}</span>
    </div>`;
  div.onclick = onclick;
  return div;
}

function renderFeaturedBooks() {
  const container = document.getElementById('featuredBooks');
  if (!container) return;
  const books = getBooks().slice(0, 6);
  container.innerHTML = '';
  books.forEach((book, i) => {
    const card = createBookCard(book, () => openBookModal(book.id));
    card.style.animationDelay = (i * 0.08) + 's';
    container.appendChild(card);
  });
}

let allBooksData = [];

function renderAllBooks() {
  allBooksData = getBooks();
  displayBooks(allBooksData);
}

function displayBooks(books) {
  const container = document.getElementById('allBooksGrid');
  if (!container) return;
  container.innerHTML = '';
  if (!books.length) {
    container.innerHTML = '<div class="empty-state"><i class="fas fa-search"></i><p>No books found.</p></div>';
    return;
  }
  books.forEach((book, i) => {
    const card = createBookCard(book, () => openBookModal(book.id));
    card.style.animationDelay = (i * 0.06) + 's';
    container.appendChild(card);
  });
}

function filterBooks() {
  const q    = (document.getElementById('searchInput')?.value || '').toLowerCase();
  const cat  = document.getElementById('categoryFilter')?.value || '';
  const filtered = getBooks().filter(b => {
    const matchQ   = b.title.toLowerCase().includes(q) || b.author.toLowerCase().includes(q);
    const matchCat = !cat || b.category === cat;
    return matchQ && matchCat;
  });
  displayBooks(filtered);
}

// ── BOOK MODAL ───────────────────────────────────
let currentModalBookId = null;

function openBookModal(bookId) {
  const book = getBooks().find(b => b.id === bookId);
  if (!book) return;
  currentModalBookId = bookId;

  document.getElementById('modalTitle').textContent = book.title;
  document.getElementById('modalAuthor').textContent = 'by ' + book.author;
  document.getElementById('modalCat').textContent = book.category;
  document.getElementById('modalDesc').textContent = book.desc;
  document.getElementById('modalCover').innerHTML = `<i class="fas ${bookIcon(book.category)}"></i>`;
  document.getElementById('modalCover').style.background = book.color;
  document.getElementById('readBtn').href = book.link || '#';
  document.getElementById('readBtn').target = book.link && book.link !== '#' ? '_blank' : '_self';

  const u = currentUser();
  const statusEl = document.getElementById('modalStatus');
  const borrowBtn = document.getElementById('borrowBtn');

  if (!u) {
    statusEl.textContent = 'Login to borrow this book.';
    statusEl.className = 'modal-status';
    borrowBtn.style.display = 'inline-flex';
    borrowBtn.disabled = false;
    borrowBtn.onclick = () => { closeModal(); window.location.href = 'login.html'; };
  } else if (u.blocked) {
    statusEl.textContent = 'Your account is blocked. Return overdue books or contact admin.';
    statusEl.className = 'modal-status error';
    borrowBtn.style.display = 'none';
  } else {
    const activeBorrow = getBorrows().find(b => b.bookId === bookId && b.userId === u.id && !b.returned);
    if (activeBorrow) {
      const st = getBorrowStatus(activeBorrow);
      statusEl.textContent = `You have borrowed this book. Status: ${st.label}`;
      statusEl.className = 'modal-status warning';
      borrowBtn.style.display = 'none';
    } else {
      statusEl.textContent = 'Available to borrow for 10 days.';
      statusEl.className = 'modal-status';
      borrowBtn.style.display = 'inline-flex';
      borrowBtn.disabled = false;
      borrowBtn.onclick = () => borrowBook();
    }
  }

  document.getElementById('bookModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('bookModal').style.display = 'none';
  document.body.style.overflow = '';
  currentModalBookId = null;
}

// Close on backdrop click
document.addEventListener('click', e => {
  const modal = document.getElementById('bookModal');
  if (modal && e.target === modal) closeModal();
});

// ── BORROW ───────────────────────────────────────
function borrowBook() {
  const u = currentUser();
  if (!u) { window.location.href = 'login.html'; return; }
  if (u.blocked) { showToast('Your account is blocked.', 'error'); return; }

  const bookId = currentModalBookId;
  if (!bookId) return;

  const borrows = getBorrows();
  const already = borrows.find(b => b.bookId === bookId && b.userId === u.id && !b.returned);
  if (already) { showToast('You already borrowed this book.', 'error'); return; }

  borrows.push({
    id: 'br' + Date.now(),
    userId: u.id,
    bookId,
    borrowedAt: new Date().toISOString(),
    returned: false,
    returnedAt: null,
    fine: 0
  });
  saveBorrows(borrows);
  closeModal();
  showToast('Book borrowed successfully! You have 10 days to return it.', 'success');
}

// ── RETURN ───────────────────────────────────────
function returnBook(borrowId) {
  const borrows = getBorrows();
  const idx = borrows.findIndex(b => b.id === borrowId);
  if (idx === -1) return;

  const status = getBorrowStatus(borrows[idx]);
  borrows[idx].returned   = true;
  borrows[idx].returnedAt = new Date().toISOString();
  borrows[idx].fine       = status.fine;
  saveBorrows(borrows);

  // Unblock user if no more critical borrows
  const u = currentUser();
  if (u) {
    const remaining = getBorrows().filter(b => b.userId === u.id && !b.returned);
    const stillCritical = remaining.some(b => getBorrowStatus(b).shouldBlock);
    if (!stillCritical && u.blocked) {
      const users = getUsers();
      const ui = users.findIndex(x => x.id === u.id);
      if (ui !== -1) { users[ui].blocked = false; saveUsers(users); }
    }
  }

  showToast(status.fine > 0 ? `Book returned! Fine: ${status.fine} units. Contact admin.` : 'Book returned successfully!', 'success');
  renderBorrowedBooks();
}

// ── MY BORROWED BOOKS ─────────────────────────────
function renderBorrowedBooks() {
  const u = currentUser();
  if (!u) return;

  checkAndBlockUsers();
  const freshUser = getUsers().find(x => x.id === u.id);

  const blockedAlert = document.getElementById('blockedAlert');
  if (blockedAlert) blockedAlert.style.display = freshUser?.blocked ? 'flex' : 'none';

  const container = document.getElementById('borrowedList');
  if (!container) return;

  const myBorrows = getBorrows().filter(b => b.userId === u.id).reverse();
  if (!myBorrows.length) {
    container.innerHTML = '<div class="empty-state"><i class="fas fa-book-open"></i><p>You haven\'t borrowed any books yet. <a href="books.html" style="color:var(--accent)">Browse collection</a></p></div>';
    return;
  }

  container.innerHTML = '';
  myBorrows.forEach(borrow => {
    const book   = getBooks().find(b => b.id === borrow.bookId);
    if (!book) return;
    const status = getBorrowStatus(borrow);

    const card = document.createElement('div');
    card.className = `borrowed-card ${status.class === 'grace' ? 'overdue' : status.class === 'overdue' ? 'critical' : ''}`;

    const dueDate = new Date(new Date(borrow.borrowedAt).getTime() + BORROW_DAYS * 86400000);
    const returnedDate = borrow.returnedAt ? new Date(borrow.returnedAt).toLocaleDateString() : '—';

    card.innerHTML = `
      <div class="bc-cover" style="background:${book.color}">
        <i class="fas ${bookIcon(book.category)}"></i>
      </div>
      <div class="bc-info">
        <h3>${book.title}</h3>
        <div class="bc-author">by ${book.author}</div>
        <div class="bc-dates">
          <div class="bc-date-item"><strong>${new Date(borrow.borrowedAt).toLocaleDateString()}</strong><span>Borrowed</span></div>
          <div class="bc-date-item"><strong>${dueDate.toLocaleDateString()}</strong><span>Due Date</span></div>
          ${borrow.returned ? `<div class="bc-date-item"><strong>${returnedDate}</strong><span>Returned</span></div>` : ''}
        </div>
        <span class="status-badge ${status.class}"><i class="fas ${status.icon}"></i>${status.label}</span>
        ${status.fine > 0 ? `<div class="fine-notice"><i class="fas fa-coins"></i> Fine: ${status.fine} units${borrow.returned ? ' (paid on return)' : ' — Please return immediately'}</div>` : ''}
        <div class="bc-actions">
          ${!borrow.returned ? `<button class="btn-fill" onclick="returnBook('${borrow.id}')"><i class="fas fa-undo"></i> Return Book</button>` : ''}
          <a href="${book.link}" target="_blank" class="btn-outline"><i class="fas fa-book-open"></i> Read</a>
        </div>
      </div>`;
    container.appendChild(card);
  });
}

// ── COMPLAINTS ───────────────────────────────────
function prefillComplaintForm() {
  const u = currentUser();
  if (!u) return;
  const nameEl  = document.getElementById('cName');
  const emailEl = document.getElementById('cEmail');
  if (nameEl)  nameEl.value = u.name;
  if (emailEl) emailEl.value = u.email;
}

function submitComplaint() {
  const name  = document.getElementById('cName').value.trim();
  const email = document.getElementById('cEmail').value.trim();
  const cat   = document.getElementById('cCategory').value;
  const msg   = document.getElementById('cMessage').value.trim();
  const msgEl = document.getElementById('complaintMsg');

  if (!name || !email || !cat || !msg) return showMsg(msgEl, 'Please fill in all fields.', 'error');

  const u = currentUser();
  const complaints = getComplaints();
  complaints.push({
    id: 'c' + Date.now(),
    userId: u ? u.id : null,
    name, email,
    category: cat,
    message: msg,
    status: 'open',
    submittedAt: new Date().toISOString()
  });
  saveComplaints(complaints);
  showMsg(msgEl, 'Your complaint has been submitted. We will respond within 24-48 hours.', 'success');
  document.getElementById('cCategory').value = '';
  document.getElementById('cMessage').value = '';
  renderMyComplaints();
}

function renderMyComplaints() {
  const u = currentUser();
  const container = document.getElementById('complaintsList');
  if (!container) return;

  const mine = u ? getComplaints().filter(c => c.userId === u.id) : getComplaints().filter(c => c.email === document.getElementById('cEmail')?.value);
  if (!mine.length) {
    container.innerHTML = '<p style="font-size:0.8rem;color:var(--muted)">No complaints submitted yet.</p>';
    return;
  }
  container.innerHTML = mine.reverse().map(c => `
    <div class="complaint-item">
      <div class="ci-cat">${c.category}</div>
      <div class="ci-msg">${c.message.substring(0, 80)}${c.message.length > 80 ? '…' : ''}</div>
      <div class="ci-date">${new Date(c.submittedAt).toLocaleDateString()}</div>
    </div>`).join('');
}

// ── ADMIN FUNCTIONS ──────────────────────────────
function loadAdminStats() {
  const books      = getBooks();
  const users      = getUsers().filter(u => u.role !== 'admin');
  const borrows    = getBorrows().filter(b => !b.returned);
  const complaints = getComplaints();
  const el = id => document.getElementById(id);
  if (el('totalBooks'))      el('totalBooks').textContent = books.length;
  if (el('totalUsers'))      el('totalUsers').textContent = users.length;
  if (el('totalBorrowed'))   el('totalBorrowed').textContent = borrows.length;
  if (el('totalComplaints')) el('totalComplaints').textContent = complaints.length;
}

function adminAddBook() {
  const title  = document.getElementById('bookTitle').value.trim();
  const author = document.getElementById('bookAuthor').value.trim();
  const cat    = document.getElementById('bookCategory').value;
  const color  = document.getElementById('bookColor').value.trim() || '#2c3e50';
  const desc   = document.getElementById('bookDesc').value.trim();
  const link   = document.getElementById('bookLink').value.trim() || '#';
  const msgEl  = document.getElementById('addBookMsg');

  if (!title || !author || !desc) return showMsg(msgEl, 'Please fill in Title, Author, and Description.', 'error');

  const books = getBooks();
  books.push({ id: 'b' + Date.now(), title, author, category: cat, color, desc, link, addedAt: new Date().toISOString() });
  saveBooks(books);
  showMsg(msgEl, `"${title}" added to the library!`, 'success');
  ['bookTitle','bookAuthor','bookDesc','bookLink'].forEach(id => document.getElementById(id).value = '');
  loadAdminStats();
  showToast('Book added!', 'success');
}

function adminDeleteBook(bookId) {
  if (!confirm('Delete this book permanently?')) return;
  saveBooks(getBooks().filter(b => b.id !== bookId));
  saveBorrows(getBorrows().filter(b => b.bookId !== bookId));
  renderAdminBooks();
  loadAdminStats();
  showToast('Book removed.', 'success');
}

function renderAdminBooks() {
  const container = document.getElementById('adminBooksList');
  if (!container) return;
  const books = getBooks();
  if (!books.length) { container.innerHTML = '<p style="color:var(--muted)">No books yet.</p>'; return; }
  container.innerHTML = books.map(b => `
    <div class="admin-book-row">
      <div class="admin-book-cover" style="background:${b.color}"><i class="fas ${bookIcon(b.category)}"></i></div>
      <div class="abr-info">
        <strong>${b.title}</strong>
        <span>${b.author} · ${b.category}</span>
      </div>
      <span class="book-cat">${b.category}</span>
      <button class="btn-danger" onclick="adminDeleteBook('${b.id}')"><i class="fas fa-trash"></i> Remove</button>
    </div>`).join('');
}

function adminToggleBlock(userId) {
  const users = getUsers();
  const idx   = users.findIndex(u => u.id === userId);
  if (idx === -1) return;
  users[idx].blocked = !users[idx].blocked;
  saveUsers(users);
  renderAdminUsers();
  showToast(users[idx].blocked ? 'User blocked.' : 'User unblocked.', 'success');
}

function renderAdminUsers() {
  const container = document.getElementById('adminUsersList');
  if (!container) return;
  const users = getUsers().filter(u => u.role !== 'admin');
  if (!users.length) { container.innerHTML = '<p style="color:var(--muted)">No registered users yet.</p>'; return; }

  container.innerHTML = users.map(u => {
    const myBorrows = getBorrows().filter(b => b.userId === u.id && !b.returned).length;
    return `
    <div class="admin-user-row">
      <i class="fas fa-user-circle" style="font-size:1.8rem;color:var(--muted)"></i>
      <div class="abr-info">
        <strong>${u.name}</strong>
        <span>${u.email} · Joined ${new Date(u.joinedAt).toLocaleDateString()} · ${myBorrows} active borrow(s)</span>
      </div>
      <span class="user-status-badge ${u.blocked ? 'blocked' : 'active'}">${u.blocked ? 'Blocked' : 'Active'}</span>
      <button class="${u.blocked ? 'btn-success' : 'btn-danger'}" onclick="adminToggleBlock('${u.id}')">
        <i class="fas ${u.blocked ? 'fa-unlock' : 'fa-ban'}"></i> ${u.blocked ? 'Unblock' : 'Block'}
      </button>
    </div>`;
  }).join('');
}

function renderAdminComplaints() {
  const container = document.getElementById('adminComplaintsList');
  if (!container) return;
  const complaints = getComplaints().reverse();
  if (!complaints.length) { container.innerHTML = '<p style="color:var(--muted)">No complaints yet.</p>'; return; }
  container.innerHTML = complaints.map(c => `
    <div class="admin-complaint-row">
      <div class="abr-info">
        <strong>${c.name} — <em style="color:var(--accent)">${c.category}</em></strong>
        <span>${c.email} · ${new Date(c.submittedAt).toLocaleDateString()}</span>
        <span style="margin-top:6px;display:block">${c.message}</span>
      </div>
      <span class="status-badge ${c.status === 'open' ? 'grace' : 'ok'}">${c.status}</span>
      ${c.status === 'open' ? `<button class="btn-success" onclick="resolveComplaint('${c.id}')"><i class="fas fa-check"></i> Resolve</button>` : ''}
    </div>`).join('');
}

function resolveComplaint(cid) {
  const complaints = getComplaints();
  const idx = complaints.findIndex(c => c.id === cid);
  if (idx !== -1) { complaints[idx].status = 'resolved'; saveComplaints(complaints); }
  renderAdminComplaints();
  loadAdminStats();
  showToast('Complaint marked as resolved.', 'success');
}

// ── UTILITIES ────────────────────────────────────
function showMsg(el, text, type) {
  if (!el) return;
  el.textContent = text;
  el.className = `auth-msg ${type}`;
  el.style.display = 'block';
}

function showToast(msg, type = 'success') {
  const existing = document.querySelector('.toast');
  if (existing) existing.remove();
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>${msg}`;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3500);
}

// ── INIT ─────────────────────────────────────────
initData();
