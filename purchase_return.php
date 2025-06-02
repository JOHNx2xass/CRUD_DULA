<?php
session_start();
include 'database.php';

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Only allow Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

// Fetch purchases for dropdown
$purchases = $conn->query("SELECT purchases.id, suppliers.name, purchases.purchase_date FROM purchases JOIN suppliers ON purchases.supplier_id = suppliers.id");

// Handle return submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['return'])) {
    $purchase_id = intval($_POST['purchase_id']);
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1;
    $return_date = date('Y-m-d');
    $reason = $_POST['reason'];

    // Insert into purchase_returns
    $stmt = $conn->prepare("INSERT INTO purchase_returns (purchase_id, user_id, return_date, reason) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $purchase_id, $user_id, $return_date, $reason);
    $stmt->execute();
    $purchase_return_id = $stmt->insert_id;
    $stmt->close();

    // For each returned product
    if (isset($_POST['product_id']) && is_array($_POST['product_id'])) {
        foreach ($_POST['product_id'] as $i => $product_id) {
            $qty = intval($_POST['quantity'][$i]);
            // Validate against original purchase
            $pd = $conn->query("SELECT quantity FROM purchase_details WHERE purchase_id=$purchase_id AND product_id=$product_id")->fetch_assoc();
            if ($pd && $qty > 0 && $qty <= $pd['quantity']) {
                // Insert return detail
                $stmt = $conn->prepare("INSERT INTO purchase_return_details (purchase_return_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $purchase_return_id, $product_id, $qty);
                $stmt->execute();
                $stmt->close();
                // Deduct returned quantity from stock
                $stmt2 = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt2->bind_param("ii", $qty, $product_id);
                $stmt2->execute();
                $stmt2->close();
            }
        }
    }
    echo "<div class='alert alert-success'>Return processed!</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Purchase Return | BOOK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; }
        .main { max-width: 700px; margin: 40px auto; background: #e7dfcf; border-radius: 10px; padding: 30px; }
        .form-group label { font-weight: 500; }
        .btn { margin-top: 10px; }
        .sidebar { background: #343a40; color: white; height: 100vh; padding-top: 20px; }
        .sidebar a { color: white; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { background: #495057; }
        h2 { font-family: serif; }
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
        <h2 class="text-center">BOOK</h2>
        <a href="dashboard.php" class="active">Products</a>
        <?php if ($_SESSION['role'] === 'Admin'): ?>
            <a href="sales.php">Sales</a>
            <a href="purchase.php">Purchases</a>
            <a href="suppliers.php">Suppliers</a>
            <a href="purchase_return.php">Returns</a>
            <a href="settings.php">Settings</a>
        <?php elseif ($_SESSION['role'] === 'Cashier'): ?>
            <a href="sales.php">Sales</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    </div>
    <div class="main flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Purchase Returns</h3>
            <div class="profile">
                <span>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <img src="https://static.vecteezy.com/system/resources/previews/020/765/399/non_2x/default-profile-account-unknown-icon-black-silhouette-free-vector.jpg" alt="Profile">
            </div>
        </div>
        <form method="POST">
            <div class="form-group">
                <label>Invoice</label>
                <select name="purchase_id" class="form-control" required onchange="fetchProducts(this.value)">
                    <option value="">Select Invoice</option>
                    <?php while($row = $purchases->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?> (<?= $row['purchase_date'] ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div id="products-section"></div>
            <div class="form-group">
                <label>Reason</label>
                <input type="text" name="reason" class="form-control" required>
            </div>
            <button type="submit" name="return" class="btn btn-primary">Process Return</button>
        </form>
        <script>
        function fetchProducts(purchaseId) {
            var section = document.getElementById('products-section');
            section.innerHTML = '';
            if (!purchaseId) return;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'purchase_products.php?purchase_id=' + purchaseId, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    section.innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }
        </script>
    </div>
</div>
</body>
</html>
