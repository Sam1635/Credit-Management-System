<?php
include('config.php');
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

if (isset($_POST['email']) && isset($_POST['name'])) {
    $email = $_POST['email'];
    $name = $_POST['name'];

    // Check if email already exists in admin table
    $checkQuery = "SELECT * FROM admin WHERE email = '$email'";
    $result = mysqli_query($conn, $checkQuery);

    if (mysqli_num_rows($result) > 0) {
        echo "email_exists";
        exit;
    }

    // Generate and store OTP
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['email'] = $email;
    $_SESSION['business_name'] = $name;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'yourcreditmanagement@gmail.com'; // Your Gmail
        $mail->Password = 'nblc omej aysc ivpc'; // App-specific password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('yourcreditmanagement@gmail.com', 'Credit Management System');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'OTP Code';
        $mail->Body = "
            <h1>OTP for the Credit Management System</h1>
            <h3>Your OTP is: <strong>$otp</strong></h3>";

        $mail->send();

        echo "otp_sent"; 
        exit;
    } catch (Exception $e) {
        echo "otp_failed";
        exit;
    }
} else {
    echo "no_email";
}
?>
