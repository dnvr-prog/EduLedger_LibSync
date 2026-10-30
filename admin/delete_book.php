<?php

session_start();
require_once "../config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET["id"])) {

    $id = $_GET["id"];

    $sql = $conn->prepare("DELETE FROM books WHERE id = ?");
    $sql->bind_param("i", $id);
    $sql->execute();
}

header("Location: books.php");
exit();

?>