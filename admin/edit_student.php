<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

$message = "";

// GET STUDENT ID
if (!isset($_GET["id"])) {
    header("Location: students.php");
    exit();
}

$id = $_GET["id"];

// GET STUDENT INFORMATION
$getStudent = $conn->prepare(
    "SELECT * FROM students WHERE id = ?"
);

$getStudent->bind_param("i", $id);
$getStudent->execute();

$result = $getStudent->get_result();

if ($result->num_rows != 1) {
    header("Location: students.php");
    exit();
}

$student = $result->fetch_assoc();


// UPDATE STUDENT
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $student_id = trim($_POST["student_id"]);
    $full_name = trim($_POST["full_name"]);
    $course = trim($_POST["course"]);
    $year_level = trim($_POST["year_level"]);
    $email = trim($_POST["email"]);
    $status = trim($_POST["status"]);

    // Check duplicate Student ID
    $checkStudent = $conn->prepare(
        "SELECT id FROM students
         WHERE student_id = ?
         AND id != ?"
    );

    $checkStudent->bind_param(
        "si",
        $student_id,
        $id
    );

    $checkStudent->execute();

    $studentResult = $checkStudent->get_result();

    if ($studentResult->num_rows > 0) {

        $message = "Student ID already exists.";

    } else {

        // Get old Student ID
        $oldStudentId = $student["student_id"];

        // UPDATE STUDENT
        $updateStudent = $conn->prepare(
            "UPDATE students
             SET student_id = ?,
                 full_name = ?,
                 course = ?,
                 year_level = ?,
                 email = ?,
                 status = ?
             WHERE id = ?"
        );

        $updateStudent->bind_param(
            "ssssssi",
            $student_id,
            $full_name,
            $course,
            $year_level,
            $email,
            $status,
            $id
        );

        if ($updateStudent->execute()) {

            // Update linked user account
            $updateUser = $conn->prepare(
                "UPDATE users
                 SET student_id = ?,
                     status = ?
                 WHERE student_id = ?"
            );

            $updateUser->bind_param(
                "sss",
                $student_id,
                $status,
                $oldStudentId
            );

            $updateUser->execute();


            // Activity log
            $action = "Updated student " . $student_id;

            $log = $conn->prepare(
                "INSERT INTO activity_logs
                 (user_id, action)
                 VALUES (?, ?)"
            );

            $log->bind_param(
                "is",
                $_SESSION["user_id"],
                $action
            );

            $log->execute();


            header("Location: students.php");
            exit();

        } else {

            $message = "Failed to update student.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduLedger: LibSync - Edit Student</title>

    <link rel="stylesheet" href="../css/admin-dashboard.css">
    <link rel="stylesheet" href="../css/students.css">
    <link rel="stylesheet" href="../css/admin-edit_student.css">
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


    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- HEADER -->
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

                    <span class="profile-arrow">▾</span>

                </div>

            </div>

        </header>


        <!-- PAGE CONTENT -->
        <section class="page-content">

            <div class="page-header">

                <div class="page-title">

                    <div class="page-title-icon">
                        ✎
                    </div>

                    <div>

                        <h1>Edit Student</h1>

                        <p>
                            Update the student's information and account status.
                        </p>

                    </div>

                </div>

            </div>


            <?php if ($message != ""): ?>

                <div class="form-message">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>


            <form method="POST" class="student-form">


                <!-- STUDENT INFORMATION -->

                <div class="form-card">

                    <div class="form-card-header">

                        <h2>Student Information</h2>

                        <p>
                            Update the student's basic information.
                        </p>

                    </div>


                    <div class="form-grid">


                        <div class="form-group">

                            <label>Student ID</label>

                            <input
                                type="text"
                                name="student_id"
                                value="<?php echo htmlspecialchars($student["student_id"]); ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>Full Name</label>

                            <input
                                type="text"
                                name="full_name"
                                value="<?php echo htmlspecialchars($student["full_name"]); ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>Course</label>

                            <select name="course" required>

                                <option value="BSIT"
                                    <?php echo $student["course"] == "BSIT" ? "selected" : ""; ?>>
                                    BSIT
                                </option>

                                <option value="BSCS"
                                    <?php echo $student["course"] == "BSCS" ? "selected" : ""; ?>>
                                    BSCS
                                </option>

                                <option value="BSED"
                                    <?php echo $student["course"] == "BSED" ? "selected" : ""; ?>>
                                    BSED
                                </option>

                                <option value="BSCRIM"
                                    <?php echo $student["course"] == "BSCRIM" ? "selected" : ""; ?>>
                                    BSCRIM
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>Year Level</label>

                            <select name="year_level" required>

                                <option value="1st Year"
                                    <?php echo $student["year_level"] == "1st Year" ? "selected" : ""; ?>>
                                    1st Year
                                </option>

                                <option value="2nd Year"
                                    <?php echo $student["year_level"] == "2nd Year" ? "selected" : ""; ?>>
                                    2nd Year
                                </option>

                                <option value="3rd Year"
                                    <?php echo $student["year_level"] == "3rd Year" ? "selected" : ""; ?>>
                                    3rd Year
                                </option>

                                <option value="4th Year"
                                    <?php echo $student["year_level"] == "4th Year" ? "selected" : ""; ?>>
                                    4th Year
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>Email</label>

                            <input
                                type="email"
                                name="email"
                                value="<?php echo htmlspecialchars($student["email"]); ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>Status</label>

                            <select name="status" required>

                                <option value="Active"
                                    <?php echo $student["status"] == "Active" ? "selected" : ""; ?>>
                                    Active
                                </option>

                                <option value="Inactive"
                                    <?php echo $student["status"] == "Inactive" ? "selected" : ""; ?>>
                                    Inactive
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <!-- FORM ACTIONS -->

                <div class="form-actions">

                    <a href="students.php" class="cancel-btn">
                        Cancel
                    </a>

                    <button type="submit" class="save-student-btn">
                        Save Changes
                    </button>

                </div>


            </form>

        </section>

    </main>

</div>

</body>
</html>