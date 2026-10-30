<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: books.php");
    exit();
}

$id = $_GET["id"];

$sql = $conn->prepare("SELECT * FROM books WHERE id = ?");
$sql->bind_param("i", $id);
$sql->execute();

$result = $sql->get_result();

if ($result->num_rows != 1) {
    header("Location: books.php");
    exit();
}

$book = $result->fetch_assoc();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $book_id = $_POST["book_id"];
    $title = $_POST["title"];
    $author = $_POST["author"];
    $category = $_POST["category"];
    $status = $_POST["status"];

    $update = $conn->prepare(
        "UPDATE books
         SET book_id = ?, title = ?, author = ?, category = ?, status = ?
         WHERE id = ?"
    );

    $update->bind_param(
        "sssssi",
        $book_id,
        $title,
        $author,
        $category,
        $status,
        $id
    );

    if ($update->execute()) {

    // Record activity
    $user_id = $_SESSION["user_id"];

    $action = "Updated book " . $book_id . " - " . $title;

    $log_sql = "
        INSERT INTO activity_logs (user_id, action)
        VALUES (?, ?)
    ";

    $log_stmt = $conn->prepare($log_sql);
    $log_stmt->bind_param("is", $user_id, $action);
    $log_stmt->execute();
    $log_stmt->close();
    
    header("Location: books.php");
    exit();

    } else {

    $message = "Error: " . $update->error;

    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Book</title>
</head>

<body>

<h1>EduLedger: LibSync</h1>

<h2>Edit Book</h2>

<form method="POST">

    <label>Book ID:</label><br>
    <input
        type="text"
        name="book_id"
        value="<?php echo htmlspecialchars($book["book_id"]); ?>"
        required
    >

    <br><br>

    <label>Book Title:</label><br>
    <input
        type="text"
        name="title"
        value="<?php echo htmlspecialchars($book["title"]); ?>"
        required
    >

    <br><br>

    <label>Author:</label><br>
    <input
        type="text"
        name="author"
        value="<?php echo htmlspecialchars($book["author"]); ?>"
        required
    >

    <br><br>

    <label>Category:</label><br>
    <input
        type="text"
        name="category"
        value="<?php echo htmlspecialchars($book["category"]); ?>"
        required
    >

    <br><br>

    <label>Status:</label><br>

    <select name="status">

        <option value="Available"
            <?php if ($book["status"] == "Available") echo "selected"; ?>>
            Available
        </option>

        <option value="Borrowed"
            <?php if ($book["status"] == "Borrowed") echo "selected"; ?>>
            Borrowed
        </option>

    </select>

    <br><br>

    <button type="submit">Update Book</button>

</form>

<p><?php echo $message; ?></p>

<br>

<a href="books.php">Back to Books</a>

</body>

</html>