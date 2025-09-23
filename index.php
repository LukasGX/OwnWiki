<?php
session_start();

require 'vendor/autoload.php';
include_once("backend/include.php");
include_once("conf-glob/html-titlebar.php");
include_once("backend/dbc.php");
include_once("backend/autouser.php");

$f = isset($_GET["f"]) ? htmlspecialchars(strip_tags($_GET["f"])) : "";

try {
    $routingData = routing();
    $json = getJSON($routingData);
}
catch (Exception $e) {
    header("Location: ?f=special:404&t=$f");
    exit;
}

// gen user object
if (isset($_SESSION["id"])) 
    $user = autoUser($_SESSION["id"], $conn);
else
    $user = dummyUser();

$userId = $user->getId();

if (!$user->hasPermission(getAccessPermission($json))[0]) {
    header("Location: ?f=special:403&t=$f&r=" . urlencode(getAccessPermission($json)));
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Own wiki</title>

    <?php echo getPageContents("conf-glob/html-include.html", $user); ?>
</head>
<body>
    <?php echo getPageContents("conf-glob/html-head.html", $user); ?>
    <div class="msplit">
        <?php echo getPageContents("conf-glob/html-side.html", $user); ?>
        <div class="content">
            <h1 class="page_title"><?php echo getTitle($json, $routingData); ?></h1>
            <?php if (!noControls($json)) echo getTitleBar($user, getProtectedStatus($json), $routingData[0]); ?>
            <?php echo getHtml($routingData, $user, $json); ?>
        </div>
    </div>
    
    <!-- jquery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.min.js"></script>
    <!-- flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/de.js"></script>
    <!-- own scripts -->
    <script>
        const ME_ID = "<?php echo $userId == null ? "": $userId; ?>";
    </script>
    <script src="js/sel2.js"></script>
    <script src="js/codehighlight.js"></script>
    <script src="js/manipulate_datetime.js"></script>
</body>
</html>