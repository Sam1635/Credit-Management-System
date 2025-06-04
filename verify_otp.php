<?php
include('config.php');
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

if (isset($_POST['otp'])) {
    $entered_otp = $_POST['otp'];
    $stored_otp = $_SESSION['otp'] ?? '';
    $email = $_SESSION['email'] ?? '';

    if ($entered_otp == $stored_otp) {
        // OTP is correct — generate credentials
        $businessName = $_SESSION['business_name'] ?? 'User';
        $username = generateUsername($businessName);
        $password = generatePassword(10); // plain password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT); // secure storage

        $_SESSION['username'] = $username;
        $_SESSION['password'] = $password;

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'yourcreditmanagement@gmail.com';
            $mail->Password = 'nblc omej aysc ivpc';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('yourcreditmanagement@gmail.com', 'Credit Management System');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Your Account Credentials';
            $mail->Body    = "
            <center>
                <h1>Welcome to credit management system!</h1>
                <h3>Your Account Has Been Created Successfully</h3>
                <p><strong>Username:</strong> $username</p>
                <p><strong>Password:</strong> $password</p>
            </center>
            ";

            $mail->send();
            echo '<div id="otpMessage"><h2>✅ OTP Verified! Credentials sent to your email.</h2></div>';

        } catch (Exception $e) {
            echo '<div id="otpMessage"><h2>❌ Failed to send credentials. Mailer Error: ' . $mail->ErrorInfo . '</h2></div>';
        }

        // Insert into database
        if ($conn->connect_error) {
            die('<div id="otpMessage"><h2>❌ Database connection failed: ' . $conn->connect_error . '</h2></div>');
        }

        $roleid = 1;
        $stmt = $conn->prepare("INSERT INTO admin (businessname, username, password, email, roleid) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $businessName, $username, $hashedPassword, $email, $roleid);

        if ($stmt->execute()) {
            // Optionally show success
            // echo '<div id="otpMessage"><h2>✅ User stored in database.</h2></div>';
        } else {
            echo '<div id="otpMessage"><h2>❌ Database insert error: ' . $stmt->error . '</h2></div>';
        }

        $stmt->close();
        $conn->close();

    } else {
        echo '<div id="otpMessage"><h2>❌ Invalid OTP. Please try again.</h2></div>';
    }

} else {
    echo '<div id="otpMessage"><h2>No OTP received.</h2></div>';
}

echo '
<script>
    setTimeout(() => {
        const msg = document.getElementById("otpMessage");
        if (msg) {
            msg.classList.add("fade-out");
            setTimeout(() => msg.style.display = "none", 500); // after fade
        }
    }, 5000);
</script>';

function generateUsername($businessName) {
    $namePart = preg_replace('/[^A-Za-z]/', '', $businessName); // Remove non-letters
    $namePart = substr($namePart, 0, 5); // Limit to 5 chars max
    $randomDigit = rand(10, 99); // 2-digit suffix
    return strtolower($namePart) . $randomDigit;
}

function generatePassword($length = 8) {
    $upper = chr(rand(65, 90));
    $lower = chr(rand(97, 122));
    $digit = chr(rand(48, 57));
    $specials = '@#$%&';
    $special = $specials[rand(0, strlen($specials) - 1)];

    $all = $upper . $lower . $digit . $special;
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' . $specials;
    for ($i = 0; $i < $length - 4; $i++) {
        $all .= $chars[rand(0, strlen($chars) - 1)];
    }

    return str_shuffle($all);
}
?>
