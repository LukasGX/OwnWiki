<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

include_once("dbc.php");
require_once("user.php");

if (isset($_POST["page"], $_POST["newpage"], $_POST["suppressredirect"])) {
    $oldPage = htmlspecialchars(strip_tags($_POST["page"]));
    $newPage = htmlspecialchars(strip_tags($_POST["newpage"]));
    $suppressRedirect = htmlspecialchars(strip_tags($_POST["suppressredirect"]));

    $oldSplit = explode(":", $oldPage);
    $oldNamespace = strtolower($oldSplit[0]);
    $oldFilename = strtolower($oldSplit[1]);

    $oldMdPath = "../pages/$oldNamespace/$oldFilename.md";
    $oldJsonPath = "../pages/$oldNamespace/$oldFilename.json";

    if (!file_exists($oldMdPath)) {
        http_response_code(404);
        echo json_encode(["error" => "Page not found"]);
        exit;
    }

    $newSplit = explode(":", $newPage);
    $newNamespace = strtolower($newSplit[0]);
    $newFilename = strtolower($newSplit[1]);

    $newMdPath = "../pages/$newNamespace/$newFilename.md";
    $newJsonPath = "../pages/$newNamespace/$newFilename.json";

    if (!is_dir("../pages/$newNamespace")) {
        mkdir("../pages/$newNamespace", 0777, true);
    }

    $moveSuccess = true;

    if (!rename($oldMdPath, $newMdPath)) {
        $moveSuccess = false;
    }

    if (file_exists($oldJsonPath)) {
        if (!rename($oldJsonPath, $newJsonPath)) {
            $moveSuccess = false;
        }
    }

    if ($moveSuccess) {
        if ($suppressRedirect === "false") {
            if (!is_dir("../pages/$oldNamespace")) {
                mkdir("../pages/$oldNamespace", 0777, true);
            }

            $redirectContent = '$REDIRECT:' . $newPage . '$';
            file_put_contents($oldMdPath, $redirectContent);
        }

        echo json_encode(["success" => "Page moved successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to move page"]);
    }
}
?>