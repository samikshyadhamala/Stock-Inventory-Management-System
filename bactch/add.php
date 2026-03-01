<?php
require "../config/db_config.php";

if ($_POST) {
    $product_id = $_POST['product_id'];
    $batch_no = $_POST['batch_no'];
    $qty = $_POST['quantity_received'];
    $expiry = $_POST['expire_date'];

    $stmt = $conn->prepare(
        "INSERT INTO batches (product_id, batch_no, quantity_received, expire_date)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("isis", $product_id, $batch_no, $qty, $expiry);
    $stmt->execute();

    // update product stock
    $conn->query(
        "UPDATE product SET quantity = quantity + $qty WHERE product_id = $product_id"
    );
}
?>
