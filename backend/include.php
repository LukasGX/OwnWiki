<?php
require 'vendor/autoload.php';

use League\CommonMark\CommonMarkConverter;

function getPageContents($page) {
    $template = file_get_contents($page);
    $template = preg_replace_callback(
        '/<!--PLACEHOLDER:([a-zA-Z0-9_\-\/\.]+)-->/',
        function ($matches) {
            $filePath = $matches[1];
            if (is_readable($filePath)) {
                return file_get_contents($filePath);
            } else {
                return "<!-- could not load $filePath -->";
            }
        },
        $template
    );
    return $template;
}

function routing() {
    $route = isset($_GET["f"]) ? htmlspecialchars(strip_tags($_GET["f"])) : "";

    if ($route === "") {
        header("Location: ?f=Main:Mainpage");
        exit;
    }

    $filename = strtolower(explode(":", $route)[1]);
    $filepath = "pages/" . $filename . ".md";

    $text = file_get_contents($filepath) ?? null;
    if ($text === null) {
        header("HTTP/1.0 404 Not Found");
        exit;
    }

    return [$route, $text];
}

function getHtml($args) {
    $converter = new CommonMarkConverter();
    return $converter->convertToHtml($args[1]);
}

function getTitle($args) {
    $filename = strtolower(explode(":", $args[0])[1]);
    $filepath = "pages/" . $filename . ".json";

    $text = @file_get_contents($filepath) ?? null;
    if ($text === false || $text === null) {
        header("HTTP/1.0 500 Internal Server Error");
        return "Untitled Page";
    }

    $data = json_decode($text, true);
    if (isset($data['title'])) {
        return $data['title'];
    } else {
        return "Untitled Page";
    }
}
?>