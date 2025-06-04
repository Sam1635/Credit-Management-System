<?php
header('Content-Type: application/json');
include('config.php');
session_start();

// Check if user is logged in using the session variable from login.php
if (!isset($_SESSION['userid'])) {
    die(json_encode(['error' => 'User not logged in']));
}

$userid = $_SESSION['userid'];

// Handle POST requests (password changes and profile updates)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Password change request
    if (isset($_POST['old_password']) && isset($_POST['new_password'])) {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        
        // Verify old password (plaintext comparison since your login checks plaintext)
        $stmt = $conn->prepare("SELECT password FROM admin WHERE userid = ?");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Compare plaintext passwords (since your login does this)
            if ($old_password === $user['password']) {
                // Update password (store plaintext - NOT RECOMMENDED FOR PRODUCTION)
                $update_stmt = $conn->prepare("UPDATE admin SET password = ? WHERE userid = ?");
                $update_stmt->bind_param("si", $new_password, $userid);
                
                if ($update_stmt->execute()) {
                    echo json_encode(['success' => 'Password updated successfully']);
                } else {
                    echo json_encode(['error' => 'Failed to update password']);
                }
            } else {
                echo json_encode(['error' => 'Incorrect old password']);
            }
        } else {
            echo json_encode(['error' => 'User not found']);
        }
        exit;
    }
    
    // Profile update request
    if (isset($_POST['business_name']) || isset($_POST['email'])) {
        $business_name = $_POST['business_name'] ?? null;
        $email = $_POST['email'] ?? null;
        
        // Check if admin table has these columns (adjust as needed)
        $update_fields = [];
        $params = [];
        $types = '';
        
        if ($business_name !== null) {
            $update_fields[] = "username = ?";
            $params[] = $business_name;
            $types .= 's';
        }
        
        if ($email !== null) {
            $update_fields[] = "email = ?";
            $params[] = $email;
            $types .= 's';
        }
        
        if (!empty($update_fields)) {
            $params[] = $userid;
            $types .= 'i';
            
            $sql = "UPDATE admin SET " . implode(', ', $update_fields) . " WHERE userid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => 'Profile updated successfully']);
            } else {
                echo json_encode(['error' => 'Failed to update profile']);
            }
            exit;
        }
    }
}

// GET request - fetch profile data
$profile_data = [];

// Get admin info (using your admin table structure)
$stmt = $conn->prepare("SELECT username, email FROM admin WHERE userid = ?");
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    // Map username to business_name for frontend
    $profile_data['business_name'] = $user['username'];
    $profile_data['email'] = $user['email'] ?? 'Not set';
    
    // Get group count - using correct column name (userid instead of user_id)
    $group_count = 0;
    if ($group_stmt = $conn->prepare("SELECT COUNT(*) as group_count FROM user_group WHERE userid = ?")) {
        $group_stmt->bind_param("i", $userid);
        $group_stmt->execute();
        $group_result = $group_stmt->get_result();
        $group_data = $group_result->fetch_assoc();
        $group_count = $group_data['group_count'] ?? 0;
    }
    $profile_data['group_count'] = $group_count;
    
    // Get customer count - fixed query to use correct column name
    $customer_count = 0;
    if ($customer_stmt = $conn->prepare("SELECT COUNT(DISTINCT clientid) as customer_count FROM transaction_table WHERE userid = ?")) {
        $customer_stmt->bind_param("i", $userid);
        $customer_stmt->execute();
        $customer_result = $customer_stmt->get_result();
        $customer_data = $customer_result->fetch_assoc();
        $customer_count = $customer_data['customer_count'] ?? 0;
    }
    $profile_data['customer_count'] = $customer_count;
    
    // Get total balance - fixed query (latest current_balance per clientid)
    $total_balance = 0;
    if ($balance_stmt = $conn->prepare("
        SELECT SUM(t.current_balance) AS total_balance
        FROM transaction_table t
        INNER JOIN (
            SELECT clientid, MAX(transactionid) AS latest_transaction
            FROM transaction_table
            WHERE userid = ?
            GROUP BY clientid
        ) latest ON t.transactionid = latest.latest_transaction
        WHERE t.userid = ?
    ")) {
        $balance_stmt->bind_param("ii", $userid, $userid);
        $balance_stmt->execute();
        $balance_result = $balance_stmt->get_result();
        $balance_data = $balance_result->fetch_assoc();
        $total_balance = $balance_data['total_balance'] ?? 0;
    }
    $profile_data['total_balance'] = number_format($total_balance, 2);
    
} else {
    $profile_data['error'] = 'User not found';
}

// Password change request
if (isset($_POST['old_password'])) {
    $old_password = $_POST['old_password'];
    $verify_only = isset($_POST['verify_only']);
    
    // Verify old password (plaintext comparison since your login checks plaintext)
    $stmt = $conn->prepare("SELECT password FROM admin WHERE userid = ?");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Compare plaintext passwords (since your login does this)
        if ($old_password === $user['password']) {
            if ($verify_only) {
                // Just verifying, not changing yet
                echo json_encode(['success' => true]);
                exit;
            }
            
            // Actual password change
            if (isset($_POST['new_password'])) {
                $new_password = $_POST['new_password'];
                $update_stmt = $conn->prepare("UPDATE admin SET password = ? WHERE userid = ?");
                $update_stmt->bind_param("si", $new_password, $userid);
                
                if ($update_stmt->execute()) {
                    echo json_encode(['success' => 'Password updated successfully']);
                } else {
                    echo json_encode(['error' => 'Failed to update password']);
                }
            }
        } else {
            echo json_encode(['error' => 'Incorrect old password']);
        }
    } else {
        echo json_encode(['error' => 'User not found']);
    }
    exit;
}

// Profile update request
if (isset($_POST['update_profile'])) {
    $business_name = $_POST['business_name'] ?? null;
    $email = $_POST['email'] ?? null;
    
    // Get user's roleid from session or database
    $roleid = $_SESSION['roleid'] ?? null;
    if (!$roleid) {
        // Fallback to query if not in session
        $stmt = $conn->prepare("SELECT roleid FROM admin WHERE userid = ?");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $roleid = $user['roleid'] ;
    }

    if (!$roleid) {
        echo json_encode(['error' => 'Unable to verify user role']);
        exit;
    }

    // Prepare and execute the update query
    $stmt = $conn->prepare("UPDATE admin SET username = ?, email = ? WHERE userid = ? AND roleid = ?");
    $stmt->bind_param("ssii", $business_name, $email, $userid, $roleid);
    
    if ($stmt->execute()) {
        // Update successful
        $_SESSION['username'] = $business_name; // Update session if needed
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to update profile: ' . $conn->error]);
    }
    exit;
}

echo json_encode($profile_data);

$conn->close();
?>
