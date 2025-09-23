<?php
include_once("dbc.php");
class User {
    private $id;
    private $username;
    private $firstname;
    private $lastname;
    private $email;
    private $role;
    private $activeBlock;
    private $blockData;

    public function __construct($id, $username, $firstname, $lastname, $email, $role, $activeBlock, $blockData) {
        $this->username = $username;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->role = $role;
        $this->id = $id;
        $this->activeBlock = $activeBlock;
        $this->blockData = $blockData;
    }

    public function getId() {
        return $this->id;
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

    public function isBlocked() {
        return $this->activeBlock;
    }

    public function hasPermission($permission) {
        $conn = $GLOBALS['conn'];

        if ($permission == "dummy") return [true, "dummy"];

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

        $removeOnBlock = [
            "edit", "applytags", "editmyoptions", "createpage", "creatediscussion", "editsemiprotected", "minoredit", "upload", "uploadbyurl",
            "editcontentmodel", "pagelang", "export", "autoconfirmed", "import", "higherapilimits", "bot", "suppressredirect", "editprotected",
            "move", "movefile", "reupload", "delete", "bigdelete", "block", "blockemail", "browsedeleted", "protect", "rollback", "undelete",
            "deletetags", "import", "managetags", "higherapilimits", "ipblockbypass", "suppressredirect", "editinterface", "editsitecss",
            "editsitejs", "editsuperprotected", "userrights", "renameusers", "deletelogentry", "deletehistory", "hideuser", "suppressionlog",
            "supresshistory", "viewsuppressed", "renameusers"
        ];

        $pr = ["createAccount", "sendemail"];

        $placeholders = implode(',', array_fill(0, count($rolesToCheck), '?'));
        $types = str_repeat('i', count($rolesToCheck));

        $sql = $conn->prepare("SELECT privileges FROM roles WHERE id IN ($placeholders)");
        $sql->bind_param($types, ...$rolesToCheck);
        $sql->execute();
        $result = $sql->get_result();

        while ($row = $result->fetch_assoc()) {
            $privileges = json_decode($row['privileges'], true);

            if (is_array($privileges) && in_array($permission, $privileges)) {
                if ($this->isBlocked() && in_array($permission, $removeOnBlock)) {
                    return [false, "block"];
                }
                return [true, "permission"];
            }
        }

        return [false, "nopermission"];
    }
}
?>