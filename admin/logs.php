<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

$sql = "
    SELECT
        activity_logs.id,
        users.username,
        activity_logs.action,
        activity_logs.created_at
    FROM activity_logs

    INNER JOIN users
        ON activity_logs.user_id = users.id

    ORDER BY activity_logs.id DESC
";

$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduLedger: LibSync - Activity Logs</title>

    <link rel="stylesheet" href="../css/admin-dashboard.css">
    <link rel="stylesheet" href="../css/admin-logs.css">
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

            <a href="logs.php" class="nav-item active">
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
                        <strong>
                            <?php echo htmlspecialchars($_SESSION["username"]); ?>
                        </strong>
                        <span>Administrator</span>
                    </div>

                    <span class="profile-arrow">▾</span>

                </div>

            </div>

        </header>


        <!-- PAGE CONTENT -->

        <section class="page-content">

            <div class="page-header">

                <div class="page-title">

                    <div class="page-title-icon">☷</div>

                    <div>
                        <h1>Activity Logs</h1>
                        <p>Track and review actions performed in the library system.</p>
                    </div>

                </div>

            </div>


            <!-- SUMMARY CARD -->

            <div class="log-summary">

                <div class="summary-card">

                    <div class="summary-icon">☷</div>

                    <div class="summary-info">
                        <span>Total Activities</span>
                        <strong><?php echo $result->num_rows; ?></strong>
                    </div>

                </div>

            </div>


            <!-- ACTIVITY TABLE -->

            <div class="logs-card">

                <div class="logs-card-header">

                    <div>
                        <h2>System Activity</h2>
                        <p>View the recorded actions and the users who performed them.</p>
                    </div>

                </div>

                <div class="table-wrapper">

                    <table class="logs-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Date &amp; Time</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($result->num_rows > 0): ?>

                            <?php while ($row = $result->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        <span class="log-id">
                                            <?php echo (int)$row["id"]; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="user-cell">

                                            <div class="user-avatar">
                                                <?php
                                                echo strtoupper(
                                                    substr($row["username"], 0, 1)
                                                );
                                                ?>
                                            </div>

                                            <span>
                                                <?php
                                                echo htmlspecialchars(
                                                    $row["username"]
                                                );
                                                ?>
                                            </span>

                                        </div>
                                    </td>

                                    <td>
                                        <span class="action-text">
                                            <?php
                                            echo htmlspecialchars(
                                                $row["action"]
                                            );
                                            ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="date-text">
                                            <?php
                                            echo htmlspecialchars(
                                                $row["created_at"]
                                            );
                                            ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="4">

                                    <div class="empty-state">
                                        <div class="empty-icon">☷</div>
                                        <strong>No activity recorded yet.</strong>
                                        <p>System actions will appear here when recorded.</p>
                                    </div>

                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


            <div class="page-footer">
                <a href="dashboard.php">← Back to Dashboard</a>
            </div>

        </section>

    </main>

</div>

</body>
</html>