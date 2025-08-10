<?php
use League\CommonMark\CommonMarkConverter;

function getPageContents($page) {
    $template = file_get_contents($page);
    if (isset($_SESSION["username"])) {
        $template = str_replace("{loginstatus}", "loggedin", $template);
    } else {
        $template = str_replace("{loginstatus}", "loggedout", $template);
    }
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
    if ($text === false || $text === null) {
        throw new Exception("404 Not Found");
    }

    return [$route, $text];
}

function getHtml($args, $user) {
    // converter
    $conv_config = [
        'commonmark' => [
            'unordered_list_markers' => ['-', '+'],
        ],
        'html_input' => 'allow',
        'allow_unsafe_links' => false,
        'max_nesting_level' => PHP_INT_MAX,
    ];
    $converter = new CommonMarkConverter($conv_config);

    // args[0]
    $split = explode(":", $args[0]);
    $namespace = strtolower($split[0]);
    $filename = strtolower($split[1]);

    // 1. Define Magic Words
    $magicWords = [
        'NOINDEX' => function() {
            global $generateTOC;
            $generateTOC = false;
            return '';
        },
        'ASKEDSITE' => function() {
            $tried = isset($_GET["t"]) ? htmlspecialchars(strip_tags($_GET["t"])) : "";
            return $tried;
        },
        'ASKCREATE' => function() {
            global $user;
            $tried = isset($_GET["t"]) ? htmlspecialchars(strip_tags($_GET["t"])) : "";
            $namespace = strtolower(explode(":", $tried)[0]);
            if ($namespace == "" || $namespace == "special" || $user->hasPermission("createpage") === false) {
                return '';
            }
            return '<a href="?f=special:create&t=' . urlencode($tried) . '">Seite erstellen</a>';
        },
    ];

    // 2. Replace Templates
    $args[1] = preg_replace_callback(
        '/{{\s*([A-Za-z0-9_]+)\s*}}/',
        function ($matches) {
            $templateName = $matches[1];
            $templatePath = 'pages/Template/' . strtolower($templateName) . '.md';
            if (file_exists($templatePath)) {
                return file_get_contents($templatePath);
            } else {
                return 'File not found: ' . htmlspecialchars($templatePath);
            }
        },
        $args[1]
    );

    // 3. Replace Magic Words
    $args[1] = preg_replace_callback(
        '/\[\[\s*([A-Z0-9_]+)\s*\]\]/',
        function ($matches) use ($magicWords) {
            $word = strtoupper($matches[1]); 
            if (isset($magicWords[$word])) {
                return $magicWords[$word]();  
            }
            return '';
        },
        $args[1]
    );
    // parse
    $dirty_html = $converter->convertToHtml($args[1]);
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.DefinitionID', 'custom-def-1');
    $config->set('HTML.DefinitionRev', 6);
    // Configuration for HTMLPurifier
    if ($namespace == "special") {
        $config->set('HTML.Allowed', 'div,i,h2,h3,h4,h5,h6,p,span,ul,ol,li,a,strong,em,br,img,table,tr,td,th,form,input,button,textarea');
        $config->set('HTML.AllowedAttributes', '*.class,*.style,a.href,a.title,img.src,img.alt,img.title,input.name,input.value,input.type,input.placeholder,button.type,button.name,textarea.name,form.action,form.method');
    }
    else {
        $config->set('HTML.Allowed', 'div,i,h2,h3,h4,h5,h6,p,span,ul,ol,li,a,strong,em,br,img,table,tr,td,th');
        $config->set('HTML.AllowedAttributes', '*.class,*.style,a.href,a.title,img.src,img.alt,img.title');
    }
    $config->set('HTML.ForbiddenElements', ['b']);
    $config->set('CSS.AllowedProperties', [
        'color',
        'background-color',
        'text-align',
        'margin',
        'padding',
        'border'
    ]);
    $config->set('CSS.AllowImportant', false); // no !important

    if (($def = $config->maybeGetRawHTMLDefinition()) && $namespace == "special") {
        $def->addElement('input', 'Inline', 'Empty', 'Common', [
            'type'  => 'Text',
            'name'  => 'Text',
            'value' => 'Text',
        ]);
        $def->addAttribute('input', 'placeholder', 'Text');
        $def->addElement('button', 'Inline', 'Flow', 'Common', [
            'type' => 'Text',
            'name' => 'Text'
        ]);
        $def->addElement('textarea', 'Inline', 'Flow', 'Common', [
            'name' => 'Text'
        ]);
        $def->addElement('form', 'Block', 'Flow', 'Common', [
            'action*' => 'URI',
            'method'  => 'Text'
        ]);
    }

    $purifier = new HTMLPurifier($config);
    $clean_html = $purifier->purify($dirty_html);

    return $clean_html;
}

function getJSON($args) {
    $split = explode(":", $args[0]);
    $namespace = strtolower($split[0]);
    $filename = strtolower($split[1]);
    $filepath = "pages/$namespace/$filename.json";

    $text = @file_get_contents($filepath) ?? null;
    if ($text === false || $text === null) {
        header("HTTP/1.0 500 Internal Server Error");
        return "Untitled Page";
    }

    return $text;
}

function getTitle($text, $args) {
    $data = json_decode($text, true);
    if (isset($data['title'])) {
        return $data['title'];
    } else {
        return "Untitled Page";
    }
}

function noControls($text) {
    $data = json_decode($text, true);
    if (isset($data['noControls']) && $data['noControls'] === true) {
        return true;
    } else {
        return false;
    }
}

function getProtectedStatus($text) {
    $data = json_decode($text, true);
    return $data['protect'] ?? "none";
}
?>