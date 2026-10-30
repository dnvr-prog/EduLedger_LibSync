<?php
session_start();
require_once "config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST["username"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        if ($password == $user["password"]) {

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];

            if ($user["role"] == "admin") {
                header("Location: admin/dashboard.php");
                exit();
            }

            if ($user["role"] == "student") {

               $_SESSION["student_id"] = $user["student_id"];
               
                header("Location: student/dashboard.php");
                exit();
            }

        } else {
            $message = "Incorrect password.";
        }

    } else {
        $message = "Username not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduLedger: LibSync - Login</title>

    <!-- External CSS -->
    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <!-- LEFT SIDE: BRANDING -->

    <div class="brand-container">

        <img
            src="assets/uep_logo.png"
            alt="UEP Logo"
            class="logo"
        >

        <h1 class="main-title">
            Edu<span>Ledger</span>
        </h1>

        <h2 class="subtitle">
            LibSync
        </h2>

        <div class="tagline-box">

            DIGITAL LIBRARY RECORDS SYSTEM

        </div>

        <p class="description">

            A simple and efficient library record system<br>
            for the University of Eastern Pangasinan.

        </p>

    </div>


    <!-- RIGHT SIDE: LOGIN -->

    <div class="login-container">

        <div class="login-card">

            <h2>
                Login
            </h2>

            <p>
                Access your library records
            </p>


            <form method="POST">

                <!-- Username -->

                <div class="input-group">

                    <input
                        type="text"
                        name="username"
                        placeholder="Username"
                        required
                    >

                </div>


                <!-- Password -->

                <div class="input-group">

                    <input
                        type="password"
                        name="password"
                        placeholder="Password"
                        required
                    >

                </div>


                <!-- Login Button -->

                <button
                    type="submit"
                    class="login-btn"
                >
                    Login
                </button>

            </form>


            <!-- Error Message -->

            <?php if ($message != ""): ?>

                <p class="error-message">
                    <?php echo $message; ?>
                </p>

            <?php endif; ?>


            <!-- Divider -->

            <div class="divider">
                OR
            </div>


            <!-- Student Registration -->

            <a
                href="register.php"
                class="back-home"
            >
                Create Student Account
            </a>

        </div>

    </div>


    <!-- TOP TAGLINE -->

    <div class="vertical-tagline">

        LEARN • GROW • BUILD YOUR FUTURE

    </div>


    <!-- FOOTER -->

    <div class="footer-text">

        University of Eastern Pangasinan

    </div>

</body>

</html>