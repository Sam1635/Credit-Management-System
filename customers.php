<?php
include 'config.php';
session_start(); 

header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);


$userid = $_SESSION['userid'] ?? null;


if (!$userid) {
    echo json_encode(["error" => "Unauthorized. User not logged in."]);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['groupid'])) {
    $groupid = intval($_GET['groupid']);

    $sql = "SELECT c.id, c.name, t.current_balance AS amount
            FROM customers c
            JOIN transaction_table t ON c.id = t.clientid
            INNER JOIN (
                SELECT clientid, MAX(transactionid) AS latest_transaction
                FROM transaction_table
                WHERE groupid = ? AND userid = ?
                GROUP BY clientid
            ) latest ON t.clientid = latest.clientid AND t.transactionid = latest.latest_transaction
            WHERE t.groupid = ? AND t.userid = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $groupid, $userid, $groupid, $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }

    echo json_encode($customers);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(["error" => "Invalid JSON input"]);
        exit;
    }

    $name = $input['name'] ?? null;
    $phone = $input['phone'] ?? null;
    $balance = $input['balance'] ?? null; 
    $billno = $input['billno'] ?? null;    
    $groupid = $input['groupid'] ?? null;

    if (!$name || !$phone || !$groupid || $balance === null || $billno === null) {
        echo json_encode(["error" => "Missing required fields"]);
        exit;
    }

    $conn->begin_transaction();

    try {
       
        $stmt = $conn->prepare("INSERT INTO customers (name, phone, groupid) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $phone, $groupid);
        $stmt->execute();
        $clientid = $stmt->insert_id;

        $stmt2 = $conn->prepare("INSERT INTO transaction_table 
            (clientid, bill_number, amount_given, amount_received, date, current_balance, userid, groupid)
            VALUES (?, ?, ?, 0, NOW(), ?, ?, ?)");
        $stmt2->bind_param("isiiii", $clientid, $billno, $balance, $balance, $userid, $groupid);
        $stmt2->execute();

        $conn->commit();
        echo json_encode(["message" => "Customer added successfully"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
    exit;
}
?>
