<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}
?>

<?php

require_once "../config.php";


// =========================
// TOTAL STUDENTS
// =========================

$studentQuery = $conn->query(
    "SELECT COUNT(*) AS total_students
     FROM students"
);

$studentData = $studentQuery->fetch_assoc();

$totalStudents = $studentData["total_students"];


// =========================
// TOTAL BOOKS
// =========================

$bookQuery = $conn->query(
    "SELECT COUNT(*) AS total_books
     FROM books"
);

$bookData = $bookQuery->fetch_assoc();

$totalBooks = $bookData["total_books"];


// =========================
// CURRENTLY BORROWED
// =========================

$borrowedQuery = $conn->query(
    "SELECT COUNT(*) AS total_borrowed
     FROM borrowings
     WHERE status = 'Borrowed'"
);

$borrowedData = $borrowedQuery->fetch_assoc();

$totalBorrowed = $borrowedData["total_borrowed"];


// =========================
// OVERDUE
// =========================

$overdueQuery = $conn->query(
    "SELECT COUNT(*) AS total_overdue
     FROM borrowings
     WHERE status = 'Borrowed'
     AND due_date < CURDATE()"
);

$overdueData = $overdueQuery->fetch_assoc();

$totalOverdue = $overdueData["total_overdue"];

// =========================
// RECENT BORROWING RECORDS
// =========================

$recentBorrowingQuery = $conn->query(
    "SELECT
        borrowings.id,
        students.student_id,
        students.full_name,
        books.title,
        borrowings.borrow_date,
        borrowings.due_date,
        borrowings.status
     FROM borrowings
     INNER JOIN students
        ON borrowings.student_id = students.student_id
     INNER JOIN books
        ON borrowings.book_id = books.book_id
     ORDER BY borrowings.id DESC
     LIMIT 5"
);

$recentBorrowings = $recentBorrowingQuery->fetch_all(MYSQLI_ASSOC);

// =========================
// RECENT ACTIVITY
// =========================

$activityQuery = $conn->query(
    "SELECT
        activity_logs.action,
        activity_logs.created_at,
        users.username
     FROM activity_logs
     LEFT JOIN users
        ON activity_logs.user_id = users.id
     ORDER BY activity_logs.id DESC
     LIMIT 5"
);

$recentActivities = $activityQuery->fetch_all(MYSQLI_ASSOC);

// =========================
// BOOK BORROWINGS OVERVIEW
// =========================

$borrowingChartQuery = $conn->query(
    "SELECT
        DATE_FORMAT(borrow_date, '%b') AS month,
        COUNT(*) AS total
     FROM borrowings
     WHERE YEAR(borrow_date) = YEAR(CURDATE())
     GROUP BY MONTH(borrow_date), DATE_FORMAT(borrow_date, '%b')
     ORDER BY MONTH(borrow_date)"
);

$borrowingChartData = $borrowingChartQuery->fetch_all(MYSQLI_ASSOC);

// =========================
// BOOKS BY CATEGORY
// =========================

$categoryQuery = $conn->query(
    "SELECT
        category,
        COUNT(*) AS total
     FROM books
     GROUP BY category
     ORDER BY total DESC"
);

$categoryData = $categoryQuery->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduLedger: LibSync - Admin Dashboard</title>

    <link rel="stylesheet" href="../css/admin-dashboard.css">

</head>

<body>

