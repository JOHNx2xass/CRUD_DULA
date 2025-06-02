<?php
include 'database.php';
session_start(); // Start the session

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username=?";
    $stmt = $conn->prepare($sql); // Use prepared statements
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // Store user role in session
            $_SESSION['user_id'] = $user['id']; // Store user id in session
            if ($user['role'] === 'Cashier') {
                header("Location: sales.php"); // Redirect Cashier to sales.php
            } else {
                header("Location: dashboard.php"); // Redirect Admin to dashboard.php
            }
            exit();
        } else {
            echo "Incorrect password.";
        }
    } else {
        echo "Username not found.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form | BOOK</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; }
        .main { max-width: 400px; margin: 60px auto; background: #e7dfcf; border-radius: 10px; padding: 30px; }
        .title { font-family: serif; font-size: 2em; text-align: center; margin-bottom: 20px; }
        .btn { margin-top: 10px; }
        .signup-link { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="main">
        <div class="title">Login Form</div>
        <form method="POST" action="index.php">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>    
        </form>
    </div>
</body>
</html>
