<?php
function autoCheck($oldContent, $newContent) {
    // define example rules
    $rules = [
        "noDeleteEverything" => function () use (&$newContent) {
            return $newContent == "" ? ["block"] : ["ok"];
        },
        "prohibitedWords" => function () use (&$newContent) {
            $content = file_get_contents("../helper/prohibited-words.json");
            $prohibitedWords = json_decode($content, true);

            foreach ($prohibitedWords as $word) {
                if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $newContent)) {
                    return ["block", $word];
                }
            }

            return ["ok"];
        }
    ];

    $status = "ok";
    $extraInfo = "";
    $ruleName = "";
    foreach ($rules as $key => $rule) {
        $result = $rule();
        if ($result[0] == "block") {
            $status = "block";
            $ruleName = $key;
            if (isset($result[1])) $extraInfo = $result[1];
        }
    }

    return [$status, $ruleName, $extraInfo];
}
?>