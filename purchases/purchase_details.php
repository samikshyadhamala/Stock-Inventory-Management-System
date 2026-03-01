<?php
require "../config/db_config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

checkLogin();

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: purchase_orders.php");
    exit();
}

$conn = getDBConnection();

$po_id = (int) $_GET['id'];

/* ============================
   FETCH PURCHASE ORDER
============================ */
$poQuery = $conn->prepare("
    SELECT 
        po.purchase_id,
        po.order_date,
        po.expected_delivery,
        po.status,
        s.supplier_name,
        s.supplier_phone,
        s.supplier_email
    FROM purchase_order po
    JOIN supplier s ON po.supplier_id = s.supplier_id
    WHERE po.purchase_id = ?
");
$poQuery->bind_param("i", $po_id);
$poQuery->execute();
$po = $poQuery->get_result()->fetch_assoc();

if (!$po) {
    header("Location: purchase_orders.php");
    exit();
}

/* ============================
   FETCH PURCHASE ITEMS
============================ */
$itemsQuery = $conn->prepare("
    SELECT 
        pi.quantity_order,
        pi.unit_price,
        p.product_name,
        p.product_category,
        p.unit
    FROM purchased_item pi
    JOIN product p ON pi.product_id = p.product_id
    WHERE pi.purchase_id = ?
");
$itemsQuery->bind_param("i", $po_id);
$itemsQuery->execute();
$items = $itemsQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">

<div class="header">
    <h1>Purchase Order Details</h1>

    <div class="header-actions">
        <a href="purchase_orders.php" class="btn btn-warning">← Back</a>
        <button type="button" class="btn btn-success" onclick="window.print()">🖨️ Print </button>
    </div>
</div>

    <!-- PO INFO -->
    <div class="invoice-header" style="background:#fff;padding:25px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:20px;">
        <h2>Purchase Order #<?php echo $po['purchase_id']; ?></h2>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;">

            <div>
                <h3>Order Information</h3>
                <p><strong>Order Date:</strong> <?php echo formatDate($po['order_date']); ?></p>
                <p><strong>Expected Delivery:</strong> <?php echo formatDate($po['expected_delivery']); ?></p>
                <p><strong>Status:</strong>
                    <span class="badge badge-<?php 
                        echo $po['status'] === 'completed' ? 'success' : 'warning'; 
                    ?>">
                        <?php echo ucfirst($po['status']); ?>
                    </span>
                </p>
            </div>

            <div>
                <h3>Supplier Information</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($po['supplier_name']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($po['supplier_phone']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($po['supplier_email']); ?></p>
            </div>

        </div>
    </div>

    <!-- ITEMS TABLE -->
    <div class="table-container">
        <div class="table-header">
            <h2>Purchased Items</h2>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>

            <?php
            $grand_total = 0;
            while ($item = $items->fetch_assoc()):
                $item_total = $item['quantity_order'] * $item['unit_price'];
                $grand_total += $item_total;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['product_category']); ?></td>
                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                    <td><?php echo $item['quantity_order']; ?></td>
                    <td><?php echo formatCurrency($item['unit_price']); ?></td>
                    <td><?php echo formatCurrency($item_total); ?></td>
                </tr>
            <?php endwhile; ?>

                <tr style="font-weight:bold;background:#f8f9fa;">
                    <td colspan="5" style="text-align:right;">Grand Total</td>
                    <td><?php echo formatCurrency($grand_total); ?></td>
                </tr>

            </tbody>
        </table>
    </div>

</div>

<?php closeDBConnection($conn); ?>
</body>
</html>
