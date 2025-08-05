<?php
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


$route = isset($_GET["f"]) ? htmlspecialchars(strip_tags($_GET["f"])) : "";

if ($route === "") {
    header("Location: ?f=Main:Mainpage");
    exit;
}

$routes = [
    "Main:Mainpage",
    "Special:RandomPage",
    "Special:SpecialPages"
];

if (!in_array($route, $routes)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}
?>