<?php
if (!$_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['text']) || !isset($input['title'])) {
    http_response_code(400);
    echo json_encode(["error" => "Bad Request"]);
    exit;
}

$text = $input['text'];
$title = $input['title'];

$split = explode(":", $title);
$namespace = strtolower($split[0]);
$filename = strtolower($split[1]);
$filepathMD = "../pages/$namespace/$filename.md";
$filepathJSON = "../pages/$namespace/$filename.json";

if (!is_dir("../pages/$namespace")) {
    mkdir("../pages/$namespace", 0777, true);
}

file_put_contents($filepathMD, $text);

file_put_contents($filepathJSON, json_encode([
    "title" => $filename,
    "content" => $text,
    "noControls" => false,
    "protected" => "none"
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo json_encode(["success" => ""]);
?>