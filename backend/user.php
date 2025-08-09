<?php
include_once("dbc.php");
class User {
    private $username;
    private $firstname;
    private $lastname;
    private $email;
    private $role;

    public function __construct($username, $firstname, $lastname, $email, $role) {
        $this->username = $username;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->role = $role;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getRole() {
        return $this->role;
    }

    public function isLoggedIn() {
        return isset($_SESSION["username"]) && $_SESSION["username"] === $this->username;
    }

    public function getFirstname() {
        return $this->firstname;
    }

    public function getLastname() {
        return $this->lastname;
    }

    public function getEmail() {
        return $this->email;
    }

    public function hasPermission($permission) {
        $conn = $GLOBALS['conn'];

        $roleHierarchy = [
            1 => [1],
            2 => [1, 2],
            3 => [1, 2, 3],
            4 => [1, 2, 3, 4],
            5 => [1, 2, 3, 5],
            6 => [1, 2, 3, 6],
            7 => [1, 2, 3, 7],
            8 => [1, 2, 3, 8]
        ];

        $rolesToCheck = $roleHierarchy[$this->role] ?? [$this->role];

        $placeholders = implode(',', array_fill(0, count($rolesToCheck), '?'));
        $types = str_repeat('i', count($rolesToCheck));

        $sql = $conn->prepare("SELECT privileges FROM roles WHERE id IN ($placeholders)");
        $sql->bind_param($types, ...$rolesToCheck);
        $sql->execute();
        $result = $sql->get_result();

        while ($row = $result->fetch_assoc()) {
            $privileges = json_decode($row['privileges'], true);

            if (is_array($privileges) && in_array($permission, $privileges)) {
                return true;
            }
        }

        return false;
    }
}
?>