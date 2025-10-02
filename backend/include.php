<?php
use League\CommonMark\CommonMarkConverter;

function getPageContents($page, $user) {
    $template = file_get_contents($page);
    if (isset($_SESSION["id"])) {
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

                include("dbc.php");

                $magicWords = [
                    'TITLE' => function() {
                        $f = isset($_GET["f"]) ? htmlspecialchars(strip_tags($_GET["f"])) : "";
                        return $f;
                    },
                    'USERNAME' => function() use ($conn) {
                        $id = $_SESSION["id"];

                        $sql = $conn->prepare("SELECT username FROM users WHERE id = ?");
                        $sql->bind_param("s", $id);
                        $sql->execute();
                        $result = $sql->get_result();
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                return $row["username"];
                            }
                        }
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
    $altFilepath = "pages/$namespace/$filename.php";

    $text = @file_get_contents($filepath) ?? false;
    $altText = @file_get_contents($altFilepath) ?? false;
    if ($text === false && $altText === false) {
        throw new Exception("404 Not Found");
    }

    $right = $text != false ? $text : $altText;

    return [$route, $right];
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

function editLogResults($row) {
    $gen = "
    <h2>Einträge aus dem Barbeitungslogbuch</h2>
    <div class='log'>
        Benutzer: " . $row["userId"] . "<br />
        Seite: <a href='?f=" . $row["target"] . "'>" . $row["target"] . "</a><br />
        Neue Version: " . $row["newVersionId"] . "
    </div>
    ";
    return $gen;
}

function getHtml($args, $user, $json) {
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

    $data = json_decode($json, true);
    if (isset($data["isPHP"]) && $data["isPHP"]) {
        ob_start();
        include("pages/$namespace/$filename.php");
        $html = ob_get_clean();
        return $html;
    }

    $validate = true;

    if (isset($data["NO-VALIDATION"]) && $data["NO-VALIDATION"] === true) {
        $validate = false;
    }

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
            return "article:" . $randomFile;
        },
        'ASKCREATE' => function() use (&$user) {
            $tried = isset($_GET["t"]) ? htmlspecialchars(strip_tags($_GET["t"])) : "";
            $namespace = strtolower(explode(":", $tried)[0]);
            if ($namespace == "" || $namespace == "special" || $user->hasPermission("createpage")[0] === false) {
                return '';
            }
            return '<a href="?f=special:create&t=' . urlencode($tried) . '">Seite erstellen</a>';
        },
        'LISTSPECIALPAGES' => function() use (&$user) {
            $gen = "";
            $files = glob("pages/special/*.json");
            if (empty($files)) {
                return '';
            }
            foreach ($files as $file) {
                $filename = basename($file, ".json");

                if (substr($filename, 0, 2) === "__") {
                    continue;
                }

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
                        $hp = $user->hasPermission($data['accessPermission']);
                        if ($hp[0]) {
                            $permission = "<span class='hasPermission'><i class='fas fa-check'></i> " . $data["accessPermission"] . "</span>";
                        }
                        else {
                            $permission = "<span class='hasNotPermission'><i class='fas fa-xmark'></i> " . $data["accessPermission"] . "</span>";
                            if ($hp[1] == "block") $permission .= "<span class='hasNotPermission'>Dieses Recht wurde dir durch eine Sperre entzogen.</span>";
                        }
                    }
                    else {
                        if ($user->hasPermission('read')[0]) {
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

                    if ($user->hasPermission('read')[0]) {
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
        'LOGSMENU' => function() use (&$user) {
            global $_GET;
            $type = isset($_GET["type"]) ? htmlspecialchars(strip_tags($_GET["type"])) : "edit";

            $editSelected = $type == "edit" ? "selected" : "";
            $moveSelected = $type == "move" ? "selected" : "";

            $menu = "
            <h2>Logbücher ansehen</h2>
            <form action='' method='get'>
                <input type='hidden' name='f' value='special:logs'>
                <select name='type'>
                    <option value='edit' " . $editSelected . ">Bearbeitungslogbuch</option>
                    <option value='move' " . $moveSelected . ">Verschiebungslogbuch</option>
                </select>
                <input type='text' name='user' placeholder='Benutzer'>
                <input type='text' name='page' placeholder='Seite'>
                <input type='submit' value='Logbuch ansehen'>
            </form>
            ";

            return '<div class="log-menu">' . $menu . '</div>';
        },
        'LOGS' => function() use (&$user) {
            global $conn;
            $type = htmlspecialchars(strip_tags($_GET["type"] ?? ""));
            $username = htmlspecialchars(strip_tags($_GET["user"] ?? ""));
            $pagename = htmlspecialchars(strip_tags($_GET["page"] ?? ""));

            if (!isset($conn) || $conn === null) {
                return "Database connection not initialized.";
            }

            $userId = null;
            // get userId
            $sql = $conn->prepare("SELECT id FROM users WHERE username=?");
            $sql->bind_param("s", $username);
            $sql->execute();
            $result = $sql->get_result();
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $userId = $row["id"];
                }
            }
            $sql->close();

            // get logs
            $types = [
                "edit" => "edit_logs"
            ];

            $toReturn = ""; 

            if (array_key_exists($type, $types)) {
                $table = $types[$type];
                $query = "SELECT * FROM `" . $conn->real_escape_string($table) . "`";
                $sql = $conn->query($query);
                if ($sql && $sql->num_rows > 0) {
                    while($row = $sql->fetch_assoc()) {
                        if ($type == "edit") $toReturn .= editLogResults($row);
                    }
                }
                else {
                    $toReturn = "No logs found";
                }
            }
            else {
                $toReturn = "Log type not found";
            }
            return '<div>' . $toReturn . '</div>';
        },
        'ALLPAGESMENU' => function() use (&$user) {
            $setNS = isset($_GET["ns"]) ? htmlspecialchars(strip_tags($_GET["ns"])) : null;

            $namespaces = [["all", "Alle Namensräume"]];
            foreach (glob("pages/*/__namespace__.json") as $nsFile) {
                $content = @file_get_contents($nsFile);
                if ($content === false) continue;
                $data = json_decode($content, true);
                $id = $data["id"];
                $ns = $data["name"];
                $namespaces[] = [$id, $ns];
            }

            $toReturn = "
            <h2>Alle Seiten</h2>
            <form action='#' method='get'>
                <input type='hidden' name='f' value='special:allpages'>
                <select name='ns'>
                    <option value=''>-- Bitte wählen --</option>
            ";
            foreach ($namespaces as $ns) {
                if ($setNS !== null && $setNS == $ns[0]) {
                    $toReturn .= "<option value='" . htmlspecialchars($ns[0]) . "' selected>" . htmlspecialchars(ucfirst($ns[1])) . "</option>";
                    continue;
                }
                $toReturn .= "<option value='" . htmlspecialchars($ns[0]) . "'>" . htmlspecialchars(ucfirst($ns[1])) . "</option>";
            }
            $toReturn .= "
                </select>
                <input type='submit' value='Anzeigen'>
            </form>
            ";
            return '<div class="allpagesmenu">' . $toReturn . '</div>';
        },
        'ALLPAGES' => function() use (&$user) {
            $setNS = isset($_GET["ns"]) ? htmlspecialchars(strip_tags($_GET["ns"])) : null;
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

            if ($setNS == "") {
                return '<div class="allpages">Keine Ergebnisse</div>';
            }

            $perPage = 50;
            $gen = "";

            $directory = "pages/" . ($setNS == "all" ? "" : ($setNS . "/"));
            $allFiles = [];

            if (is_dir($directory)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isFile() && ($fileInfo->getExtension() === 'md' || $fileInfo->getExtension() === 'php')) {
                        $allFiles[] = $fileInfo->getPathname();
                    }
                }
            }

            usort($allFiles, function($a, $b) {
                $nameA = basename($a, '.' . pathinfo($a, PATHINFO_EXTENSION));
                $nameB = basename($b, '.' . pathinfo($b, PATHINFO_EXTENSION));
                return strcasecmp($nameA, $nameB);
            });

            if (empty($allFiles)) {
                return '';
            }

            $totalFiles = count($allFiles);
            $totalPages = ceil($totalFiles / $perPage);
            $offset = ($page - 1) * $perPage;
            $filesLimited = array_slice($allFiles, $offset, $perPage);

            foreach ($filesLimited as $file) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $filename = htmlspecialchars(basename($file, "." . $ext));
                $ns = htmlspecialchars(basename(dirname($file)));
                $gen .= '<div class="allpage"><a href="?f=' . $ns . ':' . $filename . '">' . 
                        ($setNS == "all" ? ($ns . ":") : "") . 
                        $filename . '</a></div>';
            }

            $pagination = '<div class="pagination">';
            if ($page > 1) {
                $pagination .= '<a href="?ns=' . urlencode($setNS) . '&page=' . ($page - 1) . '">&laquo; Prev</a> ';
            }
            $pagination .= " Seite $page von $totalPages ";
            if ($page < $totalPages) {
                $pagination .= '<a href="?ns=' . urlencode($setNS) . '&page=' . ($page + 1) . '">Next &raquo;</a>';
            }
            $pagination .= '</div>';

            return '<div class="allpages">' . $gen . '</div>' . $pagination;
        },
        'ALLUSERS' => function() use (&$user) {
            global $conn;

            $gen = "";
            $sql = $conn->query("SELECT 
            u.username,
            u.firstname,
            u.lastname,
            u.email,
            r.role_name AS roleName,
            b.id AS isBlocked
            FROM users u
            JOIN roles r ON u.role = r.id
            LEFT JOIN blocks b ON u.id = b.targetId AND (b.durationUntil >= NOW())
            ORDER BY username ASC");
            if ($sql && $sql->num_rows > 0) {
                while($row = $sql->fetch_assoc()) {
                    $roleName = $row["roleName"] ?? "Unknown";
                    if (isset($row["isBlocked"])) $block = true;
                    else $block = false;

                    $gen .= '
                    <div class="user">
                        <span class="username">' . htmlspecialchars($row["username"]) . '</span>
                        <span class="miniinfo">
                            <span class="name">' . htmlspecialchars($row["firstname"] . ' ' . $row["lastname"]) . '</span>
                            <span class="role">' . htmlspecialchars($roleName) . '</span>
                            <span class="email">' . htmlspecialchars($row["email"]) . '</span>
                        </span>
                        <span class="tf-indicator blr ' . ($block ? "false" : "true") . '">
                            ' . ($block ? 'Aktive Sperre' : 'Keine aktive Sperre') . '
                        </span>
                    </div>';
                }
            }
            else {
                $gen = "No users found";
            }
            return '<div class="allusers">' . $gen . '</div>';
        },
        'PROTECTIONCHECK' => function() use (&$user, $data, $namespace, $filename) {
            // If a target page is passed via ?t= (edit page), use that page's JSON to check protection.
            $target = isset($_GET['t']) ? htmlspecialchars(strip_tags($_GET['t'])) : null;
            $checkProtect = 'none';
            $checkNamespace = $namespace;
            $checkFilename = $filename;

            if ($target) {
                $parts = explode(':', $target, 2);
                if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
                    $checkNamespace = strtolower($parts[0]);
                    $checkFilename = strtolower($parts[1]);
                    $jsonPath = "pages/{$checkNamespace}/{$checkFilename}.json";
                    $jsonText = @file_get_contents($jsonPath);
                    if ($jsonText !== false && $jsonText !== null) {
                        $j = json_decode($jsonText, true);
                        if (is_array($j) && isset($j['protect'])) {
                            $checkProtect = $j['protect'];
                        }
                    }
                }
            } else {
                // fallback to current page data if no ?t is provided
                $checkProtect = $data['protect'] ?? 'none';
            }

            $rights = [
                "none" => "edit",
                "semiprotected" => "editsemiprotected",
                "protected" => "editprotected",
                "superprotected" => "editsuperprotected"
            ];

            $right = $rights[$checkProtect] ?? "edit";

            if (!$user->hasPermission($right)[0]) {
                // Redirect to 403 showing which page was blocked (use the target if present)
                $redirectTarget = $target ? $target : ($namespace . ":" . $filename);
                header("Location: ?f=special:403&t=" . urlencode($redirectTarget) . "&r=" . urlencode($right));
                exit;
            }
            return '';
        },
    ];

    // Helper function to parse template parameters (positional and named)
    function parseTemplateParams($paramString) {
        $params = [];
        $named = [];
        $parts = explode('|', $paramString);
        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, '=') !== false) {
                list($key, $value) = explode('=', $part, 2);
                $named[trim($key)] = trim($value);
            } else {
                $params[] = $part;
            }
        }
        return [$params, $named];
    }

    // Replace Templates (support {{TemplateName}}, {{TemplateName|param1|param2}}, {{TemplateName|foo=bar}})
    // If a template has a corresponding JSON file with "NO-VALIDATION": true, we
    // render that template to HTML separately and embed it as a base64 placeholder
    // inside a div with class 'ownwiki-raw'. After HTMLPurifier runs we decode those
    // placeholders back into raw HTML so ONLY the included template bypasses purification.
    $rawPlaceholderClass = 'ownwiki-raw';
    $args[1] = preg_replace_callback(
        '/(?<!{){{\s*([A-Za-z0-9_!?]+)(?:\|([^}]*))?\s*}}(?!})/',
        function ($matches) use ($converter, $rawPlaceholderClass) {
            $templateName = $matches[1];
            $params = [];
            $named = [];
            if (isset($matches[2])) {
                list($params, $named) = parseTemplateParams($matches[2]);
            }
            $templatePath = 'pages/template/' . strtolower($templateName) . '.md';
            $templateJsonPath = 'pages/template/' . strtolower($templateName) . '.json';
            if (file_exists($templatePath)) {
                $content = file_get_contents($templatePath);
                // Replace positional params: {{{1}}}, {{{2}}}, ...
                foreach ($params as $idx => $param) {
                    $content = str_replace('{{{' . ($idx + 1) . '}}}', $param, $content);
                    // also support default syntax {{{1|default}}}
                    $content = preg_replace_callback('/{{{' . ($idx + 1) . '\|([^}]+)}}}/', function($m) use ($param) {
                        return ($param !== null && $param !== '') ? $param : $m[1];
                    }, $content);
                }
                // Replace named params: {{{paramName}}} and named defaults {{{paramName|default}}}
                foreach ($named as $key => $value) {
                    $content = str_replace('{{{' . $key . '}}}', $value, $content);
                    $content = preg_replace_callback('/{{{' . preg_quote($key, '/') . '\|([^}]+)}}}/', function($m) use ($value) {
                        return ($value !== null && $value !== '') ? $value : $m[1];
                    }, $content);
                }
                // For placeholders that weren't replaced because the caller omitted them,
                // support default values inline like {{{1|Preset}}} or {{{name|Preset}}}.
                $content = preg_replace_callback('/{{{\s*([0-9]+)\s*\|\s*([^}]+)\s*}}}/', function($m) use ($params) {
                    $index = (int)$m[1] - 1;
                    $val = $params[$index] ?? '';
                    return ($val !== null && $val !== '') ? $val : $m[2];
                }, $content);
                $content = preg_replace_callback('/{{{\s*([A-Za-z0-9_\-]+)\s*\|\s*([^}]+)\s*}}}/', function($m) use ($named) {
                    $key = $m[1];
                    $val = $named[$key] ?? '';
                    return ($val !== null && $val !== '') ? $val : $m[2];
                }, $content);

                // If the template has a JSON config that requests NO-VALIDATION,
                // render the template separately and embed it as base64 inside a div.
                if (file_exists($templateJsonPath)) {
                    $meta = @file_get_contents($templateJsonPath);
                    if ($meta !== false) {
                        $mdata = json_decode($meta, true);
                        if (is_array($mdata) && isset($mdata['NO-VALIDATION']) && $mdata['NO-VALIDATION'] === true) {
                            // convert template markdown to HTML (do NOT purify)
                            $rendered = $converter->convertToHtml($content);
                            $b64 = base64_encode($rendered);
                            // embed as HTML so CommonMark (with html_input=allow) will pass it through
                            return '<div class="' . $rawPlaceholderClass . '">' . $b64 . '</div>';
                        }
                    }
                }

                return $content;
            } else {
                return 'Template not found: ' . htmlspecialchars($templateName);
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
            else if ($word == "PAGECONTENTS") return "[[PAGECONTENTS]]";
            return '';
        },
        $args[1]
    );

    // check for redirect
    if (preg_match('/^\$REDIRECT:([A-Za-z0-9:_-]+)\$/m', $args[1], $matches)) {
        $redirectPage = strtolower($matches[1]);
        header("Location: ?f=$redirectPage");
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

    $dirty_html = preg_replace_callback(
        '/\[\[\s*PAGECONTENTS\s*\]\]/',
        function () {
            $page = isset($_GET["t"]) ? htmlspecialchars(strip_tags($_GET["t"])) : "";
            $split = explode(":", $page);
            $namespace = $split[0] ?? '';
            $pagename = $split[1] ?? '';
            if ($namespace === '' || $pagename === '') {
                return 'Error';
            }
            $content = @file_get_contents("pages/$namespace/$pagename.md");
            if ($content === false || $content === null) {
                return 'Error';
            }
            return $content;
        },
        $dirty_html
    );

    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.DefinitionID', 'custom-def-1');
    $config->set('HTML.DefinitionRev', 26);
    // Configuration for HTMLPurifier
    if ($namespace == "special") {
        $config->set('HTML.Allowed', 'div,i,h2,h3,h4,h5,h6,p,span,ul,ol,li,a,strong,em,br,img,table,tr,td,th,form,input,button,textarea,select,option');
        $config->set('HTML.AllowedAttributes', '*.class,*.style,a.href,a.title,h2.id,h3.id,h4.id,h5.id,h6.id,img.src,img.alt,img.title,input.name,input.value,input.type,input.placeholder,button.type,button.name,textarea.name,form.action,form.method,select.name,option.value,option.selected');
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
        'border',
        'height',
        'font-size',
        'font-weight'
    ]);
    $config->set('CSS.AllowImportant', false); // no !important
    $config->set('CSS.AllowTricky', true);

    if (($def = $config->maybeGetRawHTMLDefinition()) && $namespace == "special") {
        $def->addElement('input', 'Inline', 'Empty', 'Common', [
            'type'  => 'Text',
            'name'  => 'Text',
            'value' => 'Text',
        ]);
        $def->addElement('select', 'Inline', 'Flow', 'Common', [
            'name'  => 'Text',
        ]);
        $def->addElement('option', 'Inline', 'Flow', 'Common', [
            'value' => 'Text',
            'selected' => 'Text'
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

    // After purification, replace our raw placeholders with their decoded HTML.
    // This ensures only included templates that requested NO-VALIDATION bypass purifier.
    $clean_html = preg_replace_callback(
        '/<div class="ownwiki-raw">\s*([A-Za-z0-9+\/=]+)\s*<\/div>/i',
        function ($m) {
            $decoded = base64_decode($m[1]);
            if ($decoded === false) return '';
            return $decoded;
        },
        $clean_html
    );

    $toc = $generateTOC == true ? genTOC($args[1]) : "";

    return !$validate ? $toc . $dirty_html : $toc . $clean_html;
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