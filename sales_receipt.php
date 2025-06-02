<?php
include 'database.php';
session_start();

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Cashier'])) {
    header("Location: index.php");
    exit();
}
if (!isset($_GET['sale_id'])) exit('No sale ID.');
$sale_id = intval($_GET['sale_id']);
$sale = $conn->query("SELECT * FROM sales WHERE id=$sale_id")->fetch_assoc();
$user = $conn->query("SELECT username FROM users WHERE id={$sale['user_id']}")->fetch_assoc();
$details = $conn->query("SELECT sd.*, p.name FROM sales_details sd JOIN products p ON sd.product_id=p.id WHERE sd.sale_id=$sale_id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sales Receipt | BOOK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; }
        .main { max-width: 700px; margin: 40px auto; background: #e7dfcf; border-radius: 10px; padding: 30px; }
        .btn { margin-top: 10px; }
    </style>
</head>
<body class="p-4">
    <div class="main">
        <h3>Sales Receipt</h3>
        <p><strong>OR/Invoice #:</strong> <?= $sale['id'] ?></p>
        <p><strong>Date:</strong> <?= $sale['sale_date'] ?></p>
        <p><strong>Cashier:</strong> <?= htmlspecialchars($user['username']) ?></p>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th>
                </tr>
            </thead>
            <tbody>
            <?php $grand = 0; while($row = $details->fetch_assoc()): $line = $row['quantity'] * $row['price']; $grand += $line; ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= $row['quantity'] ?></td>
                    <td><?= number_format($row['price'],2) ?></td>
                    <td><?= number_format($line,2) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">Grand Total</th>
                    <th><?= number_format($grand,2) ?></th>
                </tr>
            </tfoot>
        </table>
        <button onclick="window.print()" class="btn btn-secondary">Print</button>
    </div>
</body>
</html>
