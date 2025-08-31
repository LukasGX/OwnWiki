<?php
if (!$_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

include("../helper/autocheck.php");

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
    $checkResult = autoCheck(file_get_contents($filepathMD), $text);
    if ($checkResult[0] == "block") {
        if ($checkResult[2] != "") echo json_encode(["error" => "Not allowed", "rule" => $checkResult[1], "extraInfo" => $checkResult[2]]);
        else echo json_encode(["error" => "Not allowed", "rule" => $checkResult[1]]);
        exit;
    }

    file_put_contents($filepathMD, $text);
    echo json_encode(["success" => "success"]);
}
else {
    echo json_encode(["error" => "File not found"]);
    exit;
}
?>