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


$ruleID = htmlspecialchars(strip_tags($input['rule-id'] ?? ""));
$ruleName = htmlspecialchars(strip_tags($input['rule-name'] ?? null));
$active = htmlspecialchars(strip_tags($input['rule-active'] ?? 'false'));
$type = htmlspecialchars(strip_tags($input['pattern-type'] ?? null));
$check = htmlspecialchars(strip_tags($input['pattern-check'] ?? null));
$threshold = htmlspecialchars(strip_tags($input['pattern-threshold'] ?? null));
$actionType = htmlspecialchars(strip_tags($input['action-type'] ?? null));
$actionMessage = htmlspecialchars(strip_tags($input['action-message'] ?? null));
$words = $input['pattern-words'] ?? [];

if (!is_array($words)) {
    $words = json_decode($words, true) ?? [];
}

$ruleFilePath = __DIR__ . '/../helper/rules/' . $ruleID . '.json';
$ruleData = [];

$ruleData['id'] = $ruleID;
$ruleData['name'] = $ruleName ?? $ruleID;
$ruleData['enabled'] = $active === 'true' ? true : false;
if ($type !== null) $ruleData['pattern']['type'] = $type;
if ($check !== null) $ruleData['pattern']['check'] = $check;
if ($threshold !== null) $ruleData['pattern']['threshold'] = is_numeric($threshold) ? (float)$threshold : $threshold;
if (!empty($words)) $ruleData['pattern']['words'] = $words;
if ($actionType !== null) $ruleData['action']['type'] = $actionType;
if ($actionMessage !== null) $ruleData['action']['message'] = $actionMessage;

file_put_contents($ruleFilePath, json_encode($ruleData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode(["success" => true]);
?>