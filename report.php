<?php
include('config.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['userid'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$userid = $_SESSION['userid'];

// Fetch group list for dropdown
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetchGroups'])) {
    $sql = "SELECT groupid, groupname FROM user_group WHERE userid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    $groups = [];
    while ($row = $result->fetch_assoc()) {
        $groups[] = $row;
    }

    echo json_encode($groups);
    exit;
}

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start = $_POST['start_date'] ?? null;
    $end = $_POST['end_date'] ?? null;
    $groupid = $_POST['groupid'] ?? null;

    if ($start && $end && $groupid) {
        $sql = "SELECT 
                    COALESCE(SUM(amount_given), 0) AS total_given,
                    COALESCE(SUM(amount_received), 0) AS total_received
                FROM transaction_table
                WHERE userid = ? AND groupid = ? AND date BETWEEN ? AND ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $userid, $groupid, $start, $end);
        $stmt->execute();
        $stmt->bind_result($total_given, $total_received);
        $stmt->fetch();
        $stmt->close();

        $balance = ($total_given ?? 0) - ($total_received ?? 0);

   
        echo json_encode([
            'given' => $total_given ?? 0,
            'received' => $total_received ?? 0,
            'balance' => $balance
        ]);
    } else {
        echo json_encode(['error' => 'Missing required fields']);
    }
}
?>
