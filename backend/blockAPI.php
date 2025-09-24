<?php
if (!$_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

include_once("dbc.php");

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['target'])) {
    http_response_code(400);
    echo json_encode(["error" => "Bad Request"]);
    exit;
}

$adminId = $input["meId"];
$target = $input['target'];
$target_id;
$scope = $input['scope'];
$optCreateAccounts = $input['optCreateAccounts'];
$optSendEmails = $input['optSendEmails'];
$optOwnDiscussion = $input['optOwnDiscussion'];
$durationUntil = $input['durationUntil'];
$reason = $input['reason'];

$dateTime = DateTime::createFromFormat('d.m.Y H:i', $durationUntil);
if ($dateTime !== false) {
    $mysqlFormat = $dateTime->format('Y-m-d H:i:s');
}

$sql = $conn->prepare("SELECT id FROM users WHERE username = ?");
$sql->bind_param("s", $target);
$sql->execute();
$result = $sql->get_result();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $target_id = $row["id"];
    }
}
else {
    echo json_encode(["error" => "Target User Not Found"]);
    exit;
}
$sql->close();

$sql = $conn->prepare("INSERT INTO blocks (createdAt, adminId, targetId, scope, optCreateAccounts, optSendEmails, optOwnDiscussion, durationUntil, reason) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?)");
$sql->bind_param("ssssssss", $adminId, $target_id, $scope, $optCreateAccounts, $optSendEmails, $optOwnDiscussion, $mysqlFormat, $reason);
$sql->execute();

echo json_encode(["success" => "success"]);
?>