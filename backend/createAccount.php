<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../index.php");
    exit;
}
include_once("dbc.php");
include_once("user.php");

if (!isset($_POST["username"])) {
    echo "Username is required.";
    exit;
}

if (!isset($_POST["pw"])) {
    echo "Password is required.";
    exit;
}

$username = htmlspecialchars(strip_tags($_POST["username"]));
$firstname = htmlspecialchars(strip_tags($_POST["firstname"] ?? ''));
$lastname = htmlspecialchars(strip_tags($_POST["lastname"] ?? ''));
$email = htmlspecialchars(strip_tags($_POST["email"] ?? ''));
$password = htmlspecialchars(strip_tags($_POST["pw"]));
$hash = hash("sha256", $password);

$sql = $conn->prepare("SELECT * FROM users WHERE username = ?");
$sql->bind_param("s", $username);
$sql->execute();
$result = $sql->get_result();
if ($result->num_rows > 0) {
    echo "Username already exists.";
    exit;
}

$defaultRole = "2";

$sql = $conn->prepare("INSERT INTO users (username, firstname, lastname, email, `password`, `role`) VALUES (?, ?, ?, ?, ?, ?)");
$sql->bind_param("ssssss", $username, $firstname, $lastname, $email, $hash, $defaultRole);
$sql->execute();

// treat as logged in
$_SESSION["username"] = $username;
$_SESSION["firstname"] = $firstname;
$_SESSION["lastname"] = $lastname;
$_SESSION["email"] = $email;
$_SESSION["role"] = $defaultRole;
header("Location: ../index.php");
exit;
?>