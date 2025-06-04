<?php
session_start();
header('Content-Type: application/json');
include 'config.php';
if (!isset($_SESSION['userid'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}
$userid = $_SESSION['userid'];
$admin_stmt = $conn->prepare("SELECT businessname FROM admin WHERE userid = ?");
$admin_stmt->bind_param("i", $userid);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result()->fetch_assoc();
$businessname = $admin_result['businessname'] ?? 'User';
$trans_stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(amount_given), 0) AS total_debited,     -- money you gave
        COALESCE(SUM(amount_received), 0) AS total_credited  -- money you got
    FROM transaction_table
    WHERE userid = ?
");
$trans_stmt->bind_param("i", $userid);
$trans_stmt->execute();
$trans_result = $trans_stmt->get_result()->fetch_assoc();

$balance_stmt = $conn->prepare("
    SELECT SUM(latest_balance) AS total_balance
    FROM (
        SELECT tt.clientid, tt.current_balance AS latest_balance
        FROM transaction_table tt
        WHERE tt.userid = ?
        AND tt.transactionid = (
            SELECT MAX(transactionid) FROM transaction_table
            WHERE clientid = tt.clientid AND userid = tt.userid
        )
    ) AS latest_balances
");
$balance_stmt->bind_param("i", $userid);
$balance_stmt->execute();
$balance_result = $balance_stmt->get_result()->fetch_assoc();
$total_balance = $balance_result['total_balance'] ?? 0;


echo json_encode([
    'businessname'    => $businessname,
    'total_debited'   => $trans_result['total_debited'],
    'total_credited'  => $trans_result['total_credited'],
    'total_balance'   => $total_balance
]);
?>
