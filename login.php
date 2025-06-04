<?php
include('config.php');
session_start(); // Start the session to manage login state

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';  // Get the username from the form
    $password = $_POST['password'] ?? '';  // Get the password from the form

    // Check if the connection was successful
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }

    // Prepare the SQL query to check for the provided credentials
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);  // Bind the parameters
    $stmt->execute();  // Execute the query
    $result = $stmt->get_result();  // Get the result of the query

    // If the result has exactly one row, login is successful
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();  // Fetch the result as an associative array
        
        // Set the session variables for the logged-in user
        $_SESSION['userid'] = $row['userid'];
        $_SESSION['username'] = $row['username'];  // You can also store the username or other info in session
        
        // Redirect the user to the home page after successful login
        header("Location: home.html");
        exit;
    } else {
        // Invalid login
        echo "
        <script>
            alert('❌ Incorrect username or password');
            window.history.back();  // Go back to the previous page (login page)
        </script>";
    }
    // Close the statement and the connection
    $stmt->close();
    $conn->close();
}
?>