<div class="dashboard-container">

    <!-- =========================
         SIDEBAR
    ========================== -->

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

            <a href="students.php" class="nav-item">
                <span class="nav-icon">♙</span>
                <span>Student Management</span>
            </a>

            <a href="books.php" class="nav-item">
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

            <a href="../logout.php" class="nav-item logout">
                <span class="nav-icon">↪</span>
                <span>Logout</span>
            </a>

        </div>

    </aside>

    <!-- =========================
         MAIN CONTENT
    ========================== -->

    <main class="main-content">


        <!-- =========================
             TOP HEADER
        ========================== -->

        <header class="top-header">

            <div class="university-brand">

                <img src="../assets/uep_logo.png" alt="UEP Logo">

                <div>
                    <h2>UNIVERSITY OF EASTERN PANGASINAN</h2>
                    <p>Digital Library Records System</p>
                </div>

            </div>


            <div class="header-right">

                <button class="notification-btn">
                
                    <span class="notification-count"></span>
                </button>


                <div class="admin-header-profile">

                    <div class="admin-avatar">
                        👤
                    </div>

                    <div class="admin-info">
                        <strong>Admin</strong>
                        <span>   Administrator</span>
                    </div>

                    <span class="profile-arrow">▾</span>

                </div>

            </div>

        </header>

        <!-- =========================
             DASHBOARD CONTENT
        ========================== -->

                    <section class="dashboard-content">

    <div class="dashboard-main-layout">

        <!-- =================================
             LEFT MAIN CONTENT
        ================================== -->

        <main class="dashboard-main">


            <!-- =========================
                 WELCOME BANNER
            ========================== -->

            <section class="welcome-banner">

                <div class="welcome-text">

                    <h1>Welcome, Admin!</h1>

                    <p>
                        Manage library records, students, and books efficiently.
                    </p>

                </div>

                <div class="welcome-image">

                    <!-- UEP building image will go here -->

                </div>

            </section>


            <!-- =========================
                 STATISTICS
            ========================== -->

            <section class="statistics-grid">


                <div class="stat-card">

                    <div class="stat-icon">
                        👥
                    </div>

                    <div class="stat-information">

                        <span>Total Students</span>

                        <h2><?php echo $totalStudents; ?></h2>

                        <small>+12 this month</small>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        📚
                    </div>

                    <div class="stat-information">

                        <span>Total Books</span>

                        <h2><?php echo $totalBooks; ?></h2>

                        <small>+28 this month</small>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        🔄
                    </div>

                    <div class="stat-information">

                        <span>Currently Borrowed</span>

                        <h2><?php echo $totalBorrowed; ?></h2>

                        <small>+15 this week</small>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        ⚠
                    </div>

                    <div class="stat-information">

                        <span>Overdue</span>

                        <h2><?php echo $totalOverdue; ?></h2>

                        <small>-3 this week</small>

                    </div>

                </div>


            </section>


            <!-- =========================
                 QUICK ACTIONS
            ========================== -->

            <section class="dashboard-card quick-actions">

                <div class="section-header">

                    <h2>Quick Actions</h2>

                    <a href="#">View All</a>

                </div>


                <div class="quick-action-grid">


                    <a href="students.php" class="quick-action">

                        <div class="quick-icon">
                            👤+
                        </div>

                        <span>Add Student</span>

                    </a>


                    <a href="add_book.php" class="quick-action">

                        <div class="quick-icon">
                            📖
                        </div>

                        <span>Add Book</span>

                    </a>


                    <a href="borrow.php" class="quick-action">

                        <div class="quick-icon">
                            🔄
                        </div>

                        <span>Borrow Book</span>

                    </a>


                    <a href="return.php" class="quick-action">

                        <div class="quick-icon">
                            ↩
                        </div>

                        <span>Return Book</span>

                    </a>


                    <a href="search.php" class="quick-action">

                        <div class="quick-icon">
                            🔍
                        </div>

                        <span>Search Books</span>

                    </a>


                    <a href="reports.php" class="quick-action">

                        <div class="quick-icon">
                            📊
                        </div>

                        <span>View Reports</span>

                    </a>


                </div>

            </section>


            <!-- =========================
     ANALYTICS
========================== -->

<section class="analytics-grid">

    <!-- BOOK BORROWINGS OVERVIEW -->
    <div class="dashboard-card">

        <div class="section-header">
            <h2>Book Borrowings Overview</h2>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="borrowing-summary">

            <div class="borrowing-summary-card">
                <span>Borrowing Records</span>
                <strong id="totalChartBorrowings">0</strong>
                <small>Total in displayed period</small>
            </div>

            <div class="borrowing-summary-card">
                <span>Latest Month</span>
                <strong id="latestChartBorrowings">0</strong>
                <small>Borrowing records</small>
            </div>

        </div>

        <!-- BORROWING BAR GRAPH -->
        <div class="chart-container">
            <canvas id="borrowingChart"></canvas>
        </div>

    </div>

    <!-- BOOKS BY CATEGORY -->
    <div class="dashboard-card">

        <div class="section-header">
            <h2>Books by Category</h2>
        </div>

        <div class="category-chart">
            <canvas id="categoryChart"></canvas>
        </div>

    </div>

