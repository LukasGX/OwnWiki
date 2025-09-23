<?php
if (!$_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['text'])) {
    http_response_code(400);
    echo json_encode(["error" => "Bad Request"]);
    exit;
}

$text = $input['text'];

require '../vendor/autoload.php';
include_once("include.php");
include_once("autouser.php");

// generate user object
$user = dummyUser();

$html = getHtml(["dummy:dummy", $text], $user);

echo json_encode([
    "success" => $html
]);
?>