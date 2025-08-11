<?php
use League\CommonMark\CommonMarkConverter;

function getPageContents($page, $user) {
    $template = file_get_contents($page);
    if (isset($_SESSION["username"])) {
        $template = str_replace("{loginstatus}", "loggedin", $template);
    } else {
        $template = str_replace("{loginstatus}", "loggedout", $template);
    }

    if ($user->getRole() == "5") {
        $template = str_replace("{isAdmin}", "admin", $template);
    } else {
        $template = str_replace("{isAdmin}", "noadmin", $template);
    }

    $template = preg_replace_callback(
        '/<!--PLACEHOLDER:([a-zA-Z0-9_\-\/\.]+)-->/',
        function ($matches) {
            $filePath = $matches[1];
            if (is_readable($filePath)) {
                $content = file_get_contents($filePath);

                $magicWords = [
                    'TITLE' => function() {
                        $f = isset($_GET["f"]) ? htmlspecialchars(strip_tags($_GET["f"])) : "";
                        return $f;
                    },
                    'USERNAME' => function() {
                        return isset($_SESSION["username"]) ? htmlspecialchars(strip_tags($_SESSION["username"])) : "";
                    },
                ];

                $content = preg_replace_callback(
                    '/\[\[\s*([A-Z0-9_]+)\s*\]\]/',
                    function ($matches) use ($magicWords) {
                        $word = strtoupper($matches[1]); 
                        if (isset($magicWords[$word])) {
                            return $magicWords[$word]();  
                        }
                        return '';
                    },
                    $content
                );

                return $content;
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

function genTOC($md) {
    preg_match_all('/^(#{2,3})\s+(.*)$/m', $md, $matches, PREG_SET_ORDER);

    $result = [];
    $currentH2 = null;

    foreach ($matches as $match) {
        $level = strlen($match[1]);
        $text = trim($match[2]);

        if ($level === 2) {
            $currentH2 = $text;
            $result[$currentH2] = [];
        } elseif ($level === 3 && $currentH2 !== null) {
            $result[$currentH2][] = $text;
        }
    }
    $toc = "<div class='toc'><p class='toc-h'>Inhaltsverzeichnis</p><ul>";
    foreach ($result as $h2 => $h3s) {
        $toc .= "<li><a href='#" . htmlspecialchars(strtolower(str_replace(' ', '-', $h2))) . "'>" . htmlspecialchars($h2) . "</a>";
        if (!empty($h3s)) {
            $toc .= "<ul>";
            foreach ($h3s as $h3) {
                $toc .= "<li><a href='#" . htmlspecialchars(strtolower(str_replace(' ', '-', $h3))) . "'>" . htmlspecialchars($h3) . "</a></li>";
            }
            $toc .= "</ul>";
        }
        $toc .= "</li>";
    }
    $toc .= "</ul></div>";

    return $toc;
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

    // config for functions
    $generateTOC = true;

    // args[0]
    $split = explode(":", $args[0]);
    $namespace = strtolower($split[0]);
    $filename = strtolower($split[1]);

    // Define Magic Words
    $magicWords = [
        'NOTOC' => function() use (&$generateTOC) {
            $generateTOC = false;
            return '';
        },
        'ASKEDSITE' => function() {
            $tried = isset($_GET["t"]) ? htmlspecialchars(strip_tags($_GET["t"])) : "";
            return $tried;
        },
        'VIOLATEDPERMISSION' => function() {
            $permission = isset($_GET["r"]) ? htmlspecialchars(strip_tags($_GET["r"])) : "";
            return $permission;
        },
        'RANDOMPAGE' => function() {
            $files = glob("pages/article/*.md");
            if (empty($files)) {
                return '';
            }
            $randomFile = $files[array_rand($files)];
            $randomFile = str_replace("pages/article/", "", $randomFile);
            $randomFile = str_replace(".md", "", $randomFile);
            return $randomFile;
        },
        'ASKCREATE' => function() use (&$user) {
            $tried = isset($_GET["t"]) ? htmlspecialchars(strip_tags($_GET["t"])) : "";
            $namespace = strtolower(explode(":", $tried)[0]);
            if ($namespace == "" || $namespace == "special" || $user->hasPermission("createpage") === false) {
                return '';
            }
            return '<a href="?f=special:create&t=' . urlencode($tried) . '">Seite erstellen</a>';
        },
        'LISTSPECIALPAGES' => function() use (&$user) {
            $gen = "";
            $files = glob("pages/special/*.md");
            if (empty($files)) {
                return '';
            }
            foreach ($files as $file) {
                $filename = basename($file, ".md");

                $config = @file_get_contents("pages/special/$filename.json");
                if ($config !== false) {
                    $data = json_decode($config, true);

                    $description = "";
                    $title = "";

                    if (isset($data['excludeFromSpecialPagesList']) && $data['excludeFromSpecialPagesList'] === true) {
                        continue;
                    }
                    if (isset($data['description']) && !empty($data['description'])) {
                        $description = $data['description'];
                    }
                    else {
                        $description = "Keine Beschreibung verfügbar.";
                    }

                    if (isset($data['title']) && !empty($data['title'])) {
                        $title = $data["title"];
                    }
                    else {
                        $title = $filename;
                    }

                    if (isset($data['accessPermission']) && !empty($data['accessPermission'])) {
                        if ($user->hasPermission($data['accessPermission'])) {
                            $permission = "<span class='hasPermission'><i class='fas fa-check'></i> " . $data["accessPermission"] . "</span>";
                        }
                        else {
                            $permission = "<span class='hasNotPermission'><i class='fas fa-xmark'></i> " . $data["accessPermission"] . "</span>";
                        }
                    }
                    else {
                        if ($user->hasPermission('read')) {
                            $permission = "<span class='hasPermission'><i class='fas fa-check'></i> read</span>";
                        }
                        else {
                            $permission = "<span class='hasNotPermission'><i class='fas fa-xmark'></i> read</span>";
                        }
                    }
                }
                else {
                    $description = "Keine Beschreibung verfügbar.";
                    $title = $filename;

                    if ($user->hasPermission('read')) {
                        $permission = "<span class='hasPermission'><i class='fas fa-check'></i> read</span>";
                    }
                    else {
                        $permission = "<span class='hasNotPermission'><i class='fas fa-xmark'></i> read</span>";
                    }
                }

                $gen .= '<div class="specialpage"><a href="?f=special:' . htmlspecialchars($filename) . '">' . htmlspecialchars($title) . '</a><span>' . $description . '</span>' . $permission . '</div>';
            }
            return '<ul>' . $gen . '</ul>';
        },
    ];

    // Replace Templates
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

    // Replace Magic Words
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

    // check for redirect
    if (preg_match('/^\$REDIRECT:([A-Za-z0-9_]+)\$/', $args[1], $matches)) {
        $redirectPage = strtolower($matches[1]);
        header("Location: ?f=article:$redirectPage");
        exit;
    }

    // parse
    $dirty_html = $converter->convertToHtml($args[1]);
    // Add id attribute directly to h2 and h3 in the HTML output
    $dirty_html = preg_replace_callback(
        '/<(h[23])>(.*?)<\/\1>/i',
        function ($matches) {
            $tag = $matches[1];
            $content = trim(strip_tags($matches[2]));
            $id = htmlspecialchars(strtolower(str_replace(' ', '-', $content)));
            return '<' . $tag . ' id="' . $id . '">' . $matches[2] . '</' . $tag . '>';
        },
        $dirty_html
    );
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.DefinitionID', 'custom-def-1');
    $config->set('HTML.DefinitionRev', 10);
    // Configuration for HTMLPurifier
    if ($namespace == "special") {
        $config->set('HTML.Allowed', 'div,i,h2,h3,h4,h5,h6,p,span,ul,ol,li,a,strong,em,br,img,table,tr,td,th,form,input,button,textarea');
        $config->set('HTML.AllowedAttributes', '*.class,*.style,a.href,a.title,h2.id,h3.id,h4.id,h5.id,h6.id,img.src,img.alt,img.title,input.name,input.value,input.type,input.placeholder,button.type,button.name,textarea.name,form.action,form.method');
    }
    else {
        $config->set('HTML.Allowed', 'div,i,h2,h3,h4,h5,h6,p,span,ul,ol,li,a,strong,em,br,img,table,tr,td,th');
        $config->set('HTML.AllowedAttributes', '*.class,*.style,a.href,a.title,h2.id,h3.id,h4.id,h5.id,h6.id,img.src,img.alt,img.title');
    }
    $config->set('Attr.EnableID', true);
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

    $toc = $generateTOC == true ? genTOC($args[1]) : "";

    return $toc . $clean_html;
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

function getAccessPermission($text) {
    $data = json_decode($text, true);
    return $data['accessPermission'] ?? "read";
}
?>