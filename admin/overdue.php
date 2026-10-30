<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

$sql = "
    SELECT
        borrowings.id,
        borrowings.student_id,
        students.full_name,
        books.book_id,
        books.title,
        borrowings.borrow_date,
        borrowings.due_date,
        DATEDIFF(CURDATE(), borrowings.due_date) AS days_overdue
    FROM borrowings

    INNER JOIN students
        ON borrowings.student_id = students.student_id

    INNER JOIN books
        ON borrowings.book_id = books.book_id

    WHERE borrowings.status = 'Borrowed'
    AND borrowings.due_date < CURDATE()

    ORDER BY borrowings.due_date ASC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>EduLedger: LibSync - Overdue Monitoring</title>

    <link rel="stylesheet"
          href="../css/admin-dashboard.css">

    <link rel="stylesheet"
          href="../css/admin-overdue.css">

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

            <a href="overdue.php" class="nav-item active">
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
                        ◷
                    </div>

                    <div>

                        <h1>
                            Overdue Monitoring
                        </h1>

                        <p>
                            Monitor borrowed books that have passed their due date.
                        </p>

                    </div>

                </div>

            </div>



            <!-- WARNING SUMMARY -->

            <div class="overdue-summary">

                <div class="summary-icon">
                    !
                </div>

                <div>

                    <h3>
                        Overdue Books
                    </h3>

                    <p>
                        The records below require attention from the library administrator.
                    </p>

                </div>

                <div class="overdue-count">

                    <?php echo $result->num_rows; ?>

                    <span>
                        overdue
                    </span>

                </div>

            </div>



            <!-- TABLE CARD -->

            <div class="overdue-table-card">


                <div class="table-card-header">

                    <div>

                        <h2>
                            Overdue Records
                        </h2>

                        <p>
                            Books that have not been returned on or before the due date.
                        </p>

                    </div>

                </div>



                <div class="table-container">

                    <table class="overdue-table">

                        <thead>

                            <tr>

                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Book ID</th>
                                <th>Book Title</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Days Overdue</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if ($result->num_rows > 0): ?>

                            <?php while ($row = $result->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $row["student_id"]
                                    );
                                    ?>
                                </td>


                                <td>

                                    <div class="student-cell">

                                        <div class="student-avatar">

                                            <?php
                                            echo strtoupper(
                                                substr(
                                                    $row["full_name"],
                                                    0,
                                                    1
                                                )
                                            );
                                            ?>

                                        </div>

                                        <?php
                                        echo htmlspecialchars(
                                            $row["full_name"]
                                        );
                                        ?>

                                    </div>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $row["book_id"]
                                    );
                                    ?>

                                </td>


                                <td class="book-title">

                                    <?php
                                    echo htmlspecialchars(
                                        $row["title"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $row["borrow_date"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $row["due_date"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <span class="overdue-badge">

                                        ⚠
                                        <?php
                                        echo htmlspecialchars(
                                            $row["days_overdue"]
                                        );
                                        ?>
                                        day(s)

                                    </span>

                                </td>

                            </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="7"
                                    class="no-overdue">

                                    <div class="empty-state">

                                        <div class="empty-icon">
                                            ✓
                                        </div>

                                        <h3>
                                            No Overdue Books
                                        </h3>

                                        <p>
                                            All borrowed books are currently within their due dates.
                                        </p>

                                    </div>

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>


            </div>



            <!-- BACK -->

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