<?php
session_start();
include 'database.php';

// Only allow Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

// Handle add supplier
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_supplier'])) {
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);
    $stmt = $conn->prepare("INSERT INTO suppliers (name, contact, address) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $contact, $address);
    $stmt->execute();
    $stmt->close();
    header("Location: suppliers.php");
    exit();
}

// Handle delete supplier
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $stmt = $conn->prepare("DELETE FROM suppliers WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: suppliers.php");
        exit();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) { // Foreign key constraint violation
            echo "<div class='alert alert-danger text-center'>Cannot delete supplier: This supplier is referenced in purchases. Remove related purchases first.</div>";
        } else {
            echo "<div class='alert alert-danger text-center'>Error deleting supplier: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// Handle edit supplier
$edit_mode = false;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_supplier = $result->fetch_assoc();
    $stmt->close();
}

// Handle update supplier
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_supplier'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);
    $stmt = $conn->prepare("UPDATE suppliers SET name=?, contact=?, address=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $contact, $address, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: suppliers.php");
    exit();
}

// Fetch all suppliers
$suppliers = $conn->query("SELECT * FROM suppliers");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Suppliers | BOOK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; }
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
        .main {
            margin-left: 240px;
            max-width: 900px;
            margin-top: 40px;
            margin-bottom: 40px;
            background: #e7dfcf;
            border-radius: 10px;
            padding: 30px 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .supplier-form {
            background: #f8f6f1;
            border-radius: 8px;
            padding: 24px 20px 16px 20px;
            margin-bottom: 32px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .supplier-form h4 {
            margin-bottom: 18px;
        }
        .supplier-table {
            background: #f8f6f1;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .supplier-table th, .supplier-table td {
            background: #d6c7a1;
            vertical-align: middle !important;
        }
        .btn {
            margin-top: 6px;
        }
        @media (max-width: 991.98px) {
            .main {
                margin-left: 0;
                padding: 10px;
                max-width: 100%;
            }
            .sidebar {
                position: static !important;
                width: 100%;
                height: auto;
                margin-bottom: 20px;
            }
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
        <?php
            $current = basename($_SERVER['PHP_SELF']);
        ?>
        <?php if ($_SESSION['role'] === 'Admin'): ?>
            <a href="dashboard.php" class="<?= $current == 'dashboard.php' ? 'active' : '' ?>">Products</a>
            <a href="sales.php" class="<?= $current == 'sales.php' ? 'active' : '' ?>">Sales</a>
            <a href="purchase.php" class="<?= $current == 'purchase.php' ? 'active' : '' ?>">Purchases</a>
            <a href="suppliers.php" class="<?= $current == 'suppliers.php' ? 'active' : '' ?>">Suppliers</a>
            <a href="purchase_return.php" class="<?= $current == 'purchase_return.php' ? 'active' : '' ?>">Returns</a>
            <a href="settings.php" class="<?= $current == 'settings.php' ? 'active' : '' ?>">Settings</a>
            <a href="logout.php">Logout</a>
        <?php elseif ($_SESSION['role'] === 'Cashier'): ?>
            <a href="sales.php" class="<?= $current == 'sales.php' ? 'active' : '' ?>">Sales</a>
            <a href="logout.php">Logout</a>
        <?php endif; ?>
    </div>
    <div class="main flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-4 text-center" style="font-family:serif;">Suppliers</h2>
            <div class="profile">
                <span>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <img src="https://static.vecteezy.com/system/resources/previews/020/765/399/non_2x/default-profile-account-unknown-icon-black-silhouette-free-vector.jpg" alt="Profile">
            </div>
        </div>
        <div class="supplier-form mb-4">
            <?php if ($edit_mode): ?>
                <h4>Edit Supplier</h4>
                <form method="POST" action="suppliers.php?edit=<?= $edit_supplier['id'] ?>">
                    <input type="hidden" name="update_supplier" value="1">
                    <input type="hidden" name="id" value="<?= $edit_supplier['id'] ?>">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($edit_supplier['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Contact:</label>
                        <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($edit_supplier['contact']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Address:</label>
                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($edit_supplier['address']) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="suppliers.php" class="btn btn-secondary ml-2">Cancel</a>
                </form>
            <?php else: ?>
                <h4>Add Supplier</h4>
                <form method="POST">
                    <input type="hidden" name="add_supplier" value="1">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Contact:</label>
                        <input type="text" name="contact" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Address:</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-secondary">Add</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="supplier-table table-responsive mb-3">
            <table class="table table-bordered mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th style="width:140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $suppliers->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['contact']) ?></td>
                        <td><?= htmlspecialchars($row['address']) ?></td>
                        <td>
                            <a href="suppliers.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary mb-1">Edit</a>
                            <a href="suppliers.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('Delete this supplier?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="text-center">
            <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
