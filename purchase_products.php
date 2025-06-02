<?php
include 'database.php';
if (!isset($_GET['purchase_id'])) exit;
$purchase_id = intval($_GET['purchase_id']);
$res = $conn->query("SELECT pd.product_id, p.name, pd.quantity FROM purchase_details pd JOIN products p ON pd.product_id=p.id WHERE pd.purchase_id=$purchase_id");
if ($res->num_rows > 0) {
    echo '<label>Products</label>';
    while($row = $res->fetch_assoc()) {
        echo '<div class="form-row mb-2">';
        echo '<div class="col-6"><input type="hidden" name="product_id[]" value="'.$row['product_id'].'">'.htmlspecialchars($row['name']).'</div>';
        echo '<div class="col-6"><input type="number" name="quantity[]" class="form-control" max="'.$row['quantity'].'" min="0" placeholder="Return Qty (max '.$row['quantity'].')" value="0"></div>';
        echo '</div>';
    }
}
?>
