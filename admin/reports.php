<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}


/* Total Students */
$sql = "SELECT COUNT(*) AS total FROM students";
$result = $conn->query($sql);
$total_students = $result->fetch_assoc()["total"];


/* Total Books */
$sql = "SELECT COUNT(*) AS total FROM books";
$result = $conn->query($sql);
$total_books = $result->fetch_assoc()["total"];


/* Available Books */
$sql = "SELECT COUNT(*) AS total FROM books WHERE status = 'Available'";
$result = $conn->query($sql);
$available_books = $result->fetch_assoc()["total"];


/* Borrowed Books */
$sql = "SELECT COUNT(*) AS total FROM books WHERE status = 'Borrowed'";
$result = $conn->query($sql);
$borrowed_books = $result->fetch_assoc()["total"];


/* Returned Books */
$sql = "SELECT COUNT(*) AS total FROM borrowings WHERE status = 'Returned'";
$result = $conn->query($sql);
$returned_books = $result->fetch_assoc()["total"];


/* Overdue Books */
$sql = "
    SELECT COUNT(*) AS total
    FROM borrowings
    WHERE status = 'Borrowed'
    AND due_date < CURDATE()
";

$result = $conn->query($sql);
$overdue_books = $result->fetch_assoc()["total"];


/* Total Transactions */
$sql = "SELECT COUNT(*) AS total FROM borrowings";
$result = $conn->query($sql);
$total_transactions = $result->fetch_assoc()["total"];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>EduLedger: LibSync - Reports & Analytics</title>

    <link rel="stylesheet"
          href="../css/admin-dashboard.css">

    <link rel="stylesheet"
          href="../css/admin-reports.css">

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

            <a href="students.php" class="nav-item">
                <span class="nav-icon">♟</span>
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

            <a href="reports.php" class="nav-item active">
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

                <img src="../assets/uep_logo.png"
                     alt="UEP Logo">

                <div>

                    <h2>
                        UNIVERSITY OF EASTERN PANGASINAN
                    </h2>

                    <p>
                        Digital Library Records System
                    </p>

                </div>

            </div>


            <div class="header-right">

                <div class="admin-header-profile">

                    <div class="admin-avatar">
                        👤
                    </div>

                    <div class="admin-info">

                        <strong>
                            <?php echo htmlspecialchars($_SESSION["username"]); ?>
                        </strong>

                        <span>
                            Administrator
                        </span>

                    </div>

                    <span class="profile-arrow">
                        ▾
                    </span>

                </div>

            </div>

        </header>



        <!-- PAGE CONTENT -->

        <section class="page-content">


            <!-- PAGE HEADER -->

            <div class="page-header">

                <div class="page-title">

                    <div class="page-title-icon">
                        ▥
                    </div>

                    <div>

                        <h1>
                            Reports & Analytics
                        </h1>

                        <p>
                            View an overview of library records and transactions.
                        </p>

                    </div>

                </div>

            </div>



            <!-- OVERVIEW CARDS -->

            <div class="report-grid">


                <!-- STUDENTS -->

                <div class="report-card">

                    <div class="report-icon students-icon">
                        ♟
                    </div>

                    <div class="report-info">

                        <span>
                            Total Students
                        </span>

                        <strong>
                            <?php echo $total_students; ?>
                        </strong>

                    </div>

                </div>



                <!-- BOOKS -->

                <div class="report-card">

                    <div class="report-icon books-icon">
                        ▣
                    </div>

                    <div class="report-info">

                        <span>
                            Total Books
                        </span>

                        <strong>
                            <?php echo $total_books; ?>
                        </strong>

                    </div>

                </div>



                <!-- AVAILABLE -->

                <div class="report-card">

                    <div class="report-icon available-icon">
                        ✓
                    </div>

                    <div class="report-info">

                        <span>
                            Available Books
                        </span>

                        <strong>
                            <?php echo $available_books; ?>
                        </strong>

                    </div>

                </div>



                <!-- BORROWED -->

                <div class="report-card">

                    <div class="report-icon borrowed-icon">
                        ↔
                    </div>

                    <div class="report-info">

                        <span>
                            Borrowed Books
                        </span>

                        <strong>
                            <?php echo $borrowed_books; ?>
                        </strong>

                    </div>

                </div>


            </div>



            <!-- SECOND ROW -->

            <div class="report-grid report-grid-second">


                <!-- RETURNED -->

                <div class="report-card">

                    <div class="report-icon returned-icon">
                        ↩
                    </div>

                    <div class="report-info">

                        <span>
                            Returned Books
                        </span>

                        <strong>
                            <?php echo $returned_books; ?>
                        </strong>

                    </div>

                </div>



                <!-- OVERDUE -->

                <div class="report-card overdue-card">

                    <div class="report-icon overdue-icon">
                        !
                    </div>

                    <div class="report-info">

                        <span>
                            Overdue Books
                        </span>

                        <strong>
                            <?php echo $overdue_books; ?>
                        </strong>

                    </div>

                </div>



                <!-- TRANSACTIONS -->

                <div class="report-card transaction-card">

                    <div class="report-icon transaction-icon">
                        #
                    </div>

                    <div class="report-info">

                        <span>
                            Total Transactions
                        </span>

                        <strong>
                            <?php echo $total_transactions; ?>
                        </strong>

                    </div>

                </div>


            </div>



            <!-- LIBRARY OVERVIEW -->

            <div class="overview-card">


                <div class="overview-header">

                    <div>

                        <h2>
                            Library Overview
                        </h2>

                        <p>
                            Summary of the current library database.
                        </p>

                    </div>

                </div>


                <div class="overview-list">


                    <div class="overview-row">

                        <span>
                            Total Students
                        </span>

                        <strong>
                            <?php echo $total_students; ?>
                        </strong>

                    </div>


                    <div class="overview-row">

                        <span>
                            Total Books
                        </span>

                        <strong>
                            <?php echo $total_books; ?>
                        </strong>

                    </div>


                    <div class="overview-row">

                        <span>
                            Available Books
                        </span>

                        <strong>
                            <?php echo $available_books; ?>
                        </strong>

                    </div>


                    <div class="overview-row">

                        <span>
                            Borrowed Books
                        </span>

                        <strong>
                            <?php echo $borrowed_books; ?>
                        </strong>

                    </div>


                    <div class="overview-row">

                        <span>
                            Returned Books
                        </span>

                        <strong>
                            <?php echo $returned_books; ?>
                        </strong>

                    </div>


                    <div class="overview-row overdue-row">

                        <span>
                            Overdue Books
                        </span>

                        <strong>
                            <?php echo $overdue_books; ?>
                        </strong>

                    </div>


                    <div class="overview-row total-row">

                        <span>
                            Total Transactions
                        </span>

                        <strong>
                            <?php echo $total_transactions; ?>
                        </strong>

                    </div>


                </div>

            </div>



            <!-- FOOTER -->

            <div class="page-footer">

                <a href="dashboard.php">
                    ← Back to Dashboard
                </a>

            </div>


        </section>

    </main>

</div>

</body>

</html>

