<?php
session_start();
include('config.php');
require 'vendor/autoload.php'; // PHPMailer

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $enteredOtp = $_POST['otp'];

    if (isset($_SESSION['otp']) && $_SESSION['otp'] == $enteredOtp) {
        $email = $_SESSION['email'];

        // Change to MySQLi
        $stmt = $conn->prepare("SELECT password FROM admin WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            $password = $user['password']; // Assuming password is in plaintext (not recommended)

            // Send password to email
            $mail = new PHPMailer\PHPMailer\PHPMailer();
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Use your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'yourcreditmanagement@gmail.com'; // Your email address
                $mail->Password = 'nblc omej aysc ivpc'; // App-specific password
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->setFrom('yourcreditmanagement@gmail.com', 'Credit Management System');
                $mail->addAddress($email);
                $mail->Subject = 'Your Password for Credit Management System';
                $mail->Body = "Your password is: $password";
                if ($mail->send()) {
                    unset($_SESSION['otp']); // Clear OTP
                    unset($_SESSION['email']);
                    echo "<script>alert('OTP verified! Password has been sent to your email.'); window.location.href = 'signin.html';</script>";
                    exit();
                } else {
                    echo 'Mailer Error: ' . $mail->ErrorInfo;
                }
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo 'No user found.';
        }
    } else {
        echo 'Invalid OTP. Please try again.';
    }
    }

?>
