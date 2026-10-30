<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

$students = [];
$books = [];
$borrowings = [];

if ($search != "") {

    // Search Students
    $stmt = $conn->prepare("
        SELECT * FROM students
        WHERE student_id LIKE ?
        OR full_name LIKE ?
        OR course LIKE ?
    ");

    $keyword = "%" . $search . "%";

    $stmt->bind_param("sss", $keyword, $keyword, $keyword);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    $stmt->close();


    // Search Books
    $stmt = $conn->prepare("
        SELECT * FROM books
        WHERE book_id LIKE ?
        OR title LIKE ?
        OR author LIKE ?
        OR category LIKE ?
    ");

    $stmt->bind_param(
        "ssss",
        $keyword,
        $keyword,
        $keyword,
        $keyword
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }

    $stmt->close();


    // Search Borrowing Records
    $stmt = $conn->prepare("
        SELECT 
            borrowings.*,
            students.full_name,
            books.title
        FROM borrowings
        INNER JOIN students 
            ON borrowings.student_id = students.student_id
        INNER JOIN books 
            ON borrowings.book_id = books.book_id
        WHERE students.student_id LIKE ?
        OR students.full_name LIKE ?
        OR books.book_id LIKE ?
        OR books.title LIKE ?
    ");

    $stmt->bind_param(
        "ssss",
        $keyword,
        $keyword,
        $keyword,
        $keyword
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $borrowings[] = $row;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>EduLedger: LibSync - Search & View</title>

    <link rel="stylesheet"
          href="../css/admin-dashboard.css">

    <link rel="stylesheet"
          href="../css/admin-search.css">

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

            <a href="search.php" class="nav-item active">
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


            <!-- PAGE TITLE -->

            <div class="page-header">

                <div class="page-title">

                    <div class="page-title-icon">
                        ⌕
                    </div>

                    <div>

                        <h1>
                            Search & View
                        </h1>

                        <p>
                            Find students, books, and borrowing records quickly.
                        </p>

                    </div>

                </div>

            </div>



            <!-- SEARCH CARD -->

            <div class="search-card">


                <div class="search-card-header">

                    <h2>
                        Search Library Records
                    </h2>

                    <p>
                        Search by student, book, or borrowing information.
                    </p>

                </div>


                <form method="GET"
                      class="search-form">

                    <div class="search-input-wrapper">

                        <span class="search-input-icon">
                            ⌕
                        </span>

                        <input
                            type="text"
                            name="search"
                            placeholder="Search student, book, or borrowing record..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >

                    </div>


                    <button type="submit"
                            class="search-btn">

                        ⌕ Search

                    </button>

                </form>


            </div>



            <?php if ($search != ""): ?>


            <!-- SEARCH RESULT TITLE -->

            <div class="results-heading">

                <div>

                    <h2>
                        Search Results
                    </h2>

                    <p>
                        Showing results for:
                        <strong>
                            "<?php echo htmlspecialchars($search); ?>"
                        </strong>
                    </p>

                </div>

            </div>



            <!-- STUDENTS -->

            <div class="results-card">

                <div class="results-card-header">

                    <div>

                        <h3>
                            👤 Students
                        </h3>

                        <p>
                            Student records matching your search.
                        </p>

                    </div>

                    <span class="result-count">
                        <?php echo count($students); ?>
                    </span>

                </div>


                <div class="table-container">

                    <table class="search-table">

                        <thead>

                            <tr>

                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Course</th>
                                <th>Status</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (count($students) > 0): ?>

                            <?php foreach ($students as $student): ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student["student_id"]
                                    );
                                    ?>
                                </td>

                                <td>

                                    <div class="person-cell">

                                        <div class="person-avatar">

                                            <?php
                                            echo strtoupper(
                                                substr(
                                                    $student["full_name"],
                                                    0,
                                                    1
                                                )
                                            );
                                            ?>

                                        </div>

                                        <?php
                                        echo htmlspecialchars(
                                            $student["full_name"]
                                        );
                                        ?>

                                    </div>

                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student["course"]
                                    );
                                    ?>
                                </td>

                                <td>

                                    <span class="status-active">
                                        <?php
                                        echo htmlspecialchars(
                                            $student["status"]
                                        );
                                        ?>
                                    </span>

                                </td>

                            </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="4"
                                    class="no-results">

                                    No students found.

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>



            <!-- BOOKS -->

            <div class="results-card">

                <div class="results-card-header">

                    <div>

                        <h3>
                            📚 Books
                        </h3>

                        <p>
                            Library books matching your search.
                        </p>

                    </div>

                    <span class="result-count">
                        <?php echo count($books); ?>
                    </span>

                </div>


                <div class="table-container">

                    <table class="search-table">

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

                        <?php if (count($books) > 0): ?>

                            <?php foreach ($books as $book): ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $book["book_id"]
                                    );
                                    ?>
                                </td>

                                <td class="book-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $book["title"]
                                    );
                                    ?>

                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $book["author"]
                                    );
                                    ?>
                                </td>

                                <td>

                                    <span class="category-badge">

                                        <?php
                                        echo htmlspecialchars(
                                            $book["category"]
                                        );
                                        ?>

                                    </span>

                                </td>

                                <td>

                                    <span class="status-available">

                                        <?php
                                        echo htmlspecialchars(
                                            $book["status"]
                                        );
                                        ?>

                                    </span>

                                </td>

                            </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="5"
                                    class="no-results">

                                    No books found.

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>



            <!-- BORROWING -->

            <div class="results-card">

                <div class="results-card-header">

                    <div>

                        <h3>
                            ↔ Borrowing Records
                        </h3>

                        <p>
                            Borrowing transactions matching your search.
                        </p>

                    </div>

                    <span class="result-count">
                        <?php echo count($borrowings); ?>
                    </span>

                </div>


                <div class="table-container">

                    <table class="search-table borrowing-table">

                        <thead>

                            <tr>

                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Book ID</th>
                                <th>Book Title</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th>Status</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (count($borrowings) > 0): ?>

                            <?php foreach ($borrowings as $borrow): ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $borrow["student_id"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $borrow["full_name"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $borrow["book_id"]
                                    );
                                    ?>
                                </td>

                                <td class="book-name">
                                    <?php
                                    echo htmlspecialchars(
                                        $borrow["title"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $borrow["borrow_date"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $borrow["due_date"]
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php
                                    echo $borrow["return_date"]
                                        ? htmlspecialchars(
                                            $borrow["return_date"]
                                        )
                                        : "Not Returned";
                                    ?>

                                </td>

                                <td>

                                    <span class="borrow-status">

                                        <?php
                                        echo htmlspecialchars(
                                            $borrow["status"]
                                        );
                                        ?>

                                    </span>

                                </td>

                            </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="8"
                                    class="no-results">

                                    No borrowing records found.

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


            <?php endif; ?>



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