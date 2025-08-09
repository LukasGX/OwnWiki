<?php
// connect to db
$servername = "localhost";
$username = "ownwiki_root";
$password = "xchTqznOGHzJbYKf";
$conn = new mysqli($servername, $username, $password);

// create database if not exists
$sql = $conn->prepare("CREATE DATABASE IF NOT EXISTS ownwikidb");
$sql->execute();
$sql->close();

// disconnect
$conn->close();

// connect to db
$servername = "localhost";
$username = "ownwiki_root";
$password = "xchTqznOGHzJbYKf";
$db = "ownwikidb";
$conn = new mysqli($servername, $username, $password, $db);

// creation commands
$creation_commands = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        firstname VARCHAR(30) NOT NULL,
        lastname VARCHAR(30) NOT NULL,
        username VARCHAR(30) NOT NULL,
        email VARCHAR(50) NOT NULL,
        `password` VARCHAR(3000) NOT NULL,
        `role` INT(6) NOT NULL
    )",
    "CREATE TABLE IF NOT EXISTS roles (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        role_name VARCHAR(30) NOT NULL,
        privileges VARCHAR(3000) NOT NULL
    )",
    "CREATE TABLE IF NOT EXISTS user_rights (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        right_name VARCHAR(30) NOT NULL,
        `description` VARCHAR(300) NOT NULL,
        dependencies VARCHAR(300) DEFAULT NULL
    )"
];

// table deletion commands
$table_deletion_commands = [
    "DROP TABLE users",
    "DROP TABLE roles",
    "DROP TABLE user_rights"
];

$default_privileges = [
    "read", "createaccount"
];
$encoded_default_privileges = json_encode($default_privileges);

$user_privileges = [
    "edit", "applytags", "sendemail", "editmyoptions", "editmyprivateinfo", "editmywatchlist", "viewmyprivateinfo", "viewmywatchlist"
];
$encoded_user_privileges = json_encode($user_privileges);

$autoconfirmed_privileges = [
    "createpage", "creatediscussion", "editsemiprotected", "minoredit", "upload", "uploadbyurl", "editcontentmodel", "pagelang", "export", "autoconfirmed"
];
$encoded_autoconfirmed_privileges = json_encode($autoconfirmed_privileges);

$bot_privileges = [
    "import", "higherapilimits", "bot", "suppressredirect"
];
$encoded_bot_privileges = json_encode($bot_privileges);

$admin_privileges = [
    "editprotected", "move", "movefile", "reupload", "delete", "bigdelete", "block", "blockemail", "browsedeleted", "protect", "rollback", "undelete", "deletetags", "import", "managetags", "higherapilimits", "ipblockbypass", "suppressredirect"
];
$encoded_admin_privileges = json_encode($admin_privileges);

$interface_admin_privileges = [
    "editinterface", "editsitecss", "editsitejs"
];
$encoded_interface_admin_privileges = json_encode($interface_admin_privileges);

$bureaucrat_privileges = [
    "userrights", "renameusers"
];
$encoded_bureaucrat_privileges = json_encode($bureaucrat_privileges);

$suppressor_privileges = [
    "deletelogentry", "deletehistory", "hideuser", "suppressionlog", "supresshistory", "viewsuppressed", "renameusers"
];
$encoded_suppressor_privileges = json_encode($suppressor_privileges);

