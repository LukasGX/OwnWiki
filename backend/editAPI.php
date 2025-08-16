<?php
if (!$_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['text']) || !isset($input['text'])) {
    http_response_code(400);
    echo json_encode(["error" => "Bad Request"]);
    exit;
}

$text = $input['text'];
$title = $input['title'];

$split = explode(":", $title);
$namespace = $split[0];
$filename = $split[1];

$filepathMD = "../pages/$namespace/$filename.md";

if (file_exists($filepathMD)) {
    file_put_contents($filepathMD, $text);
    echo json_encode(["success" => ""]);
}
else {
    echo json_encode(["error" => "File not found"]);
    exit;
}
?>