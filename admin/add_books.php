<?php
session_start();
require_once "../config.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: ../index.php");
    exit();
}

$message = "";
$messageType = "";

$book_id = "";
$title = "";
$author = "";
$category = "";

function e($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $book_id = trim($_POST["book_id"] ?? "");
    $title = trim($_POST["title"] ?? "");
    $author = trim($_POST["author"] ?? "");
    $category = trim($_POST["category"] ?? "");

    if (
        $book_id === "" ||
        $title === "" ||
        $author === "" ||
        $category === ""
    ) {
        $message = "Please complete all required fields.";
        $messageType = "error";
    } else {
        $sql = $conn->prepare(
            "INSERT INTO books (book_id, title, author, category)
             VALUES (?, ?, ?, ?)"
        );

        $sql->bind_param(
            "ssss",
            $book_id,
            $title,
            $author,
            $category
        );

        if ($sql->execute()) {
            // Record activity after successful insertion
            $user_id = (int) $_SESSION["user_id"];
            $action = "Added book " . $book_id . " - " . $title;

            $log_sql = $conn->prepare(
                "INSERT INTO activity_logs (user_id, action)
                 VALUES (?, ?)"
            );

            $log_sql->bind_param("is", $user_id, $action);
            $log_sql->execute();
            $log_sql->close();

            $message = "Book added successfully!";
            $messageType = "success";

            // Clear the form after successful saving
            $book_id = "";
            $title = "";
            $author = "";
            $category = "";
        } else {
            if ($sql->errno === 1062) {
                $message = "This Book ID already exists. Please use a different ID.";
            } else {
                $message = "Unable to add the book. Please check the details and try again.";
            }

            $messageType = "error";
        }

        $sql->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>EduLedger: LibSync - Add Book</title>

    <link rel="stylesheet"
          href="../css/admin-dashboard.css">

    <link rel="stylesheet"
          href="../css/admin-add_books.css">
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

            <a href="students.php" class="nav-item">
                <span class="nav-icon">♟</span>
                <span>Student Management</span>
            </a>

            <a href="books.php" class="nav-item active">
                <span class="nav-icon">▣</span>
                <span>Book Management</span>
            </a>

            <a href="borrow.php" class="nav-item">
                <span class="nav-icon">↔</span>
                <span>Borrowing Records</span>
            </a>

            <a href="return.php" class="nav-item">
                <span class="nav-icon">↩</span>
                <span>Return Records</span>
            </a>

            <a href="search.php" class="nav-item">
                <span class="nav-icon">⌕</span>
                <span>Search & View</span>
            </a>

            <a href="overdue.php" class="nav-item">
                <span class="nav-icon">◷</span>
                <span>Overdue Monitoring</span>
            </a>

            <a href="reports.php" class="nav-item">
                <span class="nav-icon">▥</span>
                <span>Reports & Analytics</span>
            </a>

            <a href="logs.php" class="nav-item">
                <span class="nav-icon">☷</span>
                <span>Activity Logs</span>
            </a>

        </nav>

        <div class="sidebar-bottom">
            <a href="../logout.php" class="nav-item">
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

                    <div class="admin-avatar">👤</div>

                    <div class="admin-info">
                        <strong><?= e($_SESSION["username"] ?? "Admin") ?></strong>
                        <span>Administrator</span>
                    </div>

                    <span class="profile-arrow">▾</span>
                </div>
            </div>

        </header>

        <!-- PAGE CONTENT -->
        <section class="page-content add-book-page">

            <!-- PAGE HEADING -->
            <div class="add-book-heading">

                <div class="add-book-title">
                    <a href="books.php" class="back-link">
                        ← Book Management
                    </a>

                    <div class="heading-title-row">
                        <div class="heading-icon">📚</div>

                        <div>
                            <h1>Add New Book</h1>
                            <p>
                                Add a new book to the library collection.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="heading-label">
                    ADMINISTRATOR
                </div>

            </div>

            <!-- FEEDBACK MESSAGE -->
            <?php if ($message !== ""): ?>
                <div class="form-alert <?= e($messageType) ?>"
                     role="status">
                    <span class="alert-icon">
                        <?= $messageType === "success" ? "✓" : "!" ?>
                    </span>

                    <span><?= e($message) ?></span>
                </div>
            <?php endif; ?>

            <!-- FORM CARD -->
            <div class="add-book-layout">

                <section class="add-book-card">

                    <div class="form-card-heading">
                        <div>
                            <h2>Book Information</h2>
                            <p>
                                Enter the details of the book below.
                            </p>
                        </div>

                        <span class="required-note">
                            * Required fields
                        </span>
                    </div>

                    <form method="POST"
                          action="add_books.php"
                          class="add-book-form">

                        <div class="form-grid">

                            <div class="form-group">
                                <label for="book_id">
                                    Book ID <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    id="book_id"
                                    name="book_id"
                                    value="<?= e($book_id) ?>"
                                    placeholder="e.g. B003"
                                    maxlength="50"
                                    required
                                >

                                <small>
                                    Use a unique ID for this book.
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="category">
                                    Category <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    id="category"
                                    name="category"
                                    value="<?= e($category) ?>"
                                    placeholder="e.g. Information Technology"
                                    maxlength="100"
                                    required
                                >

                                <small>
                                    Enter the book's subject or category.
                                </small>
                            </div>

                            <div class="form-group full-width">
                                <label for="title">
                                    Book Title <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    id="title"
                                    name="title"
                                    value="<?= e($title) ?>"
                                    placeholder="Enter the complete book title"
                                    maxlength="255"
                                    required
                                >
                            </div>

                            <div class="form-group full-width">
                                <label for="author">
                                    Author <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    id="author"
                                    name="author"
                                    value="<?= e($author) ?>"
                                    placeholder="Enter the author's name"
                                    maxlength="255"
                                    required
                                >
                            </div>

                        </div>

                        <div class="form-divider"></div>

                        <div class="form-actions">
                            <a href="books.php" class="cancel-btn">
                                Cancel
                            </a>

                            <button type="submit" class="save-book-btn">
                                <span>＋</span>
                                Save Book
                            </button>
                        </div>

                    </form>

                </section>

                <!-- SIDE INFORMATION -->
                <aside class="add-book-tip">

                    <div class="tip-icon">ℹ</div>

                    <h3>Before You Save</h3>

                    <p>
                        Please check the book information before
                        adding it to the library records.
                    </p>

                    <ul>
                        <li>
                            <span>✓</span>
                            Book ID must be unique.
                        </li>
                        <li>
                            <span>✓</span>
                            Enter the complete book title.
                        </li>
                        <li>
                            <span>✓</span>
                            Check the author's name.
                        </li>
                        <li>
                            <span>✓</span>
                            Choose an appropriate category.
                        </li>
                    </ul>

                    <div class="tip-footer">
                        EduLedger: LibSync
                        <span>Digital Library Records System</span>
                    </div>

                </aside>

            </div>

        </section>

    </main>

</div>

</body>
</html>