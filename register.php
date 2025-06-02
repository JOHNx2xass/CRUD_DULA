<?php
include 'database.php';
session_start(); 

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hashing password for security
    $role = $_POST['role'];

    // Check if the username already exists in the database
    $check_username = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($check_username);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Username already exists
        echo "Error: Username already taken. Please choose a different username.";
    } else {
        // Insert new user into the database
        $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $password, $role);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Registration successful!";
            header("Location: index.php"); // Redirect to login page after registration
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form | BOOK</title>
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
        <div class="title">Register</div>
        <form method="POST" action="register.php">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter your username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Create password" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control" required>
                    <option value="" disabled selected>Select role</option>
                    <option value="Admin">Admin</option>
                    <option value="Cashier">Cashier</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary btn-block">Register Now</button>
        </form>
    </div>
</body>
</html>
