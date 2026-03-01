<?php
require "../config/db_config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);
checkLogin();

if (!isset($_GET['id'])) {
    header("Location: sales.php");
    exit();
}

$conn = getDBConnection();
$sale_id = intval($_GET['id']);

// Get sale details
$saleQuery = $conn->prepare("SELECT * FROM sale WHERE sale_id = ?");
$saleQuery->bind_param("i", $sale_id);
$saleQuery->execute();
$sale = $saleQuery->get_result()->fetch_assoc();

if (!$sale) {
    header("Location: sales.php");
    exit();
}

// Get sale items
$itemsQuery = $conn->prepare("SELECT si.*, p.product_name, p.product_category 
                               FROM sale_item si 
                               JOIN product p ON si.product_id = p.product_id 
                               WHERE si.sale_id = ?");
$itemsQuery->bind_param("i", $sale_id);
$itemsQuery->execute();
$items = $itemsQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Details - Stock Management System</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .invoice-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .invoice-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        .print-btn {
            float: right;
        }
        @media print {
            .sidebar, .print-btn, .header { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Sale Details</h1>
            <div class="header-actions">
                <button class="btn btn-primary print-btn" onclick="window.print()">🖨️ Print Invoice</button>
                <a href="sales.php" class="btn btn-warning">← Back to Sales</a>
            </div>
        </div>
        
        <div class="invoice-header">
            <h2 style="color: #2c3e50; margin-bottom: 10px;">Invoice #<?php echo $sale['sale_id']; ?></h2>
            <p style="color: #7f8c8d;">Date: <?php echo formatDate($sale['sale_date']); ?></p>
            
            <div class="invoice-info">
                <div>
                    <h3 style="color: #495057; font-size: 16px; margin-bottom: 10px;">Sale Information</h3>
                    <p><strong>Sale ID:</strong> #<?php echo $sale['sale_id']; ?></p>
                    <p><strong>Date:</strong> <?php echo date('d M Y, h:i A', strtotime($sale['sale_date'])); ?></p>
                    <p><strong>Payment Method:</strong> <?php echo ucfirst($sale['payment']); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge badge-<?php echo $sale['status'] == 'completed' ? 'success' : 'warning'; ?>">
                            <?php echo ucfirst($sale['status']); ?>
                        </span>
                    </p>
                </div>
                <div>
                    <h3 style="color: #495057; font-size: 16px; margin-bottom: 10px;">Amount Details</h3>
                    <p><strong>Total Amount:</strong> <?php echo formatCurrency($sale['total_amount']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <div class="table-header">
                <h2>Items</h2>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subtotal = 0;
                    while ($item = $items->fetch_assoc()): 
                        $item_total = $item['quantity_sold'] * $item['sale_price'];
                        $subtotal += $item_total;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['product_category']); ?></td>
                        <td><?php echo $item['quantity_sold']; ?></td>
                        <td><?php echo formatCurrency($item['sale_price']); ?></td>
                        <td><?php echo formatCurrency($item_total); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <tr style="background: #f8f9fa; font-weight: bold;">
                        <td colspan="4" style="text-align: right;">Total:</td>
                        <td><?php echo formatCurrency($subtotal); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php closeDBConnection($conn); ?>
</body>
</html>