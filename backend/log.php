<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] != "GET") {
    header("Location: ../index.php");
    exit;
}
include_once("dbc.php");
include_once("user.php");

if (!isset($_GET["logType"])) {
    echo "Log type is required.";
    exit;
}

$logType = htmlspecialchars(strip_tags($_GET["logType"]));

$types = [
    "edit" => ["userId", "target", "newVersionId"],
    "move" => ["userId", "oldName", "newName"]
];

if (array_key_exists($logType, $types)) {
    $query = "INSERT INTO edit_logs (" . implode(", ", $types[$logType]) . ") VALUES (";
    foreach($types[$logType] as $needed) {
        if (!isset($_GET[$needed])) {
            die("$needed is required.");
        }

        $query .= "?, ";
    }

    $params = [];
    foreach ($types[$logType] as $paramName) {
        $params[] = isset($_GET[$paramName]) ? $_GET[$paramName] : '';
    }

    $query = substr($query, 0, -2);
    $query .= ")";

    $length = count($types[$logType]);
    $sString = str_repeat('s', $length);

    $sql = $conn->prepare($query);
    $sql->bind_param($sString, ...$params);
    $sql->execute();
}
else {
    echo "Not recognised";
}
?>