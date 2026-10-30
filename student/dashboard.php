<?php
session_start();
require_once "../config.php";

// Protect the student dashboard.
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "student"
) {
    header("Location: ../index.php");
    exit();
}

$username = $_SESSION["username"] ?? "Student";

// Get the student's profile.
$student = null;

// Get the logged-in user's linked student number.
$stmt = $conn->prepare(
    "SELECT student_id
     FROM users
     WHERE id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$linkedStudentNumber = $user["student_id"] ?? null;

// Find the student using the matching student number.
if ($linkedStudentNumber !== null && $linkedStudentNumber !== "") {
    $stmt = $conn->prepare(
        "SELECT id, student_id, full_name, course, year_level
         FROM students
         WHERE student_id = ?
         LIMIT 1"
    );
    $stmt->bind_param("s", $linkedStudentNumber);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$displayName = $student["full_name"] ?? $username;
$studentNumber = $student["student_id"] ?? "Not available";
$course = $student["course"] ?? "Not available";
$yearLevel = $student["year_level"] ?? "Not available";

$currentBorrowed = 0;
$pendingReturns = 0;
$totalBorrowed = 0;
$overdueBooks = 0;
$currentBorrowings = [];
$recentBooks = [];
$overdueBorrowingRecords = [];

if ($student) {
    // Borrowing summary for this student only.
    $stmt = $conn->prepare(
        "SELECT
            COUNT(*) AS total_borrowed,
            COALESCE(SUM(return_date IS NULL), 0) AS current_borrowed,
            COALESCE(SUM(
                return_date IS NULL AND due_date >= CURDATE()
            ), 0) AS pending_returns,
            COALESCE(SUM(
                return_date IS NULL AND due_date < CURDATE()
            ), 0) AS overdue_books
         FROM borrowings
         WHERE student_id = ?"
    );

    $stmt->bind_param("s", $student["student_id"]);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($summary) {
        $totalBorrowed = (int) $summary["total_borrowed"];
        $currentBorrowed = (int) $summary["current_borrowed"];
        $pendingReturns = (int) $summary["pending_returns"];
        $overdueBooks = (int) $summary["overdue_books"];
    }

    // Current borrowing records.
    $stmt = $conn->prepare(
    "SELECT b.title, b.author, br.borrow_date,
            br.due_date, br.return_date
     FROM borrowings br
     INNER JOIN books b ON b.book_id = br.book_id
     WHERE br.student_id = ?
       AND br.return_date IS NULL
     ORDER BY br.due_date ASC
     LIMIT 5"
    );

   $stmt->bind_param("s", $student["student_id"]);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $currentBorrowings[] = $row;
    }

    $stmt->close();

    // Get this student's overdue books only.
$stmt = $conn->prepare(
    "SELECT
        b.title,
        br.due_date,
        DATEDIFF(CURDATE(), br.due_date) AS days_overdue
     FROM borrowings br
     INNER JOIN books b
        ON b.book_id = br.book_id
     WHERE br.student_id = ?
       AND br.return_date IS NULL
       AND br.due_date < CURDATE()
     ORDER BY br.due_date ASC"
);

$stmt->bind_param("s", $student["student_id"]);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $overdueBorrowingRecords[] = $row;
}

$stmt->close();
}

