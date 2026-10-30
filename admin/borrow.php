<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

$message = "";

/* GET STUDENTS */
$students = $conn->query(
    "SELECT student_id, full_name
     FROM students
     WHERE status = 'Active'
     ORDER BY full_name"
);

/* GET AVAILABLE BOOKS */
$books = $conn->query(
    "SELECT book_id, title
     FROM books
     WHERE status = 'Available'
     ORDER BY title"
);

/* SAVE BORROWING */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $student_id = $_POST["student_id"];
    $book_id = $_POST["book_id"];
    $borrow_date = $_POST["borrow_date"];
    $due_date = $_POST["due_date"];

    /* Insert borrowing record */
    $sql = $conn->prepare(
        "INSERT INTO borrowings
        (student_id, book_id, borrow_date, due_date, status)
        VALUES (?, ?, ?, ?, 'Borrowed')"
    );

    $sql->bind_param(
        "ssss",
        $student_id,
        $book_id,
        $borrow_date,
        $due_date
    );

    if ($sql->execute()) {

        /* Change book status */
        $updateBook = $conn->prepare(
            "UPDATE books
             SET status = 'Borrowed'
             WHERE book_id = ?"
        );
        
        // Record activity
        $user_id = $_SESSION["user_id"];

        $action = "Borrowed book " . $book_id . " by student " . $student_id;

        $log_sql = "
        INSERT INTO activity_logs (user_id, action)
        VALUES (?, ?)
        ";

        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("is", $user_id, $action);
        $log_stmt->execute();
        $log_stmt->close();


        $updateBook->bind_param("s", $book_id);
        $updateBook->execute();

        $message = "Book borrowed successfully!";

        /* Refresh available books */
        $books = $conn->query(
            "SELECT book_id, title
             FROM books
             WHERE status = 'Available'
             ORDER BY title"
        );

    } else {

        $message = "Error: " . $sql->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>EduLedger: LibSync - Borrowing Records</title>

    <link rel="stylesheet"
          href="../css/admin-dashboard.css">

    <link rel="stylesheet"
          href="../css/admin-borrow.css">

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

            <a href="borrow.php" class="nav-item active">
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
                        ↔
                    </div>

                    <div>

                        <h1>
                            Borrowing Records
                        </h1>

                        <p>
                            Record and manage library book borrowing transactions.
                        </p>

                    </div>

                </div>

            </div>



            <!-- MESSAGE -->

            <?php if ($message != ""): ?>

                <div class="form-message">

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>



            <!-- BORROW FORM -->

            <form method="POST"
                  class="borrow-form">


                <div class="form-card">


                    <div class="form-card-header">

                        <h2>
                            Record New Borrowing
                        </h2>

                        <p>
                            Select the student, book, and borrowing dates.
                        </p>

                    </div>



                    <div class="form-grid">


                        <!-- STUDENT -->

                        <div class="form-group">

                            <label>
                                Student
                            </label>

                            <select name="student_id"
                                    required>

                                <option value="">
                                    -- Select Student --
                                </option>

                                <?php while ($student = $students->fetch_assoc()): ?>

                                    <option value="<?php echo $student["student_id"]; ?>">

                                        <?php
                                        echo htmlspecialchars(
                                            $student["student_id"]
                                            . " - "
                                            . $student["full_name"]
                                        );
                                        ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>

                        </div>



                        <!-- BOOK -->

                        <div class="form-group">

                            <label>
                                Book
                            </label>

                            <select name="book_id"
                                    required>

                                <option value="">
                                    -- Select Book --
                                </option>

                                <?php while ($book = $books->fetch_assoc()): ?>

                                    <option value="<?php echo $book["book_id"]; ?>">

                                        <?php
                                        echo htmlspecialchars(
                                            $book["book_id"]
                                            . " - "
                                            . $book["title"]
                                        );
                                        ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>

                        </div>



                        <!-- BORROW DATE -->

                        <div class="form-group">

                            <label>
                                Borrow Date
                            </label>

                            <input
                                type="date"
                                name="borrow_date"
                                value="<?php echo date('Y-m-d'); ?>"
                                required
                            >

                        </div>



                        <!-- DUE DATE -->

                        <div class="form-group">

                            <label>
                                Due Date
                            </label>

                            <input
                                type="date"
                                name="due_date"
                                required
                            >

                        </div>


                    </div>


                </div>



                <!-- FORM ACTIONS -->

                <div class="form-actions">

                    <a href="dashboard.php"
                       class="cancel-btn">

                        Cancel

                    </a>


                    <button type="submit"
                            class="save-borrow-btn">

                        Record Borrowing

                    </button>

                </div>


            </form>



        </section>

    </main>

</div>

</body>

</html>