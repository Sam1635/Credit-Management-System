<?php
session_start();

// Check if OTP is set in the session
if (!isset($_SESSION['otp'])) {
    header("Location: forget_password.html");
    exit();
}

// Validate the OTP
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST['otp'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($entered_otp == $_SESSION['otp']) {
        if ($new_password == $confirm_password) {
            // Update the password in the database
            $email = $_SESSION['email'];
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            // Replace with your actual DB connection and query
            $conn = new mysqli('localhost', 'username', 'password', 'dbname');
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $sql = "UPDATE users SET password = '$hashed_password' WHERE email = '$email'";
            if ($conn->query($sql) === TRUE) {
                echo "Password updated successfully!";
                session_unset();
                session_destroy();
                header("Location: signin.html");  // Redirect to sign-in page
                exit();
            } else {
                echo "Error updating password: " . $conn->error;
            }

            $conn->close();
        } else {
            echo "Passwords do not match.";
        }
    } else {
        echo "Incorrect OTP.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reset Password</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .container {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
    }
    input, button {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
    button {
      background-color: #28a745;
      color: white;
      border: none;
      font-size: 16px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Reset Your Password</h2>
    <form method="POST">
      <label for="otp">Enter OTP:</label>
      <input type="text" name="otp" required />
      <label for="new_password">New Password:</label>
      <input type="password" name="new_password" required />
      <label for="confirm_password">Confirm New Password:</label>
      <input type="password" name="confirm_password" required />
      <button type="submit">Reset Password</button>
    </form>
  </div>
</body>
</html>
