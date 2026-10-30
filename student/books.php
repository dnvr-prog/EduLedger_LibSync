<?php
session_start();
require_once "../config.php";

// Student access protection
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "student"
) {
    header("Location: ../index.php");
    exit();
}

// Safe HTML output helper
function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

// Get the student's display name
$displayName = $_SESSION["username"] ?? "Student";

$stmt = $conn->prepare(
    "SELECT s.full_name
     FROM users u
     LEFT JOIN students s ON s.student_id = u.student_id
     WHERE u.id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$studentUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!empty($studentUser["full_name"])) {
    $displayName = $studentUser["full_name"];
}

// Search and filter values
$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");
$status = trim($_GET["status"] ?? "");

// Load categories from the database
$categories = [];

$categoryResult = $conn->query(
    "SELECT DISTINCT category
     FROM books
     WHERE category IS NOT NULL AND category != ''
     ORDER BY category ASC"
);

if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row["category"];
    }
}

// Build the books query
$sql = "SELECT book_id, title, author, category, status
        FROM books
        WHERE 1=1";

$params = [];
$types = "";

if ($search !== "") {
    $sql .= " AND (
        book_id LIKE ?
        OR title LIKE ?
        OR author LIKE ?
    )";

    $keyword = "%" . $search . "%";

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= "sss";
}

if ($category !== "" && in_array($category, $categories, true)) {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

if (in_array($status, ["Available", "Borrowed"], true)) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY title ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Library Books - EduLedger: LibSync</title>

    <link rel="stylesheet" href="../css/student-dashboard.css">
    <link rel="stylesheet" href="../css/student-books.css">
</head>

<body>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="sidebar-brand">
            <img src="../assets/uep_logo.png" alt="UEP Logo">

            <div class="brand-text">
                <h2>EduLedger</h2>
                <span>LibSync</span>
            </div>
        </div>

        <nav class="sidebar-navigation">

            <a href="dashboard.php" class="nav-item">
                <span class="nav-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a href="books.php" class="nav-item active" aria-current="page">
                <span class="nav-icon">▣</span>
                <span>Library Books</span>
            </a>

            <a href="borrowed.php" class="nav-item">
                <span class="nav-icon">◷</span>
                <span>My Borrowing History</span>
            </a>

        </nav>

        <div class="sidebar-bottom">
            <a href="../logout.php" class="nav-item logout">
                <span class="nav-icon">↪</span>
                <span>Logout</span>
            </a>
        </div>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOP HEADER -->
        <header class="top-header">

            <div class="university-brand">
                <img src="../assets/uep_logo.png" alt="UEP Logo">

                <div>
                    <h2>UNIVERSITY OF EASTERN PANGASINAN</h2>
                    <p>Digital Library Records System</p>
                </div>
            </div>

            <div class="header-right">
                <div class="admin-header-profile">

                    <div class="admin-avatar">
                        <?= e(strtoupper(substr($displayName, 0, 1))) ?>
                    </div>

                    <div class="admin-info">
                        <strong><?= e($displayName) ?></strong>
                        <span>Student</span>
                    </div>

                </div>
            </div>

        </header>

        <!-- LIBRARY BOOKS CONTENT -->
        <section class="dashboard-content">

            <div class="books-page">

                <!-- PAGE INTRODUCTION -->
                <div class="books-page-heading">
                    <div>
                        <span class="page-eyebrow">STUDENT PORTAL</span>
                        <h1>Library Books</h1>
                        <p>
                            Explore the library collection and find
                            the book you are looking for.
                        </p>
                    </div>
                </div>

                <!-- SEARCH AND FILTERS -->
                <section class="books-filter-card">

                    <form method="GET" action="books.php" class="books-filter-form">

                        <div class="books-search-field">
                            <label for="search">Search books</label>

                            <input
                                type="search"
                                id="search"
                                name="search"
                                placeholder="Search by book ID, title, or author..."
                                value="<?= e($search) ?>"
                            >
                        </div>

                        <div class="books-filter-field">
                            <label for="category">Category</label>

                            <select id="category" name="category">
                                <option value="">All Categories</option>

                                <?php foreach ($categories as $cat): ?>
                                    <option
                                        value="<?= e($cat) ?>"
                                        <?= $category === $cat ? "selected" : "" ?>
                                    >
                                        <?= e($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="books-filter-field">
                            <label for="status">Book Status</label>

                            <select id="status" name="status">
                                <option value="">All Statuses</option>

                                <option
                                    value="Available"
                                    <?= $status === "Available" ? "selected" : "" ?>
                                >
                                    Available
                                </option>

                                <option
                                    value="Borrowed"
                                    <?= $status === "Borrowed" ? "selected" : "" ?>
                                >
                                    Borrowed
                                </option>
                            </select>
                        </div>

                        <div class="books-filter-actions">
                            <button type="submit" class="books-search-button">
                                Search Books
                            </button>

                            <a href="books.php" class="books-reset-button">
                                Reset
                            </a>
                        </div>

                    </form>

                </section>

                <!-- RESULTS HEADER -->
                <div class="books-results-header">
                    <div>
                        <h2>Book Collection</h2>
                        <p>
                            <?= (int)$result->num_rows ?>
                            <?= $result->num_rows === 1 ? "book" : "books" ?> found
                        </p>
                    </div>
                </div>

                <!-- BOOKS TABLE -->
                <section class="books-table-card">

                    <div class="books-table-scroll">

                        <table class="books-table">

                            <thead>
                                <tr>
                                    <th>Book ID</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if ($result->num_rows > 0): ?>

                                    <?php while ($book = $result->fetch_assoc()): ?>
                                        <tr>

                                            <td class="book-id">
                                                <?= e($book["book_id"]) ?>
                                            </td>

                                            <td class="book-title">
                                                <?= e($book["title"]) ?>
                                            </td>

                                            <td>
                                                <?= e($book["author"]) ?>
                                            </td>

                                            <td>
                                                <span class="book-category">
                                                    <?= e($book["category"]) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?php if ($book["status"] === "Available"): ?>

                                                    <span class="status-badge status-available">
                                                        <span class="status-dot"></span>
                                                        Available
                                                    </span>

                                                <?php elseif ($book["status"] === "Borrowed"): ?>

                                                    <span class="status-badge status-borrowed">
                                                        <span class="status-dot"></span>
                                                        Borrowed
                                                    </span>

                                                <?php else: ?>

                                                    <span class="status-badge status-unknown">
                                                        <?= e($book["status"]) ?>
                                                    </span>

                                                <?php endif; ?>
                                            </td>

                                        </tr>
                                    <?php endwhile; ?>

                                <?php else: ?>

                                    <tr>
                                        <td colspan="5" class="books-empty-state">
                                            <div class="empty-icon">⌕</div>
                                            <strong>No books found</strong>
                                            <p>
                                                Try another keyword or change
                                                your selected filters.
                                            </p>
                                            <a href="books.php">View all books</a>
                                        </td>
                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </section>

                <p class="books-page-footer">
                    EduLedger: LibSync · Student Library Portal
                </p>

            </div>

        </section>

    </main>

</div>

</body>
</html>

<?php
$stmt->close();
?>
