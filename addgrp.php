<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


header('Content-Type: application/json');


session_start();
include('config.php');

if (!isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$userid = intval($_SESSION['userid']); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['groupName']) && !empty($_POST['groupName'])) {
        $groupName = $conn->real_escape_string($_POST['groupName']);

        $sql = "INSERT INTO user_group (userid, groupname) VALUES ($userid, '$groupName')";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(['success' => true, 'message' => 'Group added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Group name is required']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT groupid, groupname FROM user_group WHERE userid = $userid";
    $result = $conn->query($sql);

    $groups = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $groups[] = [
                'id' => $row['groupid'],
                'name' => $row['groupname']
            ];
        }
    }

    echo json_encode(['groups' => $groups]);
}

$conn->close();
?>
