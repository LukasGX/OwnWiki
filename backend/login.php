<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../index.php");
    exit;
}
include_once("dbc.php");
require_once("user.php");

if (isset($_POST["username"]) && isset($_POST["pw"])) {
    $username = htmlspecialchars(strip_tags($_POST["username"]));
    $password = htmlspecialchars(strip_tags($_POST["pw"]));
    $hash = hash("sha256", $password);

    $sql = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $sql->bind_param("s", $username);
    $sql->execute();
    $result = $sql->get_result();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $pwFromDB = $row["password"];
            if ($pwFromDB === $hash) {
                // logged in
                $_SESSION["id"] = $row["id"];
                header("Location: ../index.php");
            } else {
                echo "Wrong password.";
            }
        }
    }
    else {
        echo "User not found.";
    }
} else {
    echo "Username and password are required.";
}
?>