<?php
session_start();
require_once "../config.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "student"
) {
    header("Location: ../index.php");
    exit();
}

$student_id = $_SESSION["student_id"] ?? "";

function e($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// Get the student's borrowing records
$sql = "
    SELECT
        borrowings.book_id,
        books.title,
        borrowings.borrow_date,
        borrowings.due_date,
        borrowings.return_date,
        borrowings.status
    FROM borrowings
    INNER JOIN books
        ON borrowings.book_id = books.book_id
    WHERE borrowings.student_id = ?
    ORDER BY borrowings.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

// Get student name for the header
$displayName = $_SESSION["username"] ?? "Student";

$nameSql = "
    SELECT s.full_name
    FROM users u
    LEFT JOIN students s
        ON s.student_id = u.student_id
    WHERE u.id = ?
    LIMIT 1
";

$nameStmt = $conn->prepare($nameSql);
$nameStmt->bind_param("i", $_SESSION["user_id"]);
$nameStmt->execute();
$nameResult = $nameStmt->get_result();

if ($nameRow = $nameResult->fetch_assoc()) {
    if (!empty($nameRow["full_name"])) {
        $displayName = $nameRow["full_name"];
    }
}

$totalRecords = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Borrowing History - EduLedger: LibSync</title>

    <link rel="stylesheet"
          href="../css/student-dashboard.css">

    <link rel="stylesheet"
          href="../css/student-borrowed.css">
</head>

<body>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="sidebar-brand">
            <img src="../assets/uep_logo.png"
                 alt="UEP Logo">

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

            <a href="books.php" class="nav-item">
                <span class="nav-icon">▣</span>
                <span>Library Books</span>
            </a>

            <a href="borrowed.php"
               class="nav-item active">
                <span class="nav-icon">◷</span>
                <span>My Borrowing History</span>
            </a>

        </nav>

        <div class="sidebar-bottom">
            <a href="../logout.php"
               class="nav-item logout">
                <span class="nav-icon">↪</span>
                <span>Logout</span>
            </a>
        </div>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- HEADER -->
        <header class="top-header">

            <div class="university-brand">
                <img src="../assets/uep_logo.png"
                     alt="UEP Logo">

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

        <!-- PAGE CONTENT -->
        <section class="dashboard-content borrowing-content">

            <div class="page-heading">
                <div>
                    <span class="page-eyebrow">
                        STUDENT PORTAL
                    </span>

                    <h1>My Borrowing History</h1>

                    <p>
                        Track your borrowed books, due dates,
                        and return records.
                    </p>
                </div>

                <a href="books.php" class="browse-books-btn">
                    <span>▣</span>
                    Browse Books
                </a>
            </div>

            <!-- SUMMARY CARD -->
            <div class="borrowing-summary">

                <div class="summary-icon">◷</div>

                <div class="summary-details">
                    <span>Total Borrowing Records</span>
                    <strong><?= $totalRecords ?></strong>
                    <p>
                        Student ID:
                        <b><?= e($student_id) ?></b>
                    </p>
                </div>

            </div>

            <!-- HISTORY TABLE -->
            <div class="borrowing-card">

                <div class="borrowing-card-header">
                    <div>
                        <h2>Borrowing Records</h2>
                        <p>
                            Your borrowing transactions
                            are listed below.
                        </p>
                    </div>

                    <span class="record-count">
                        <?= $totalRecords ?>
                        <?= $totalRecords === 1 ? "Record" : "Records" ?>
                    </span>
                </div>

                <div class="borrowing-table-wrapper">

                    <table class="borrowing-table">

                        <thead>
                            <tr>
                                <th>Book ID</th>
                                <th>Book Title</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($result->num_rows > 0): ?>

                            <?php while ($row = $result->fetch_assoc()): ?>

                                <?php
                                $status = trim(
                                    (string)($row["status"] ?? "")
                                );

                                $statusClass = "status-default";

                                if (strcasecmp($status, "Borrowed") === 0) {
                                    $statusClass = "status-borrowed";
                                } elseif (strcasecmp($status, "Returned") === 0) {
                                    $statusClass = "status-returned";
                                } elseif (strcasecmp($status, "Overdue") === 0) {
                                    $statusClass = "status-overdue";
                                }

                                $returnDate = $row["return_date"] ?? "";
                                ?>

                                <tr>
                                    <td>
                                        <span class="book-id">
                                            <?= e($row["book_id"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="book-title">
                                            <?= e($row["title"]) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?= e($row["borrow_date"]) ?>
                                    </td>

                                    <td>
                                        <?= e($row["due_date"]) ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($returnDate)): ?>
                                            <?= e($returnDate) ?>
                                        <?php else: ?>
                                            <span class="not-returned">
                                                Not Returned
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="status-badge <?= e($statusClass) ?>">
                                            <?= e($status) ?>
                                        </span>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div class="empty-icon">▣</div>
                                        <h3>No borrowing records yet</h3>
                                        <p>
                                            Your borrowing transactions
                                            will appear here once recorded.
                                        </p>
                                        <a href="books.php">
                                            Explore Library Books
                                        </a>
                                    </div>
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <div class="borrowing-card-footer">
                    <span>
                        Showing <?= $totalRecords ?>
                        <?= $totalRecords === 1 ? "record" : "records" ?>
                    </span>

                    <a href="dashboard.php">
                        ← Back to Dashboard
                    </a>
                </div>

            </div>

        </section>

    </main>

</div>

</body>
</html>