<?php
session_start();
include_once("backend/include.php");
include_once("conf-glob/html-titlebar.php");
try {
    $routingData = routing();
    $json = getJSON($routingData);
}
catch (Exception $e) {
    header("Location: ?f=special:404");
    exit;
}

// gen user object
if (isset($_SESSION["username"])) {
    include_once("backend/user.php");
    $user = new User(
        $_SESSION["username"],
        $_SESSION["firstname"] ?? "",
        $_SESSION["lastname"] ?? "",
        $_SESSION["email"] ?? "",
        $_SESSION["role"] ?? "2"
    );
}
else {
    $user = new User("", "", "", "", "1");
}

echo $_SESSION["role"] . ": ";
echo $user->hasPermission("read") === true ? "JA" : "NEIN";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Own wiki</title>

    <?php echo getPageContents("conf-glob/html-include.html"); ?>
</head>
<body>
    <?php echo getPageContents("conf-glob/html-head.html"); ?>
    <div class="msplit">
        <?php echo getPageContents("conf-glob/html-side.html"); ?>
        <div class="content">
            <h1 class="page_title"><?php echo getTitle($json, $routingData); ?></h1>
            <?php if (!noControls($json)) echo getTitleBar($user, getProtectedStatus($json)); ?>
            <?php echo getHtml($routingData); ?>
        </div>
    </div>
</body>
</html>