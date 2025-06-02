<?php
session_start();
include 'database.php';

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Only allow access if logged in and is Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

// Get current user info
$stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($current_user['role'] !== 'Admin') {
    echo "<div class='alert alert-danger'>Access denied. Only Admins can access this page.</div>";
    exit();
}

// Handle role update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'], $_POST['role'])) {
    $user_id = intval($_POST['user_id']);
    $role = $_POST['role'] === 'Admin' ? 'Admin' : 'Cashier';
    // Prevent admin from changing their own role
    if ($user_id != $current_user['id']) {
        $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
        $stmt->bind_param("si", $role, $user_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Role updated successfully.";
    } else {
        $msg = "You cannot change your own role.";
    }
}

// Handle user deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user_id'])) {
    $delete_user_id = intval($_POST['delete_user_id']);
    // Prevent admin from deleting themselves
    if ($delete_user_id != $current_user['id']) {
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
            $stmt->bind_param("i", $delete_user_id);
            $stmt->execute();
            $stmt->close();
            $msg = "User deleted successfully.";
            // Refresh users list after deletion
            $users = $conn->query("SELECT * FROM users");
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1451) { // Foreign key constraint violation
                $msg = "Cannot delete user: This user is referenced in other records (e.g., sales, purchases).";
            } else {
                $msg = "Error deleting user: " . $e->getMessage();
            }
        }
    } else {
        $msg = "You cannot delete your own account.";
    }
}

// Fetch all users
$users = $conn->query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Settings - User Roles | BOOK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; }
        .main { max-width: 700px; margin: 40px auto; background: #e7dfcf; border-radius: 10px; padding: 30px; }
        .btn { margin-top: 10px; }
        .sidebar {
            background: #343a40;
            color: white;
            padding: 15px;
            height: 100vh;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px;
            display: block;
        }
        .sidebar a:hover, .sidebar a.active {
            background: #495057;
        }
        .profile {
            display: flex;
            align-items: center;
        }
        .profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <div class="sidebar position-fixed" style="width:220px;">
            <h2 class="text-center" style="font-family:serif;">BOOK</h2>
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <a href="dashboard.php" class="active">Products</a>
                <a href="sales.php">Sales</a>
                <a href="purchase.php">Purchases</a>
                <a href="suppliers.php">Suppliers</a>
                <a href="purchase_return.php">Returns</a>
                <a href="settings.php">Settings</a>
                <a href="logout.php">Logout</a>
            <?php elseif ($_SESSION['role'] === 'Cashier'): ?>
                <a href="sales.php" class="active">Sales</a>
                <a href="logout.php">Logout</a>
            <?php endif; ?>
        </div>
        <div class="main flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>User Role Management</h3>
                <div class="profile">
                    <span>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                    <img src="https://static.vecteezy.com/system/resources/previews/020/765/399/non_2x/default-profile-account-unknown-icon-black-silhouette-free-vector.jpg" alt="Profile">
                </div>
            </div>
            <?php if (isset($msg)): ?>
                <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Current Role</th>
                        <th>Change Role</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td>
                            <?php if ($user['id'] != $current_user['id']): ?>
                            <form method="POST" style="display:inline;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <select name="role" class="form-control" style="width:auto; min-width:110px;">
                                        <option value="Admin" <?= $user['role']=='Admin'?'selected':'' ?>>Admin</option>
                                        <option value="Cashier" <?= $user['role']=='Cashier'?'selected':'' ?>>Cashier</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary" style="width:auto">Change</button>
                               <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger ml-2">Delete</button>
                            </form>
                                </div>
                            </form>
                            
                            <?php else: ?>
                                <span class="text-muted">Cannot change own role</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
             <button onclick="window.location.href='register.php'" class="btn btn-success mt-3">Add user here</button>
        </div>
    </div>
</body>
</html>