$insertion_commands = [
    // rights
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('read', 'Read pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('edit', 'Edit pages', '[\"read\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('applytags', 'Apply tags to own edits', '[\"edit\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('createaccount', 'Create accounts', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('createpage', 'Create pages', '[\"edit\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('creatediscussion', 'Create discussion pages', '[\"edit\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('editsemiprotected', 'Edit pages with semi protection', '[\"edit\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('editprotected', 'Edit protected pages', '[\"edit\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('minoredit', 'Mark own edits as minor', '[\"edit\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('move', 'Move pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('movefile', 'Move files', '[\"move\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('upload', 'Upload files', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('uploadbyurl', 'Upload files by url', '[\"upload\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('reupload', 'Overwrite existing files', '[\"upload\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('sendemail', 'Send emails to other users', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('delete', 'Delete pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('bigdelete', 'Delete pages with large histories', '[\"delete\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('block', 'Block users', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('blockemail', 'Block users from sending emails', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('browsedeleted', 'Browse deleted pages', '[\"delete\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('deletelogentry', 'Delete log entries', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('deletehistory', 'Delete specific historys of pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('editcontentmodel', 'Edit page content models', '[\"edit\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('editinterface', 'Edit user interface', '[\"edit\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('editmyoptions', 'Edit your own preferences', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('editmyprivateinfo', 'Edit your private information', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('editmywatchlist', 'Edit your own watchlist', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('editsitecss', 'Edit site-wide css', '[\"editinterface\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('editsitejs', 'Edit site-wide js', '[\"editinterface\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('hideuser', 'Block and hide usernames', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('pagelang', 'Edit the page language', '[\"edit\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('protect', 'Protect pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('rollback', 'Rollback edits', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('suppressionlog', 'View suppression logs', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('suppresshistory', 'Suppress page histories', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('undelete', 'Undelete pages', '[\"delete\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('userrights', 'Edit user rights', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('viewmyprivateinfo', 'View your private information', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('viewmywatchlist', 'View your watchlist', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('viewsuppressed', 'View suppressed page histories', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('deletetags', 'Delete tags', '[\"managetags\"]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('import', 'Import pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('export', 'Export pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('managetags', 'Manage tags', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('renameusers', 'Rename users', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('higherapilimits', 'Use higher API rate limits', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('autoconfirmed', 'Access benefits', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('bot', 'Be treated as an automated process', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('ipblockbypass', 'Bypass ip blocks', '[]')",
    "INSERT INTO user_rights (right_name, `description`, dependencies) VALUES ('suppressredirect', 'Suppress the creation of redirects when moving a page', '[\"createpage\"]')",
    // roles
    "INSERT INTO roles (role_name, privileges) VALUES ('default', '" . $encoded_default_privileges . "')",
    "INSERT INTO roles (role_name, privileges) VALUES ('user', '" . $encoded_user_privileges . "')",
    "INSERT INTO roles (role_name, privileges) VALUES ('autoconfirmed', '" . $encoded_autoconfirmed_privileges . "')",
    "INSERT INTO roles (role_name, privileges) VALUES ('bot', '" . $encoded_bot_privileges . "')",
    "INSERT INTO roles (role_name, privileges) VALUES ('admin', '" . $encoded_admin_privileges . "')",
    "INSERT INTO roles (role_name, privileges) VALUES ('interface_admin', '" . $encoded_interface_admin_privileges . "')",
    "INSERT INTO roles (role_name, privileges) VALUES ('bureaucrat', '" . $encoded_bureaucrat_privileges . "')",
    "INSERT INTO roles (role_name, privileges) VALUES ('suppressor', '" . $encoded_suppressor_privileges . "')"
];

// danger zone
define('DB_INSERTION_COMMANDS', false);
define('DB_DELETE_TABLES', false);
define('DB_DELETE_USER_RIGHTS', false);
define('DB_DELETE_ROLES', false);

foreach ($creation_commands as $command) {
    $sql = $conn->prepare($command);
    $sql->execute();
    $sql->close();
}
echo "Creation commands executed<br />";
if (DB_INSERTION_COMMANDS === true) {
    foreach ($insertion_commands as $command) {
        $sql = $conn->prepare($command);
        $sql->execute();
        $sql->close();
    }
    echo "Insertion commands executed<br />";
}
if (DB_DELETE_TABLES === true) {
    foreach ($table_deletion_commands as $command) {
        $sql = $conn->prepare($command);
        $sql->execute();
        $sql->close();
    }
    echo "Table deletion commands executed<br />";
}
if (DB_DELETE_USER_RIGHTS === true) {
    $sql = $conn->prepare("DELETE FROM user_rights");
    $sql->execute();
    $sql->close();
    echo "Cleared user rights<br />";
}

if (DB_DELETE_ROLES === true) {
    $sql = $conn->prepare("DELETE FROM roles");
    $sql->execute();
    $sql->close();
    echo "Cleared roles<br />";
}
?>