<?php
@session_start();
include_once(__DIR__ . "/../backend/user.php");
function getTitleBar($user, $protectedStatus, $pagename) {
    $neededPermission = "edit";
    if ($protectedStatus === "semiprotected")
        $neededPermission = "editsemiprotected";
    else if ($protectedStatus === "protected")
        $neededPermission = "editprotected";
    else if ($protectedStatus === "superprotected")
        $neededPermission = "editsuperprotected";


    $output = "";

    $output .= "
    <div class=\"titlebar\">
        <div class=\"group\">
            <span class=\"element active\"><i class=\"fas fa-file\"></i> Artikel</span>
            <span class=\"element\"><i class=\"fas fa-comment\"></i> Diskussion</span>
            <span class=\"element\"><i class=\"far fa-star\"></i></span>
        </div>
        <div class=\"group\">
    ";
    if ($user->hasPermission($neededPermission)) {
        $output .= "<span class=\"element\"><a href='?f=special:edit&t=$pagename'><i class=\"fas fa-pen\"></i> Bearbeiten</a></span>";
    } else {
        $output .= "<span class=\"element\"><i class=\"fas fa-code\"></i> Quelltext anzeigen</span>";
    }
    $output .= "
            <span class=\"element\"><i class=\"fas fa-clock\"></i> Versionsgeschichte</span>
        </div>
    </div>";

    return $output;
}
?>