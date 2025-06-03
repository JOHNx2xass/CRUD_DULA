<?php
session_start();
include 'database.php';

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Only allow Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin' || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch suppliers and products for dropdowns
$suppliers = $conn->query("SELECT * FROM suppliers");
$products = $conn->query("SELECT * FROM products");

// Handle purchase submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['purchase'])) {
    $supplier_id = intval($_POST['supplier_id']);
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    $purchase_date = $_POST['purchase_date'];
    $invoice_number = $_POST['invoice_number'];
    $total = 0;

    // Calculate total
    foreach ($_POST['products'] as $i => $product_id) {
        $qty = intval($_POST['quantities'][$i]);
        $price = floatval($_POST['prices'][$i]);
        $total += $qty * $price;
    }

    // Insert into purchases
    $stmt = $conn->prepare("INSERT INTO purchases (supplier_id, user_id, purchase_date, total) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisd", $supplier_id, $user_id, $purchase_date, $total);
    $stmt->execute();
    $purchase_id = $stmt->insert_id;
    $stmt->close();

    // Insert purchase details and update stock
    foreach ($_POST['products'] as $i => $product_id) {
        $qty = intval($_POST['quantities'][$i]);
        $price = floatval($_POST['prices'][$i]);
        $stmt = $conn->prepare("INSERT INTO purchase_details (purchase_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $purchase_id, $product_id, $qty, $price);
        $stmt->execute();
        $stmt->close();

        // Update product stock
        $conn->query("UPDATE products SET stock = stock + $qty WHERE id = $product_id");
    }

    echo "<div class='alert alert-success'>Purchase recorded successfully!</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Record Purchase | BOOK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; }
        .main { max-width: 700px; margin: 40px auto; background: #e7dfcf; border-radius: 10px; padding: 30px; }
        .form-group label { font-weight: 500; }
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
            <h3>Record Purchase</h3>
            <div class="profile">
                <span>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <img src="https://static.vecteezy.com/system/resources/previews/020/765/399/non_2x/default-profile-account-unknown-icon-black-silhouette-free-vector.jpg" alt="Profile">
            </div>
        </div>
        <div class="main" style="max-width: 700px; margin: 40px auto;">
            <form method="POST">
                <div class="form-group">
                    <label>Supplier</label>
                    <select name="supplier_id" class="form-control" required>
                        <option value="">Select Supplier</option>
                        <?php while($row = $suppliers->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Invoice Number</label>
                    <input type="text" name="invoice_number" class="form-control" value="<?= 'INV' . time() ?>" required>
                </div>
                <div id="products-section">
                    <label>Products</label>
                    <div class="form-row mb-2">
                        <div class="col">
                            <select name="products[]" class="form-control" required>
                                <option value="">Select Product</option>
                                <?php
                                $products->data_seek(0);
                                while($row = $products->fetch_assoc()): ?>
                                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col">
                            <input type="number" name="quantities[]" class="form-control" placeholder="Quantity" min="1" required>
                        </div>
                        <div class="col">
                            <input type="number" name="prices[]" class="form-control" placeholder="Unit Price" min="0" step="0.01" required>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="addProductRow()">Add Another Product</button>
                <div>
                    <button type="submit" name="purchase" class="btn btn-primary">Record Purchase</button>
                </div>
            </form>
            <script>
            function addProductRow() {
                var section = document.getElementById('products-section');
                var row = section.children[1].cloneNode(true);
                // Clear values
                row.querySelectorAll('input,select').forEach(function(el){ el.value = ''; });
                section.appendChild(row);
            }
            </script>
        </div>
    </div>
</div>
</body>
</html>
