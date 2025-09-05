<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['rule-id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Bad Request"]);
    exit;
}

$ruleID = htmlspecialchars(strip_tags($input['rule-id']));

$path = "../helper/rules/$ruleID.json";
if (!file_exists($path)) {
    http_response_code(404);
    echo json_encode(["error" => "Rule not found"]);
    exit;
}
$ruleContent = file_get_contents($path);
echo $ruleContent;
?>