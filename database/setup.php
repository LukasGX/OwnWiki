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

$insertion_commands = [
    // roles
    "INSERT INTO roles (role_name, privileges) VALUES ('default', '[]')",
    "INSERT INTO roles (role_name, privileges) VALUES ('user', '[]')",
    "INSERT INTO roles (role_name, privileges) VALUES ('autoconfirmed', '[]')",
    "INSERT INTO roles (role_name, privileges) VALUES ('admin', '[]')",
    "INSERT INTO roles (role_name, privileges) VALUES ('bureaucrat', '[]')",
    "INSERT INTO roles (role_name, privileges) VALUES ('suppressor', '[]')",
    "INSERT INTO roles (role_name, privileges) VALUES ('interface_admin', '[]')",
    // rights
    "INSERT INTO user_rights (right_name, `description`) VALUES ('read', 'Read pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('edit', 'Edit pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('applytags', 'Apply tags to own edits', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('createaccount', 'Create accounts', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('createpage', 'Create pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('creatediscussion', 'Create discussion pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('editsemiprotected', 'Edit pages with semi protection', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('editprotected', 'Read pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('minoredit', 'Mark own edits as minor', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('move', 'Move pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('movefile', 'Move files', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('upload', 'Upload files', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('uploadbyurl', 'Upload files by url', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('reupload', 'Overwrite existing files', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('sendemail', 'Send emails to other users', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('delete', 'Delete pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('bigdelete', 'Delete pages with large histories', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('block', 'Block users', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('blockemail', 'Block users from sending emails', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('browsedeleted', 'Browse deleted pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('deletelogentry', 'Delete log entries', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('deletehistory', 'Delete specific historys of pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('editcontentmodel', 'Edit page content models', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('editinterface', 'Edit user interface', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('editmyoptions', 'Edit your own preferences', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('editmyprivateinfo', 'Edit your private information', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('editmywatchlist', 'Edit your own watchlist', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('editsitecss', 'Edit site-wide css', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('editsitejs', 'Edit site-wide js', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('hideuser', 'Block and hide usernames', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('pagelang', 'Edit the page language', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('protect', 'Protect pages', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('rollback', 'Rollback edits', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('suppressionlog', 'View suppression logs', '[]')",
    "INSERT INTO user_rights (right_name, `description`) VALUES ('suppresshistory', 'Suppress page histories', '[]')",
];
?>