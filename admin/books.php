<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

// Search and category filter
$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");

$sql = "SELECT * FROM books WHERE 1=1";
$params = [];
$types = "";

if ($search !== "") {
    $sql .= " AND (
        book_id LIKE ?
        OR title LIKE ?
        OR author LIKE ?
        OR category LIKE ?
    )";

    $keyword = "%" . $search . "%";

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;

    $types .= "ssss";
}

if ($category !== "") {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Get actual categories from database
$category_sql = "SELECT DISTINCT category
                 FROM books
                 WHERE category IS NOT NULL
                 AND category != ''
                 ORDER BY category ASC";

$category_result = $conn->query($category_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>EduLedger: LibSync - Book Management</title>

    <link rel="stylesheet"
          href="../css/admin-dashboard.css">

    <link rel="stylesheet"
          href="../css/admin-books.css">

</head>

<body>

<div class="dashboard-container">


    <!-- =========================
         SIDEBAR
    ========================== -->

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

            <a href="dashboard.php"
               class="nav-item">

                <span class="nav-icon">⌂</span>
                <span>Dashboard</span>

            </a>


            <a href="students.php"
               class="nav-item">

                <span class="nav-icon">♟</span>
                <span>Student Management</span>

            </a>


            <a href="books.php"
               class="nav-item active">

                <span class="nav-icon">▣</span>
                <span>Book Management</span>

            </a>


            <a href="borrow.php"
               class="nav-item">

                <span class="nav-icon">↔</span>
                <span>Borrowing Records</span>

            </a>


            <a href="return.php"
               class="nav-item">

                <span class="nav-icon">↩</span>
                <span>Return Records</span>

            </a>


            <a href="search.php"
               class="nav-item">

                <span class="nav-icon">⌕</span>
                <span>Search & View</span>

            </a>


            <a href="overdue.php"
               class="nav-item">

                <span class="nav-icon">◷</span>
                <span>Overdue Monitoring</span>

            </a>


            <a href="reports.php"
               class="nav-item">

                <span class="nav-icon">▥</span>
                <span>Reports & Analytics</span>

            </a>


            <a href="logs.php"
               class="nav-item">

                <span class="nav-icon">☷</span>
                <span>Activity Logs</span>

            </a>

        </nav>


        <div class="sidebar-bottom">

            <a href="../logout.php"
               class="nav-item">

                <span class="nav-icon">↪</span>
                <span>Logout</span>

            </a>

        </div>

    </aside>



    <!-- =========================
         MAIN CONTENT
    ========================== -->

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



        <!-- =========================
             PAGE CONTENT
        ========================== -->

        <section class="page-content">


            <!-- PAGE HEADER -->

            <div class="page-header">

                <div class="page-title">

                    <div class="page-title-icon">
                        📚
                    </div>

                    <div>

                        <h1>
                            Book Records
                        </h1>

                        <p>
                            Manage library books and their availability.
                        </p>

                    </div>

                </div>


               <a href="add_books.php" class="add-book-btn">
                    + Add Book
                </a>

            </div>



            <!-- =========================
                 SEARCH & FILTER
            ========================== -->

<form method="GET" class="book-tools">

    <div class="search-box">
        <span class="search-icon">🔍</span>

        <input
            type="text"
            name="search"
            placeholder="Search by book ID, title, author, or category..."
            value="<?php echo htmlspecialchars($search); ?>"
        >
    </div>

    <div class="category-filter">
        <select name="category" onchange="this.form.submit()">

            <option value="">All Categories</option>

            <?php while ($cat = $category_result->fetch_assoc()): ?>
                <option
                    value="<?php echo htmlspecialchars($cat["category"]); ?>"
                    <?php
                    if ($category === $cat["category"]) {
                        echo "selected";
                    }
                    ?>
                >
                    <?php echo htmlspecialchars($cat["category"]); ?>
                </option>
            <?php endwhile; ?>

        </select>
    </div>

    <button type="submit" hidden></button>

</form>


            <!-- =========================
                 BOOK TABLE
            ========================== -->

            <div class="book-table-card">

                <div class="table-container">

                    <table class="book-table">

                        <thead>

                            <tr>

                                <th>
                                    Book ID
                                </th>

                                <th>
                                    Title
                                </th>

                                <th>
                                    Author
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php while ($book = $result->fetch_assoc()): ?>

                            <tr>


                                <!-- BOOK ID -->

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $book["book_id"]
                                    );
                                    ?>
                                </td>


                                <!-- TITLE -->

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $book["title"]
                                    );
                                    ?>
                                </td>


                                <!-- AUTHOR -->

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $book["author"]
                                    );
                                    ?>
                                </td>


                                <!-- CATEGORY -->

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $book["category"]
                                    );
                                    ?>
                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span class="book-status">

                                        <?php
                                        echo htmlspecialchars(
                                            $book["status"]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="book-actions">


                                        <a
                                            href="edit_book.php?id=<?php echo $book["id"]; ?>"
                                            class="edit-btn"
                                        >
                                            Edit
                                        </a>


                                        <a
                                            href="delete_book.php?id=<?php echo $book["id"]; ?>"
                                            class="delete-btn"
                                            onclick="return confirm('Are you sure you want to delete this book?');"
                                        >
                                            Delete
                                        </a>


                                    </div>

                                </td>


                            </tr>

                        <?php endwhile; ?>


                        </tbody>

                    </table>

                </div>


                <!-- PAGINATION WILL GO HERE LATER -->

                <div class="pagination">

                    <!--
                    Pagination will be added
                    after Search and Filter.
                    -->

                </div>

            </div>



            <!-- BACK TO DASHBOARD -->

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