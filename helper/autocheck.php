<?php
function autoCheck($oldContent, $newContent) {
    /*$rules = [
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
    ];*/

    $pattern_types = [
        "diff-length" => function () use (&$oldContent, &$newContent) {
            $oldLength = mb_strlen(trim($oldContent));
            $newLength = mb_strlen(trim($newContent));

            if ($oldLength === 0) {
                return ($newLength === 0) ? 0.0 : 1.0;
            }

            return ($newLength - $oldLength) / $oldLength;
        }
    ];

    $status = "ok";
    $ruleName = "";
    $extraInfo = "";

    foreach (glob(__DIR__ . '/rules/*.json') as $ruleFile) {
        $json = file_get_contents($ruleFile);
        $content = json_decode($json, true);

        if ($content["enabled"] == true) {
            $pattern = $content["pattern"];

            if (array_key_exists($pattern["type"], $pattern_types)) {
                $result = $pattern_types[$pattern["type"]]();
                if (($pattern["check"] == "gt" && $result >= $pattern["threshold"]) || ($pattern["check"] == "lt" && $result <= $pattern["threshold"])) {
                    $status = $content["action"]["type"];
                    $ruleName = $content["id"];
                    break;
                }
                else continue;
            }
        }
        else continue;
    }

    return [$status, $ruleName, $extraInfo];
}
?>