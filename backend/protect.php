<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

include_once("dbc.php");
require_once("user.php");

if (isset($_POST["page"], $_POST["protection"])) {
    $page = htmlspecialchars(strip_tags($_POST["page"]));
    $protection = htmlspecialchars(strip_tags($_POST["protection"]));

    $split = explode(":", $page);
    $namespace = strtolower($split[0]);
    $filename = strtolower($split[1]);
    $filepathJSON = "../pages/$namespace/$filename.json";

    if (!is_dir("../pages/$namespace")) {
        mkdir("../pages/$namespace", 0777, true);
    }

    if (file_exists($filepathJSON)) {
        $jsonData = json_decode(file_get_contents($filepathJSON), true);
        $jsonData["protect"] = $protection;
        file_put_contents($filepathJSON, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo json_encode(["success" => "Protection updated successfully: $protection"]);
    } else {
        echo json_encode(["error" => "File does not exist."]);
    }
} else {
    echo json_encode(["error" => "Invalid request."]);
}
?>