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

    $split = explode(":", $route);
    $namespace = strtolower($split[0]);
    $filename = strtolower($split[1]);
    $filepath = "pages/$namespace/$filename.md";

    $text = @file_get_contents($filepath) ?? null;
    if ($text === null) {
        header("HTTP/1.0 404 Not Found");
        exit;
    }

    return [$route, $text];
}

function getHtml($args) {
    $conv_config = [
        'commonmark' => [
            'unordered_list_markers' => ['-', '+'],
        ],
        'html_input' => 'allow',
        'allow_unsafe_links' => false,
        'max_nesting_level' => PHP_INT_MAX,
    ];

    $converter = new CommonMarkConverter($conv_config);

    // own rules
    $args[1] = preg_replace_callback('/{{\s*([A-Za-z0-9_]+)\s*}}/', function ($matches) {
        $templateName = $matches[1];
        $templatePath = 'pages/Template/' . strtolower($templateName) . '.md';
        if (file_exists($templatePath)) {
            return file_get_contents($templatePath);
        } else {
            return 'File not found: ' . htmlspecialchars($templatePath);
        }
    }, $args[1]);
    // parse
    $dirty_html = $converter->convertToHtml($args[1]);

    $config = HTMLPurifier_Config::createDefault();
    // Configuration for HTMLPurifier
    $config->set('HTML.Allowed', 'div,i,h2,h3,h4,h5,h6,p,span,ul,ol,li,a,strong,em,br,img,table,tr,td,th');
    $config->set('HTML.ForbiddenElements', ['b']);
    $config->set('HTML.AllowedAttributes', '*.class,*.style, a.href, a.title, img.src, img.alt, img.title');
    $config->set('CSS.AllowedProperties', [
        'color',
        'background-color',
        'text-align',
        'margin',
        'padding',
        'border'
    ]);
    $config->set('CSS.AllowImportant', false); // no !important
    $purifier = new HTMLPurifier($config);

    $clean_html = $purifier->purify($dirty_html);

    echo $clean_html;
}

function getTitle($args) {
    $split = explode(":", $args[0]);
    $namespace = strtolower($split[0]);
    $filename = strtolower($split[1]);
    $filepath = "pages/$namespace/$filename.json";

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