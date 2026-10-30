<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

// DELETE STUDENT
if (isset($_GET["delete"])) {

    $id = $_GET["delete"];

    // Get the student's Student ID first
    $getStudent = $conn->prepare(
        "SELECT student_id FROM students WHERE id = ?"
    );

    $getStudent->bind_param("i", $id);
    $getStudent->execute();

    $result = $getStudent->get_result();

    if ($result->num_rows == 1) {

        $student = $result->fetch_assoc();
        $student_id = $student["student_id"];

        // Delete login account connected to student
        $deleteUser = $conn->prepare(
            "DELETE FROM users WHERE student_id = ?"
        );

        $deleteUser->bind_param("s", $student_id);
        $deleteUser->execute();

        // Delete student record
        $deleteStudent = $conn->prepare(
            "DELETE FROM students WHERE id = ?"
        );

        $deleteStudent->bind_param("i", $id);
        $deleteStudent->execute();

        // Activity log
        $action = "Deleted student " . $student_id;

        $log = $conn->prepare(
        "INSERT INTO activity_logs (user_id, action)
        VALUES (?, ?)"
        );

        $log->bind_param(
        "is",
        $_SESSION["user_id"],
        $action
        );

        $log->execute();
    }

    header("Location: students.php");
    exit();
}


// SEARCH AND FILTER
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$course = isset($_GET["course"]) ? trim($_GET["course"]) : "";

$sql = "SELECT * FROM students WHERE 1=1";

if ($search != "") {
    $sql .= " AND (
        student_id LIKE '%$search%'
        OR full_name LIKE '%$search%'
        OR course LIKE '%$search%'
    )";
}

if ($course != "") {
    $sql .= " AND course = '$course'";
}

$sql .= " ORDER BY id DESC";

// PAGINATION
$limit = 10;

$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;

// COUNT TOTAL RESULTS
$countSql = str_replace(
    "SELECT * FROM students",
    "SELECT COUNT(*) AS total FROM students",
    $sql
);

$countSql = str_replace(
    " ORDER BY id DESC",
    "",
    $countSql
);

$countResult = $conn->query($countSql);
$countData = $countResult->fetch_assoc();

$totalStudents = $countData["total"];
$totalPages = ceil($totalStudents / $limit);

// GET CURRENT PAGE
$sql .= " LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduLedger: LibSync - Student Management</title>

    <link rel="stylesheet" href="../css/admin-students.css">

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

            <a href="dashboard.php" class="nav-item">

                <span class="nav-icon">⌂</span>

                <span>Dashboard</span>

            </a>


            <a href="students.php" class="nav-item active">

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

                <div class="admin-header-profile">

                    <div class="admin-avatar">
                        👤
                    </div>

                    <div class="admin-info">

                        <strong>
                            <?php echo htmlspecialchars($_SESSION["username"]); ?>
                        </strong>

                        <span>Administrator</span>

                    </div>

                    <span class="profile-arrow">
                        ▾
                    </span>

                </div>

            </div>

        </header>



        <!-- =========================
             STUDENT CONTENT
        ========================== -->

        <section class="page-content">


            <!-- PAGE HEADER -->

            <div class="page-header">

                <div class="page-title">

                    <div class="page-title-icon">
                        👤
                    </div>

                    <div>

                        <h1>Student Records</h1>

                        <p>
                            Manage registered students and their information.
                        </p>

                    </div>

                </div>


                <a href="add_student.php" class="add-student-btn">
                    + Add Student
                </a>

            </div>



            <!-- SEARCH & FILTER -->

            
<form method="GET" class="student-tools">

    <div class="search-box">
        <span class="search-icon">🔍</span>

        <input
            type="text"
            name="search"
            placeholder="Search by student ID, name, or course..."
            value="<?php echo htmlspecialchars($search); ?>"
        >
    </div>

    <div class="course-filter">
        <select name="course" onchange="this.form.submit()">
            <option value="">All Courses</option>
            <option value="BSIT" <?php if ($course == "BSIT") echo "selected"; ?>>BSIT</option>
            <option value="BSBA" <?php if ($course == "BSBA") echo "selected"; ?>>BSBA</option>
            <option value="BSED" <?php if ($course == "BSED") echo "selected"; ?>>BSED</option>
            <option value="BEED" <?php if ($course == "BEED") echo "selected"; ?>>BEED</option>
        </select>
    </div>

    <button type="submit" style="display:none;"></button>

</form>



            <!-- STUDENT TABLE -->

            <div class="student-table-card">

                <div class="table-container">

                    <table class="student-table">

                        <thead>

                            <tr>

                                <th>Student ID</th>

                                <th>Full Name</th>

                                <th>Course</th>

                                <th>Year Level</th>

                                <th>Email</th>

                                <th>Status</th>

                                <th>Actions</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php while ($student = $result->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?php echo htmlspecialchars($student["student_id"]); ?>
                                </td>


                                <td>
                                    <?php echo htmlspecialchars($student["full_name"]); ?>
                                </td>


                                <td>
                                    <?php echo htmlspecialchars($student["course"]); ?>
                                </td>


                                <td>
                                    <?php echo htmlspecialchars($student["year_level"]); ?>
                                </td>


                                <td>
                                    <?php echo htmlspecialchars($student["email"]); ?>
                                </td>


                                <td>

                                    <span class="student-status active">

                                        <?php echo htmlspecialchars($student["status"]); ?>

                                    </span>

                                </td>


                                <td>

                                    <div class="student-actions">

                                        <a href="edit_student.php?id=<?php echo $student["id"]; ?>"
                                                   class="edit-btn">
                                                        Edit
                                        </a>


                                        <a
                                            href="students.php?delete=<?php echo $student["id"]; ?>"
                                            class="delete-btn"
                                            onclick="return confirm('Delete this student?');"
                                        >
                                            Delete
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>


                        </tbody>

                    </table>
                    
                    <div class="pagination">

    <?php if ($page > 1): ?>
        <a href="?search=<?php echo urlencode($search); ?>&course=<?php echo urlencode($course); ?>&page=<?php echo $page - 1; ?>">
            ← Previous
        </a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>

        <a
            href="?search=<?php echo urlencode($search); ?>&course=<?php echo urlencode($course); ?>&page=<?php echo $i; ?>"
            class="<?php echo ($page == $i) ? 'active' : ''; ?>"
        >
            <?php echo $i; ?>
        </a>

    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a href="?search=<?php echo urlencode($search); ?>&course=<?php echo urlencode($course); ?>&page=<?php echo $page + 1; ?>">
            Next →
        </a>
    <?php endif; ?>

</div>
                </div>

            </div>

        </section>


    </main>

</div>


</body>

</html>