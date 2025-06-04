<?php
session_start();
include('config.php');
require 'vendor/autoload.php'; // For PHPMailer

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['name'];
    $email = $_POST['email'];

    // Change to MySQLi
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? AND email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $otp = mt_rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['email'] = $email;

        $mail = new PHPMailer\PHPMailer\PHPMailer();
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'yourcreditmanagement@gmail.com'; // Replace with your email
            $mail->Password = 'nblc omej aysc ivpc'; // App password
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('yourcreditmanagement@gmail.com', 'Credit Management System');
            $mail->addAddress($email);
            $mail->Subject = 'OTP for Password Reset';
            $mail->Body = "Your OTP is: $otp";

            if ($mail->send()) {
                echo 'OTP sent';
            } else {
                echo 'Mailer Error: ' . $mail->ErrorInfo;
            }
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
        // Rest of your existing OTP code...
    } else {
        echo 'Username or email is incorrect';
    }
}
?>
