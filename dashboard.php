<?php
session_start();
include 'database.php';

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);

    // Delete related rows in detail tables first
    $conn->query("DELETE FROM purchase_details WHERE product_id=$product_id");
    $conn->query("DELETE FROM sales_details WHERE product_id=$product_id");
    $conn->query("DELETE FROM return_details WHERE product_id=$product_id");
    $conn->query("DELETE FROM purchase_return_details WHERE product_id=$product_id");

    $sql = "DELETE FROM products WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php");
    exit();
}

// Handle add product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $image = '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir);
        $image = $target_dir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $image);
    }

    $stmt = $conn->prepare("INSERT INTO products (name, category, price, stock, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdis", $name, $category, $price, $stock, $image);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php");
    exit();
}

// Handle edit product
$edit_mode = false;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['edit']);
    $sql = "SELECT * FROM products WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_product = $result->fetch_assoc();
    } else {
        $edit_mode = false;
    }
    $stmt->close();
}

// Handle update product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $edit_id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);

    // Get current image
    $sql = "SELECT image FROM products WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $stmt->bind_result($current_image);
    $stmt->fetch();
    $stmt->close();

    $image = $current_image;
    // Handle image upload if a new image is provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir);
        $image = $target_dir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $image);
    }

    $sql = "UPDATE products SET name=?, category=?, price=?, stock=?, image=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdisi", $name, $category, $price, $stock, $image, $edit_id);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php");
    exit();
}

// Fetch products
$result = $conn->query("SELECT * FROM products");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products | BOOK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- Responsive meta tag -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; }
        .sidebar { background: #d6c7a1; min-height: 100vh; padding: 30px 0; }
        .sidebar a { color: #333; display: block; padding: 10px 30px; text-decoration: none; }
        .sidebar a.active, .sidebar a:hover { background: #b8a77a; color: #fff; }
        .profile { float: right; }
        .profile img { border-radius: 50%; width: 40px; }
        .main { margin-left: 220px; padding: 30px; }
        .product-form, .product-table { background: #e7dfcf; border-radius: 10px; padding: 20px; }
        .product-table th, .product-table td { background: #d6c7a1; }
        .btn { margin-right: 5px; }
        @media (max-width: 991.98px) {
            .main { margin-left: 0; padding: 10px; }
            .sidebar { position: static; width: 100%; min-height: auto; padding: 10px 0; }
        }
        /* Low stock row coloring */
        .low-stock { background-color: #f8d7da !important; }
        .table-responsive { margin-bottom: 0; }
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
            <h3>Products</h3>
            <div class="profile">
                <span>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <img src="https://static.vecteezy.com/system/resources/previews/020/765/399/non_2x/default-profile-account-unknown-icon-black-silhouette-free-vector.jpg" alt="Profile">
            </div>
        </div>
        <div class="product-form mb-4">
            <?php if ($edit_mode): ?>
                <h4>Edit Product</h4>
                <form method="POST" action="dashboard.php?edit=<?= $edit_product['id'] ?>" enctype="multipart/form-data">
                    <input type="hidden" name="update_product" value="1">
                    <input type="hidden" name="id" value="<?= $edit_product['id'] ?>">
                    <div>
                        <div class="form-group">
                            <label>Name:</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($edit_product['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Category:</label>
                            <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($edit_product['category']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Price:</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="<?= htmlspecialchars($edit_product['price']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Stock:</label>
                            <input type="number" name="stock" class="form-control" value="<?= htmlspecialchars($edit_product['stock']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Image:</label>
                            <?php if ($edit_product['image']): ?>
                                <div>
                                    <img src="<?= htmlspecialchars($edit_product['image']) ?>" style="width:50px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control-file">
                        </div>
                        <div class="form-group d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="dashboard.php" class="btn btn-secondary ml-2">Cancel</a>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <h4>Add Product</h4>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_product" value="1">
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">Name:</label>
                        <div class="col-sm-10">
                            <input type="text" name="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">Category:</label>
                        <div class="col-sm-10">
                            <input type="text" name="category" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">Price:</label>
                        <div class="col-sm-10">
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">Stock:</label>
                        <div class="col-sm-10">
                            <input type="number" name="stock" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">Image:</label>
                        <div class="col-sm-10">
                            <input type="file" name="image" class="form-control-file">
                        </div>
                    </div>
                    <div class="form-group d-flex justify-content-end align-items-end">
                        <button type="submit" class="btn btn-secondary">Add</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <div class="product-table">
            <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Image</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $result->fetch_assoc()): 
                    $low_stock = $row['stock'] <= 5; // Set your low-stock threshold here
                ?>
                    <tr<?= $low_stock ? ' class="low-stock"' : '' ?>>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td><?= number_format($row['price'],2) ?></td>
                        <td>
                            <?= $row['stock'] ?>
                            <?php if ($low_stock): ?>
                                <span class="badge badge-danger ml-2">Low</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['image']): ?>
                                <img src="<?= htmlspecialchars($row['image']) ?>" style="width:40px; height:40px;">
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="dashboard.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                            <a href="dashboard.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
