<?php
include_once("backend/include.php");
$routingData = routing();
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
            <h1 class="page_title"><?php echo getTitle($routingData); ?></h1>
            <div class="titlebar">
                <div class="group">
                    <span class="element active"><i class="fas fa-file"></i> Artikel</span>
                    <span class="element"><i class="fas fa-comment"></i> Diskussion</span>
                    <span class="element"><i class="far fa-star"></i></span>
                </div>
                <div class="group">
                    <span class="element"><i class="fas fa-pen"></i> Bearbeiten</span>
                    <span class="element"><i class="fas fa-clock"></i> Versionsgeschichte</span>
                </div>
            </div>
            <?php echo getHtml($routingData); ?>
        </div>
    </div>
</body>
</html>