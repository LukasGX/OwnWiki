<?php
foreach (glob('helper/rules/*.json') as $ruleFile) {
    $json = file_get_contents($ruleFile);
    $content = json_decode($json, true);

    $enabledText = $content["enabled"] == true ? "<i class='fas fa-check'></i> Aktiviert" : "<i class='fas fa-xmark'></i> Deaktiviert";
    $enabledClass = $content["enabled"] == true ? "enabled" : "disabled";

    // condition
    $type = $content["pattern"]["type"];
    $check = $content["pattern"]["check"];
    $threshold = $content["pattern"]["threshold"] ?? "";

    if ($check === "gt") $operator = ">=";
    else if ($check === "lt") $operator = "<=";
    else $operator = "";

    if ($check === "tf") $displayThreshold = "";
    else $displayThreshold = $threshold;

    if ($type === "wordlist") {
        $words = $content["pattern"]["words"] ?? [];
        $wordsJson = json_encode($words, JSON_HEX_APOS | JSON_HEX_QUOT);
        $condition = '<a href="#" onclick=\'openWordlistModal(' . $wordsJson . '); return false;\'>' . htmlspecialchars($type) . '</a>';
    }
    else $condition = $type . " " . $operator . " " . $displayThreshold;

    echo "
    <div class='auto-rule'>
        <span>" . $content['name'] . "</span>
        <span class='" . $enabledClass . "'>" . $enabledText . "</span>
        <span class='codeh'>" . $condition . "</span>
        <div class='btns'>
            <button onclick='editRule(\"" . $content['id'] . "\")'><i class='fas fa-pencil'></i></button>
            <button onclick='deleteRule(\"" . $content['id'] . "\")' class='deleteButton'><i class='fas fa-trash'></i></button>
        </div>
    </div>
    ";
}
?>
<button class="full add-rule-btn" onclick="editRule('', true)"><i class="fas fa-plus"></i></button>

<div></div>