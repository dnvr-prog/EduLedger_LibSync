<?php
session_start();
require_once "../config.php";
require_once __DIR__ . "/../includes/mailer.php";

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: ../index.php");
    exit();
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $student_id = trim($_POST["student_id"] ?? "");
    $full_name = trim($_POST["full_name"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $year_level = trim($_POST["year_level"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (
        $student_id === "" ||
        $full_name === "" ||
        $course === "" ||
        $year_level === "" ||
        $email === "" ||
        $username === "" ||
        $password === ""
    ) {
        $message = "Please complete all required fields.";
        $messageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    } else {

        // Check Student ID
        $checkStudent = $conn->prepare(
            "SELECT id FROM students WHERE student_id = ?"
        );
        $checkStudent->bind_param("s", $student_id);
        $checkStudent->execute();
        $studentResult = $checkStudent->get_result();

        if ($studentResult->num_rows > 0) {

            $message = "Student ID already exists.";
            $messageType = "error";

        } else {

            // Check username
            $checkUser = $conn->prepare(
                "SELECT id FROM users WHERE username = ?"
            );
            $checkUser->bind_param("s", $username);
            $checkUser->execute();
            $userResult = $checkUser->get_result();

            if ($userResult->num_rows > 0) {

                $message = "Username already exists.";
                $messageType = "error";

            } else {

                try {
                    $conn->begin_transaction();

                    // Add student record
                    $studentStatus = "Active";

                    $insertStudent = $conn->prepare(
                        "INSERT INTO students
                        (student_id, full_name, course, year_level, email, status)
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    $insertStudent->bind_param(
                        "ssssss",
                        $student_id,
                        $full_name,
                        $course,
                        $year_level,
                        $email,
                        $studentStatus
                    );

                    $insertStudent->execute();

                    // Add student login account
                    $role = "student";
                    $userStatus = "Active";

                    $insertUser = $conn->prepare(
                        "INSERT INTO users
                        (student_id, username, password, role, status)
                        VALUES (?, ?, ?, ?, ?)"
                    );

                    $insertUser->bind_param(
                        "sssss",
                        $student_id,
                        $username,
                        $password,
                        $role,
                        $userStatus
                    );

                    $insertUser->execute();

                    // Record admin activity
                    $action = "Added student " . $student_id;

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

                    // Commit only after database operations succeed
                    $conn->commit();

                    // Send confirmation email after saving
                    $subject = "Your EduLedger: LibSync Account";

                    $body = "Hello " . $full_name . ",\n\n"
                        . "Your student account has been created by the "
                        . "library administrator.\n\n"
                        . "Student ID: " . $student_id . "\n"
                        . "Course: " . $course . "\n"
                        . "Year Level: " . $year_level . "\n"
                        . "Username: " . $username . "\n\n"
                        . "You may now log in to EduLedger: LibSync.\n\n"
                        . "Thank you,\n"
                        . "EduLedger: LibSync Library System";

                    $emailSent = sendSystemEmail(
                        $email,
                        $full_name,
                        $subject,
                        $body
                    );

                    if ($emailSent) {
                        $_SESSION["student_message"] =
                            "Student added successfully. Confirmation email sent.";
                    } else {
                        $_SESSION["student_message"] =
                            "Student added successfully, but the confirmation email failed. Check the mail configuration.";
                    }

                    header("Location: students.php");
                    exit();

                } catch (Throwable $e) {

                    $conn->rollback();

                    error_log(
                        "Admin add-student error: " . $e->getMessage()
                    );

                    $message = "Unable to add student. Please check the details and try again.";
                    $messageType = "error";
                }
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduLedger: LibSync - Add Student</title>

    <link rel="stylesheet" href="../css/admin-dashboard.css">
    <link rel="stylesheet" href="../css/admin-students.css">
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


        <!-- PAGE CONTENT -->
        <section class="page-content">

            <div class="page-header">

                <div class="page-title">

                    <div class="page-title-icon">
                        👤
                    </div>

                    <div>

                        <h1>Add Student</h1>

                        <p>
                            Register a new student and create their library account.
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

                        <p>Enter the student's basic information.</p>

                    </div>


                    <div class="form-grid">

                        <div class="form-group">

                            <label>Student ID</label>

                            <input
                                type="text"
                                name="student_id"
                                placeholder="Enter student ID"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>Full Name</label>

                            <input
                                type="text"
                                name="full_name"
                                placeholder="Enter full name"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>Course</label>

                            <select name="course" required>

                                <option value="">
                                    Select course
                                </option>

                                <option value="BSIT">
                                    BSIT
                                </option>

                                <option value="BSCS">
                                    BSCS
                                </option>

                                <option value="BSED">
                                    BSED
                                </option>

                                <option value="BSCRIM">
                                    BSCRIM
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>Year Level</label>

                            <select name="year_level" required>

                                <option value="">
                                    Select year level
                                </option>

                                <option value="1st Year">
                                    1st Year
                                </option>

                                <option value="2nd Year">
                                    2nd Year
                                </option>

                                <option value="3rd Year">
                                    3rd Year
                                </option>

                                <option value="4th Year">
                                    4th Year
                                </option>

                            </select>

                        </div>


                        <div class="form-group full-width">

                            <label>Email</label>

                            <input
                                type="email"
                                name="email"
                                placeholder="Enter student email"
                                required
                            >

                        </div>

                    </div>

                </div>


                <!-- LOGIN ACCOUNT -->

                <div class="form-card">

                    <div class="form-card-header">

                        <h2>Login Account</h2>

                        <p>
                            Create the student's account for system access.
                        </p>

                    </div>


                    <div class="form-grid">

                        <div class="form-group">

                            <label>Username</label>

                            <input
                                type="text"
                                name="username"
                                placeholder="Enter username"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>Password</label>

                            <input
                                type="password"
                                name="password"
                                placeholder="Enter password"
                                required
                            >

                        </div>

                    </div>

                </div>


                <!-- FORM ACTIONS -->

                <div class="form-actions">

                    <a href="students.php" class="cancel-btn">
                        Cancel
                    </a>

                    <button type="submit" class="save-student-btn">
                        + Save Student
                    </button>

                </div>

            </form>

        </section>

    </main>

</div>

</body>
</html>