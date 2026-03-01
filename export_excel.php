<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require "./config/db_config.php";

checkLogin();

$conn = getDBConnection();

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=products.xls");

echo "ID\tName\tCategory\tUnit\tPrice\tQuantity\tReorder Level\tStatus\n";

$result = $conn->query("SELECT * FROM product ORDER BY product_id DESC");

while ($row = $result->fetch_assoc()) {
    echo $row['product_id'] . "\t";
    echo $row['product_name'] . "\t";
    echo $row['product_category'] . "\t";
    echo $row['unit'] . "\t";
    echo $row['selling_price'] . "\t";
    echo $row['quantity'] . "\t";
    echo $row['reorder_level'] . "\t";
    echo $row['status'] . "\n";
}

closeDBConnection($conn);
exit;
