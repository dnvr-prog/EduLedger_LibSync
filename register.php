<?php

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/includes/mailer.php";

$message = "";
$messageType = "";

$student_id = "";
$full_name = "";
$course = "";
$year_level = "";
$email = "";
$username = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $student_id = trim($_POST["student_id"] ?? "");
    $full_name = trim($_POST["full_name"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $year_level = trim($_POST["year_level"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

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

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";
        $messageType = "error";

    } else {

        // Check if Student ID already exists
        $checkStudent = $conn->prepare(
            "SELECT student_id FROM students WHERE student_id = ?"
        );

        $checkStudent->bind_param("s", $student_id);
        $checkStudent->execute();

        $studentResult = $checkStudent->get_result();

        if ($studentResult->num_rows > 0) {

            $message = "Student ID already exists.";
            $messageType = "error";

        } else {

            // Check if Username already exists
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

                    // Save student and login account together
                    $conn->begin_transaction();

                    $studentSQL = $conn->prepare(
                        "INSERT INTO students
                        (student_id, full_name, course, year_level, email)
                        VALUES (?, ?, ?, ?, ?)"
                    );

                    $studentSQL->bind_param(
                        "sssss",
                        $student_id,
                        $full_name,
                        $course,
                        $year_level,
                        $email
                    );

                    $studentSQL->execute();

                    $userSQL = $conn->prepare(
                        "INSERT INTO users
                        (student_id, username, password, role)
                        VALUES (?, ?, ?, 'student')"
                    );

                    $userSQL->bind_param(
                        "sss",
                        $student_id,
                        $username,
                        $password
                    );

                    $userSQL->execute();

                    $conn->commit();

                    // Send confirmation email after successful registration
                    $subject = "Welcome to EduLedger: LibSync";

                    $body = "Hello " . $full_name . ",\n\n"
                        . "Your student account has been created successfully.\n\n"
                        . "Student ID: " . $student_id . "\n"
                        . "Course: " . $course . "\n"
                        . "Year Level: " . $year_level . "\n"
                        . "Username: " . $username . "\n\n"
                        . "You can now use your account to log in to "
                        . "EduLedger: LibSync.\n\n"
                        . "Thank you,\n"
                        . "EduLedger: LibSync Library System";

                    $emailSent = sendSystemEmail(
                        $email,
                        $full_name,
                        $subject,
                        $body
                    );

                    if ($emailSent) {
                        $message = "Account created successfully! "
                            . "A confirmation email has been sent. "
                            . "Please check your inbox and spam folder.";
                    } else {
                        $message = "Account created successfully, "
                            . "but the confirmation email could not be sent. "
                            . "Please check your email configuration.";
                    }

                    $messageType = "success";

                    // Clear fields after successful registration
                    $student_id = "";
                    $full_name = "";
                    $course = "";
                    $year_level = "";
                    $email = "";
                    $username = "";

                } catch (Throwable $e) {

                    $conn->rollback();

                    error_log(
                        "EduLedger registration failed: " . $e->getMessage()
                    );

                    $message = "Registration could not be completed. "
                        . "Please try again.";

                    $messageType = "error";
                }
            }
        }
    }
}

function registerEscape($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduLedger: LibSync - Register</title>
    <link rel="stylesheet" href="css/register.css">
</head>

<body>
    <div class="registration-container">
        <div class="registration-card">

            <div class="card-header">
                <h2>Create Student Account</h2>
                <p>
                    Join EduLedger: LibSync and get access to the library system.
                </p>
            </div>

            <?php if ($message !== ""): ?>
                <div class="registration-message <?= registerEscape($messageType) ?>"
                     role="status">
                    <?= registerEscape($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="registration-form">

                <div class="form-row">
                    <div class="form-group">
                        <label for="student-id">Student ID</label>
                        <input
                            type="text"
                            id="student-id"
                            name="student_id"
                            placeholder="e.g. 2026-12345"
                            value="<?= registerEscape($student_id) ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="full-name">Full Name</label>
                        <input
                            type="text"
                            id="full-name"
                            name="full_name"
                            placeholder="Enter your full name"
                            value="<?= registerEscape($full_name) ?>"
                            required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="course">Course</label>
                        <select id="course" name="course" required>
                            <option value="" disabled
                                <?= $course === "" ? "selected" : "" ?>>
                                Select your course
                            </option>
                            <option value="BSIT"
                                <?= $course === "BSIT" ? "selected" : "" ?>>
                                Bachelor of Science in Information Technology
                            </option>
                            <option value="BSCS"
                                <?= $course === "BSCS" ? "selected" : "" ?>>
                                Bachelor of Science in Computer Science
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="year-level">Year Level</label>
                        <select id="year-level" name="year_level" required>
                            <option value="" disabled
                                <?= $year_level === "" ? "selected" : "" ?>>
                                Select your year level
                            </option>
                            <?php
                            $yearOptions = [
                                "1st Year",
                                "2nd Year",
                                "3rd Year",
                                "4th Year"
                            ];

                            foreach ($yearOptions as $year):
                            ?>
                                <option value="<?= registerEscape($year) ?>"
                                    <?= $year_level === $year ? "selected" : "" ?>>
                                    <?= registerEscape($year) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Choose a username"
                            value="<?= registerEscape($username) ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Create a password"
                            required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <input
                            type="password"
                            id="confirm-password"
                            name="confirm_password"
                            placeholder="Confirm your password"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email address"
                            value="<?= registerEscape($email) ?>"
                            required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        Register
                    </button>
                </div>
            </form>

            <div class="form-footer">
                <div class="divider">OR</div>
                <p>
                    Already have an account?
                    <a href="index.php">Back to Login</a>
                </p>
            </div>

        </div>
    </div>
</body>
</html>