</section>


            <!-- =========================
                 RECENT BORROWING RECORDS
            ========================== -->

            <section class="dashboard-grid">

                <div class="dashboard-card recent-borrowing">


                    <div class="section-header">

                        <h2>Recent Borrowing Records</h2>

                        <a href="borrow.php">
                            View All
                        </a>

                    </div>


                    <div class="table-container">

                        <table>

                            <thead>

                                <tr>

                                    <th>Student ID</th>
                                    <th>Full Name</th>
                                    <th>Book Title</th>
                                    <th>Borrow Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php if (count($recentBorrowings) > 0): ?>

    <?php foreach ($recentBorrowings as $borrow): ?>

        <tr>

            <td>
                <?php echo htmlspecialchars($borrow["student_id"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($borrow["full_name"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($borrow["title"]); ?>
            </td>

            <td>
                <?php echo date("M d, Y", strtotime($borrow["borrow_date"])); ?>
            </td>

            <td>
                <?php echo date("M d, Y", strtotime($borrow["due_date"])); ?>
            </td>

            <td>

                <?php
                $status = $borrow["status"];

                if ($status == "Borrowed") {
                    echo '<span class="status on-time">Borrowed</span>';
                } elseif ($status == "Returned") {
                    echo '<span class="status returned">Returned</span>';
                } else {
                    echo '<span class="status overdue">' .
                         htmlspecialchars($status) .
                         '</span>';
                }
                ?>

            </td>

        </tr>

    <?php endforeach; ?>

<?php else: ?>

    <tr>

        <td colspan="6" style="text-align:center;">
            No borrowing records found.
        </td>

    </tr>

<?php endif; ?>



                            </tbody>

                        </table>

                    </div>

                </div>

            </section>


        </main>


        <!-- =================================
             RIGHT SIDE PANEL
        ================================== -->

        <aside class="dashboard-right-panel">


            <!-- =========================
                 ADMIN PROFILE
            ========================== -->

            <div class="dashboard-card admin-profile">

                <h2>Admin Profile</h2>


                <div class="profile-content">

                    <div class="profile-avatar">
                        👤
                    </div>


                    <div>

                        <h3>Admin</h3>

                        <p>
                            Librarian
                        </p>

                    </div>

                </div>


                <button class="profile-button">
                    View Profile
                </button>

            </div>


            <!-- =========================
                 RECENT ACTIVITY
            ========================== -->

            <div class="dashboard-card side-recent-activity">

                <div class="section-header">

                    <h2>Recent Activity</h2>

                    <a href="logs.php">
                        View All
                    </a>

                </div>


                <div class="side-activity-list">

                    <?php if (count($recentActivities) > 0): ?>

        <?php foreach ($recentActivities as $activity): ?>

            <div class="side-activity-item">

                <div class="activity-icon">
                    📌
                </div>

                <div>

                    <strong>
                        <?php echo htmlspecialchars($activity["action"]); ?>
                    </strong>

                    <span>
                        By <?php echo htmlspecialchars($activity["username"] ?? "System"); ?>
                    </span>

                    <small>
                        <?php echo date("M d, Y h:i A", strtotime($activity["created_at"])); ?>
                    </small>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="side-activity-item">

            <div class="activity-icon">
                📌
            </div>

            <div>

                <strong>No recent activity</strong>

                <small>
                    No activity has been recorded yet.
                </small>

            </div>

        </div>

    <?php endif; ?>
                   
                </div>

            </div>


        </aside>


    </div>

</section>
            

        </section>

    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ========================================
// BOOK BORROWINGS OVERVIEW
// ========================================

const borrowingLabels = <?php
    echo json_encode(array_column($borrowingChartData, "month"));
?>;

const borrowingData = <?php
    echo json_encode(array_map(
        'intval',
        array_column($borrowingChartData, "total")
    ));
?>;

// Calculate summary values
const totalBorrowings = borrowingData.reduce(
    (sum, value) => sum + value, 0
);

const latestBorrowings = borrowingData.length > 0
    ? borrowingData[borrowingData.length - 1]
    : 0;

// Update summary cards
const totalElement = document.getElementById("totalChartBorrowings");
const latestElement = document.getElementById("latestChartBorrowings");

if (totalElement) {
    totalElement.textContent = totalBorrowings;
}

if (latestElement) {
    latestElement.textContent = latestBorrowings;
}

// Create borrowing chart
const borrowingCanvas = document.getElementById("borrowingChart");

if (borrowingCanvas) {
    new Chart(borrowingCanvas, {
        type: "bar",

        data: {
            labels: borrowingLabels,

            datasets: [{
                label: "Total Borrowings",
                data: borrowingData,

                backgroundColor: "#A64B4B",
                hoverBackgroundColor: "#800000",

                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 42,
                categoryPercentage: 0.65,
                barPercentage: 0.8
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                },

                tooltip: {
                    backgroundColor: "#800000",
                    titleColor: "#FFFFFF",
                    bodyColor: "#FFFFFF",
                    padding: 12,
                    cornerRadius: 8,

                    callbacks: {
                        label: function(context) {
                            return "Borrowings: " + context.parsed.y;
                        }
                    }
                }
            },

            scales: {
                y: {
                    beginAtZero: true,

                    ticks: {
                        precision: 0,
                        stepSize: 1,
                        color: "#77716D"
                    },

                    title: {
                        display: true,
                        text: "Books Borrowed",
                        color: "#77716D",

                        font: {
                            size: 12,
                            weight: "500"
                        }
                    },

                    grid: {
                        color: "#EFE7E0"
                    },

                    border: {
                        display: false
                    }
                },

                x: {
                    ticks: {
                        color: "#665E59"
                    },

                    grid: {
                        display: false
                    },

                    border: {
                        display: false
                    }
                }
            }
        }
    });
}


// ========================================
// BOOKS BY CATEGORY
// ========================================

const categoryLabels = <?php
    echo json_encode(array_column($categoryData, "category"));
?>;

const categoryTotals = <?php
    echo json_encode(array_map(
        'intval',
        array_column($categoryData, "total")
    ));
?>;

const categoryCanvas = document.getElementById("categoryChart");

if (categoryCanvas) {
    new Chart(categoryCanvas, {
        type: "bar",

        data: {
            labels: categoryLabels,

            datasets: [{
                label: "Number of Books",
                data: categoryTotals,

                backgroundColor: [
                    "#800000",
                    "#A64B4B",
                    "#B98272",
                    "#9A8878",
                    "#C8B7A6",
                    "#756B66"
                ],

                hoverBackgroundColor: [
                    "#620000",
                    "#843737",
                    "#966451",
                    "#79695B",
                    "#A89583",
                    "#5A514C"
                ],

                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 28,
                categoryPercentage: 0.72,
                barPercentage: 0.85
            }]
        },

        options: {
            indexAxis: "y",
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                },

                tooltip: {
                    backgroundColor: "#800000",
                    titleColor: "#FFFFFF",
                    bodyColor: "#FFFFFF",
                    padding: 12,
                    cornerRadius: 8,

                    callbacks: {
                        label: function(context) {
                            const total = context.parsed.x;

                            return total +
                                (total === 1 ? " book" : " books");
                        }
                    }
                }
            },

            scales: {
                x: {
                    beginAtZero: true,

                    ticks: {
                        precision: 0,
                        color: "#77716D"
                    },

                    title: {
                        display: true,
                        text: "Number of Books",
                        color: "#77716D",

                        font: {
                            size: 12,
                            weight: "500"
                        }
                    },

                    grid: {
                        color: "#EFE7E0"
                    },

                    border: {
                        display: false
                    }
                },

                y: {
                    ticks: {
                        color: "#665E59"
                    },

                    grid: {
                        display: false
                    },

                    border: {
                        display: false
                    }
                }
            }
        }
    });
}
</script>
</body>
</html>
