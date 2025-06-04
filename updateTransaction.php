<?php
session_start();
include "config.php";
header('Content-Type: application/json');

if (!isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$userid = $_SESSION['userid'];

// New: Only latest balance request
if (isset($_GET['latestbalance']) && $_GET['latestbalance'] == 1 && isset($_GET['customerid'])) {
    $customerid = $_GET['customerid'];

    $stmt = $conn->prepare("SELECT current_balance FROM transaction_table WHERE clientid = ? AND userid = ? ORDER BY date DESC, transactionid DESC LIMIT 1");
    $stmt->bind_param("ii", $customerid, $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(["success" => true, "latest_balance" => $row['current_balance']]);
    } else {
        echo json_encode(["success" => true, "latest_balance" => 0]);
    }
    exit;
}

// Load full transaction list
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $customerid = $_GET['customerid'];

    $stmt = $conn->prepare("SELECT * FROM transaction_table WHERE clientid = ? AND userid = ? ORDER BY date DESC, transactionid DESC");
    $stmt->bind_param("ii", $customerid, $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }

    echo json_encode(['success' => true, 'transactions' => $transactions]);
    exit;
}

// Add new transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerid = $_POST['customerid'];
    $amount = $_POST['amount'];
    $type = $_POST['type'];
    $bill_no = $_POST['bill_no'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');

    $stmt = $conn->prepare("SELECT groupid FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customerid);
    $stmt->execute();
    $stmt->bind_result($groupid);
    $stmt->fetch();
    $stmt->close();

    if (!$groupid) {
        echo json_encode(['success' => false, 'message' => 'Customer group not found']);
        exit;
    }

    $stmt = $conn->prepare("SELECT current_balance FROM transaction_table WHERE clientid = ? AND userid = ? ORDER BY date DESC, transactionid DESC LIMIT 1");
    $stmt->bind_param("ii", $customerid, $userid);
    $stmt->execute();
    $stmt->bind_result($last_balance);
    $stmt->fetch();
    $stmt->close();

    $last_balance = $last_balance ?? 0;

    if ($type === 'credit') {
        $new_balance = $last_balance + $amount;
        $stmt = $conn->prepare("INSERT INTO transaction_table (clientid, amount_given, bill_number, date, current_balance, userid, groupid) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissiii", $customerid, $amount, $bill_no, $date, $new_balance, $userid, $groupid);
    } elseif ($type === 'debit') {
        $new_balance = $last_balance - $amount;
        $stmt = $conn->prepare("INSERT INTO transaction_table (clientid, amount_received, bill_number, date, current_balance, userid, groupid) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissiii", $customerid, $amount, $bill_no, $date, $new_balance, $userid, $groupid);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction type']);
        exit;
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Transaction recorded successfully',
            'current_balance' => $new_balance
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Execution failed: ' . $stmt->error]);
    }

    $stmt->close();
}
?>
