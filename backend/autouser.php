<?php
function autoUser($id, $conn) {
    $username; $firstname; $lastname; $email; $role; $activeBlock;

    $sql = $conn->prepare("SELECT
    u.username,
    u.firstname,
    u.lastname,
    u.email,
    u.role,
    b.id AS blockId
    FROM users u
    LEFT JOIN blocks b ON u.id = b.targetId AND (b.durationUntil >= NOW())
    WHERE u.id = ?");
    $sql->bind_param("s", $id);
    $sql->execute();
    $result = $sql->get_result();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $username = $row["username"];
            $firstname = $row["firstname"];
            $lastname = $row["lastname"];
            $email = $row["email"];
            $role = $row["role"];
            $activeBlock = isset($row["blockId"]) ? true : false;
        }
    }

    return new User(
        $id,
        $username,
        $firstname,
        $lastname,
        $email,
        $role,
        $activeBlock,
        []
    );
}

function dummyUser() {
    return new User("", "", "", "", "", "1", false, []);
}
?>