// Recently added books.
$result = $conn->query(
    "SELECT title, author, category, status
     FROM books
     ORDER BY id DESC
     LIMIT 4"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentBooks[] = $row;
    }
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function formatDate($date) {
    return $date
        ? date("M d, Y", strtotime($date))
        : "—";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduLedger: LibSync - Student Dashboard</title>

    <link rel="stylesheet" href="../css/student-dashboard.css">
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
            <a href="dashboard.php" class="nav-item active">
                <span class="nav-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a href="books.php" class="nav-item">
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

        <!-- DASHBOARD CONTENT -->
        <section class="dashboard-content">

            <div class="student-main-layout">

                <!-- LEFT MAIN COLUMN -->
                <main class="student-main">

                    <!-- WELCOME BANNER -->
                    <section class="welcome-banner">

                        <div class="welcome-text">
                            <span class="welcome-label">STUDENT PORTAL</span>

                            <h1>Welcome, <?= e($displayName) ?>!</h1>

                            <p>
                                Keep track of your borrowed books and
                                discover your next great read.
                            </p>

                            <a href="books.php" class="welcome-button">
                                Explore Library Books →
                            </a>
                        </div>

                        <div class="welcome-image" aria-hidden="true"></div>

                    </section>

                    <?php if (!empty($overdueBorrowingRecords)): ?>

    <section class="overdue-alert">
        <div class="overdue-alert-icon">!</div>

        <div class="overdue-alert-content">
            <h2>Overdue Books Reminder</h2>

            <p>
                You have <?= count($overdueBorrowingRecords) ?>
                overdue book(s). Please coordinate with the
                library administrator regarding their return.
            </p>

            <ul>
                <?php foreach ($overdueBorrowingRecords as $book): ?>
                    <li>
                        <strong><?= e($book["title"]) ?></strong>
                        — Due <?= e(formatDate($book["due_date"])) ?>,
                        <?= (int) $book["days_overdue"] ?>
                        day(s) overdue
                    </li>
                <?php endforeach; ?>
            </ul>

            <a href="borrowed.php">View Borrowing History →</a>
        </div>
    </section>

<?php endif; ?>

                    <!-- STUDENT INFORMATION -->
<section class="dashboard-card student-information">

    <div class="student-info-header">
        <div class="student-profile-avatar">
            <?= e(strtoupper(substr($displayName, 0, 1))) ?>
        </div>

        <div class="student-info-name">
            <h2><?= e($displayName) ?></h2>
            <p>Student Information</p>
        </div>
    </div>

    <div class="student-info-details">

        <div class="student-info-item">
            <span class="student-info-icon">▣</span>
            <div>
                <small>Student ID</small>
                <strong><?= e($studentNumber) ?></strong>
            </div>
        </div>

        <div class="student-info-item">
            <span class="student-info-icon">◆</span>
            <div>
                <small>Course</small>
                <strong><?= e($course) ?></strong>
            </div>
        </div>

        <div class="student-info-item">
            <span class="student-info-icon">▦</span>
            <div>
                <small>Year Level</small>
                <strong><?= e($yearLevel) ?></strong>
            </div>
        </div>

        <div class="student-info-item">
            <span class="student-info-icon">♟</span>
            <div>
                <small>Section</small>
                <strong><?= e($student["section"] ?? "Not available") ?></strong>
            </div>
        </div>

    </div>

</section>

                    <!-- BORROWING STATISTICS -->
                    <section class="statistics-grid">

                        <article class="stat-card">
                            <div class="stat-icon">▤</div>
                            <div class="stat-information">
                                <span>Currently Borrowed</span>
                                <h2><?= $currentBorrowed ?></h2>
                                <small>Books not yet returned</small>
                            </div>
                        </article>

                        <article class="stat-card">
                            <div class="stat-icon">◷</div>
                            <div class="stat-information">
                                <span>Pending Returns</span>
                                <h2><?= $pendingReturns ?></h2>
                                <small>Not yet overdue</small>
                            </div>
                        </article>

                        <article class="stat-card">
                            <div class="stat-icon">▣</div>
                            <div class="stat-information">
                                <span>Total Borrowed</span>
                                <h2><?= $totalBorrowed ?></h2>
                                <small>All borrowing records</small>
                            </div>
                        </article>

                        <article class="stat-card">
                            <div class="stat-icon">!</div>
                            <div class="stat-information">
                                <span>Overdue Books</span>
                                <h2><?= $overdueBooks ?></h2>
                                <small>Past the due date</small>
                            </div>
                        </article>

                    </section>

                 
                    <!-- CURRENT BORROWINGS -->
                    <section class="dashboard-card current-borrowings">

                        <div class="section-header">
                            <div>
                                <h2>Current Borrowings</h2>
                                <p>Books currently under your account</p>
                            </div>

                            <a href="borrowed.php">View History →</a>
                        </div>

                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th>Author</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                <?php if ($currentBorrowings): ?>

                                    <?php foreach ($currentBorrowings as $book): ?>
                                        <?php
                                        $isOverdue =
                                            strtotime($book["due_date"]) <
                                            strtotime(date("Y-m-d"));
                                        ?>

                                        <tr>
                                            <td><?= e($book["title"]) ?></td>
                                            <td><?= e($book["author"]) ?></td>
                                            <td><?= e(formatDate($book["due_date"])) ?></td>
                                            <td>
                                                <span class="status <?= $isOverdue ? 'overdue' : 'on-time' ?>">
                                                    <?= $isOverdue ? "Overdue" : "Borrowed" ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr>
                                        <td colspan="4" class="empty-state">
                                            No current borrowing records.
                                            <a href="books.php">Browse library books</a>
                                        </td>
                                    </tr>

                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                    </section>

                    <!-- RECENTLY ADDED BOOKS -->
                    <section class="dashboard-card recently-added">

                        <div class="section-header">
                            <div>
                                <h2>Recently Added Books</h2>
                                <p>Discover books in the library collection</p>
                            </div>

                            <a href="books.php">View All →</a>
                        </div>

                        <div class="recent-books-grid">

                            <?php if ($recentBooks): ?>

                                <?php foreach ($recentBooks as $book): ?>
                                    <article class="recent-book-card">

                                        <div class="book-cover-placeholder">
                                            ▤
                                        </div>

                                        <div class="recent-book-info">
                                            <h3><?= e($book["title"]) ?></h3>
                                            <p><?= e($book["author"]) ?></p>
                                            <span><?= e($book["category"]) ?></span>
                                        </div>

                                        <div class="book-availability">
                                            <?= e($book["status"]) ?>
                                        </div>

                                    </article>
                                <?php endforeach; ?>

                            <?php else: ?>

                                <p class="empty-state">
                                    No books are available to display yet.
                                </p>

                            <?php endif; ?>

                        </div>

                    </section>

                </main>

                <!-- RIGHT SIDE PANEL -->
                <aside class="student-right-panel">

                   

                    <!-- LIBRARY ANNOUNCEMENTS -->
                    <section class="dashboard-card announcements-card">

                        <div class="section-header">
                            <div>
                                <h2>Library Announcements</h2>
                                <p>Reminders for students</p>
                            </div>
                        </div>

                        <article class="announcement-item">
                            <div class="announcement-icon">!</div>
                            <div>
                                <strong>Return Books on Time</strong>
                                <p>Check your due dates to avoid overdue records.</p>
                            </div>
                        </article>

                        <article class="announcement-item">
                            <div class="announcement-icon">▤</div>
                            <div>
                                <strong>Explore the Collection</strong>
                                <p>Browse the catalog to see books and their availability.</p>
                            </div>
                        </article>

                        <article class="announcement-item">
                            <div class="announcement-icon">i</div>
                            <div>
                                <strong>Need Assistance?</strong>
                                <p>Contact the library administrator for record concerns.</p>
                            </div>
                        </article>

                    </section>

                    <!-- LIBRARY SHORTCUT -->
                    <section class="dashboard-card library-shortcut">

                        <div class="shortcut-icon">▤</div>

                        <h2>Find Your Next Read</h2>

                        <p>
                            Explore the library collection and check
                            which books are available.
                        </p>

                        <a href="books.php" class="shortcut-button">
                            Browse Books →
                        </a>

                    </section>

                </aside>

            </div>

            <!-- FOOTER -->
            <footer class="dashboard-footer">
                <span>EduLedger: LibSync</span>
                <span>University of Eastern Pangasinan · Student Portal</span>
            </footer>

        </section>

    </main>

</div>

</body>
</html>