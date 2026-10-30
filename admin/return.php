<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

$message = "";

/* GET CURRENTLY BORROWED BOOKS */
$sql = "
    SELECT
        borrowings.id,
        borrowings.student_id,
        students.full_name,
        borrowings.book_id,
        books.title,
        borrowings.borrow_date,
        borrowings.due_date
    FROM borrowings
    INNER JOIN students
        ON borrowings.student_id = students.student_id
    INNER JOIN books
        ON borrowings.book_id = books.book_id
    WHERE borrowings.status = 'Borrowed'
    ORDER BY borrowings.due_date ASC
";

$result = $conn->query($sql);


/* RETURN BOOK */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $borrowing_id = $_POST["borrowing_id"];

    /* Get book ID */
    $getBorrowing = $conn->prepare(
        "SELECT book_id
         FROM borrowings
         WHERE id = ?"
    );

    $getBorrowing->bind_param("i", $borrowing_id);
    $getBorrowing->execute();

    $borrowingResult = $getBorrowing->get_result();

    if ($borrowingResult->num_rows == 1) {

        $borrowing = $borrowingResult->fetch_assoc();
        $book_id = $borrowing["book_id"];

        /* Update borrowing record */
        $updateBorrowing = $conn->prepare(
            "UPDATE borrowings
             SET return_date = CURDATE(),
                 status = 'Returned'
             WHERE id = ?"
        );

        $updateBorrowing->bind_param("i", $borrowing_id);
        $updateBorrowing->execute();

        /* Make book available again */
        $updateBook = $conn->prepare(
            "UPDATE books
             SET status = 'Available'
             WHERE book_id = ?"
        );

        $updateBook->bind_param("s", $book_id);
        $updateBook->execute();

        $message = "Book returned successfully!";

    } else {

        $message = "Borrowing record not found.";
    }

    /* Refresh list */
    $result = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>EduLedger: LibSync - Return Records</title>

    <link rel="stylesheet"
          href="../css/admin-dashboard.css">

    <link rel="stylesheet"
          href="../css/admin-return.css">

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

            <a href="return.php" class="nav-item active">
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
                        ↩
                    </div>

                    <div>

                        <h1>
                            Return Records
                        </h1>

                        <p>
                            Manage returned books and update library availability.
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



            <!-- RETURN TABLE -->

            <div class="return-card">

                <div class="return-card-header">

                    <div>

                        <h2>
                            Currently Borrowed Books
                        </h2>

                        <p>
                            Select a borrowed book to record its return.
                        </p>

                    </div>

                </div>


                <div class="return-table-container">

                    <table class="return-table">

                        <thead>

                            <tr>

                                <th>Student ID</th>

                                <th>Student Name</th>

                                <th>Book ID</th>

                                <th>Book Title</th>

                                <th>Borrow Date</th>

                                <th>Due Date</th>

                                <th>Status</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php while ($row = $result->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?php echo htmlspecialchars($row["student_id"]); ?>
                                </td>


                                <td>

                                    <div class="student-info">

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

                                        <span>

                                            <?php
                                            echo htmlspecialchars(
                                                $row["full_name"]
                                            );
                                            ?>

                                        </span>

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

                                    <span class="borrowed-status">
                                        Borrowed
                                    </span>

                                </td>


                                <td>

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="borrowing_id"
                                            value="<?php
                                            echo $row["id"];
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="return-btn"
                                        >
                                            Return Book
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endwhile; ?>

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