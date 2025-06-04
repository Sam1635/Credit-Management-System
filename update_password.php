<?php
session_start();
include 'config.php'; // Ensure this contains DB connection ($conn)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($otp != $_SESSION['otp']) {
        die("Invalid OTP.");
    }

    if ($newPassword !== $confirmPassword) {
        die("Passwords do not match.");
    }

    $email = $_SESSION['reset_email'];
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $email);
    if ($stmt->execute()) {
        unset($_SESSION['otp'], $_SESSION['reset_email']);
        header("Location: signin.html");
        exit();
    } else {
        echo "Error updating password.";
    }
}
?>
