<?php
session_start();
include 'database.php';

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Only allow Cashier or Admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Cashier'])) {
    header("Location: index.php");
    exit();
}

// Fetch products for dropdown
$products = $conn->query("SELECT * FROM products");

// Handle sale submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sale'])) {
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    $sale_date = date('Y-m-d H:i:s');
    $or_number = 'OR' . time();
    $total = 0;

    if (!$user_id) {
        die("User ID not found in session.");
    }

    foreach ($_POST['products'] as $i => $product_id) {
        $qty = intval($_POST['quantities'][$i]);
        $price = floatval($_POST['prices'][$i]);
        $total += $qty * $price;
    }

    // Insert into sales
    $stmt = $conn->prepare("INSERT INTO sales (user_id, sale_date, total) VALUES (?, ?, ?)");
    $stmt->bind_param("isd", $user_id, $sale_date, $total);
    $stmt->execute();
    $sale_id = $stmt->insert_id;
    $stmt->close();

    // Insert sales details and update stock
    foreach ($_POST['products'] as $i => $product_id) {
        $qty = intval($_POST['quantities'][$i]);
        $price = floatval($_POST['prices'][$i]);
        $stmt = $conn->prepare("INSERT INTO sales_details (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $sale_id, $product_id, $qty, $price);
        $stmt->execute();
        $stmt->close();

        // Update product stock
        $conn->query("UPDATE products SET stock = stock - $qty WHERE id = $product_id");
    }

    // Generate printable receipt
    header("Location: sales_receipt.php?sale_id=$sale_id");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Record Sale | BOOK</title>
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
        .text-center { text-align: center; }
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
            <h3>Record Sale</h3>
            <div class="profile">
                <span>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <img src="https://static.vecteezy.com/system/resources/previews/020/765/399/non_2x/default-profile-account-unknown-icon-black-silhouette-free-vector.jpg" alt="Profile">
            </div>
        </div>
        <div class="main" style="max-width: 900px; margin: 40px auto;">
            <div class="mb-4">
                <h5>Products</h5>
                <div class="table-responsive">
                    <table class="table table-bordered" id="productsTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Image</th>
                                <th>Order</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $products->data_seek(0);
                        while($row = $products->fetch_assoc()):
                        ?>
                            <tr data-product-id="<?= $row['id'] ?>"
                                data-product-name="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>"
                                data-product-category="<?= htmlspecialchars($row['category'], ENT_QUOTES) ?>"
                                data-product-price="<?= $row['price'] ?>"
                                data-product-stock="<?= $row['stock'] ?>">
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= number_format($row['price'],2) ?></td>
                                <td><?= $row['stock'] ?></td>
                                <td>
                                    <?php if ($row['image']): ?>
                                        <img src="<?= htmlspecialchars($row['image']) ?>" style="width:50px;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success" onclick="addToOrder(this)">Order</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <form method="POST" id="saleForm">
                <div class="form-group">
                    <label>OR/Invoice Number</label>
                    <input type="text" class="form-control" value="<?= 'OR' . time() ?>" readonly>
                </div>
                <div id="order-section">
                    <label>Order List</label>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="orderTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Unit Price</th>
                                    <th>Stock</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Order rows will be added here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <button type="submit" name="sale" class="btn btn-primary">Record Sale</button>
                </div>
            </form>
            <script>
            // Store order items as an array of objects
            let orderItems = [];

            function addToOrder(btn) {
                const row = btn.closest('tr');
                const id = row.getAttribute('data-product-id');
                if (orderItems.find(item => item.id === id)) {
                    alert('Product already added to order.');
                    return;
                }
                const name = row.getAttribute('data-product-name');
                const category = row.getAttribute('data-product-category');
                const price = parseFloat(row.getAttribute('data-product-price'));
                const stock = parseInt(row.getAttribute('data-product-stock'));
                orderItems.push({id, name, category, price, stock, quantity: 1});
                renderOrderTable();
            }

            function renderOrderTable() {
                const tbody = document.querySelector('#orderTable tbody');
                tbody.innerHTML = '';
                orderItems.forEach((item, idx) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>
                            <input type="hidden" name="products[]" value="${item.id}">
                            ${item.name}
                        </td>
                        <td>${item.category}</td>
                        <td>${item.price.toFixed(2)}</td>
                        <td>${item.stock}</td>
                        <td>
                            <input type="number" name="quantities[]" class="form-control" min="1" max="${item.stock}" value="${item.quantity}" onchange="updateQuantity(${idx}, this)">
                        </td>
                        <td>
                            <input type="number" name="prices[]" class="form-control" min="0" step="0.01" value="${item.price.toFixed(2)}">
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeOrderItem(${idx})">Remove</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            function updateQuantity(idx, input) {
                let val = parseInt(input.value);
                if (isNaN(val) || val < 1) val = 1;
                if (val > orderItems[idx].stock) val = orderItems[idx].stock;
                orderItems[idx].quantity = val;
                input.value = val;
            }

            function removeOrderItem(idx) {
                orderItems.splice(idx, 1);
                renderOrderTable();
            }
            </script>
        </div>
    </div>
</div>
</body>
</html>
