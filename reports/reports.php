<?php
require "../config/db_config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);
checkLogin();

$conn = getDBConnection();

// Get date range from form or use defaults
$start_date = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : date('Y-m-d');

// Sales Report
$salesReportQuery = $conn->prepare("SELECT 
    DATE(sale_date) as date,
    COUNT(*) as total_sales,
    SUM(total_amount) as revenue
    FROM sale 
    WHERE sale_date BETWEEN ? AND ?
    GROUP BY DATE(sale_date)
    ORDER BY DATE(sale_date) DESC");
$salesReportQuery->bind_param("ss", $start_date, $end_date);
$salesReportQuery->execute();
$salesReport = $salesReportQuery->get_result();

$topProductsQuery = $conn->prepare("
    SELECT 
        p.product_name,
        p.product_category,
        SUM(si.quantity_sold) AS total_sold,
        SUM(si.quantity_sold * si.sale_price) AS revenue
    FROM sale_item si
    JOIN product p ON si.product_id = p.product_id
    JOIN sale s ON si.sale_id = s.sale_id
    WHERE s.sale_date BETWEEN ? AND ?
    GROUP BY si.product_id
    ORDER BY total_sold DESC
    LIMIT 10
");

if (!$topProductsQuery) {
    die("Prepare failed: " . $conn->error);
}

$topProductsQuery->bind_param("ss", $start_date, $end_date);
$topProductsQuery->execute();
$topProducts = $topProductsQuery->get_result();


$purchaseReportQuery = $conn->prepare("
    SELECT 
        po.purchase_id,
        s.supplier_name,
        po.order_date,
        po.expected_delivery,
        po.status,
        COUNT(pi.purchase_item_id) AS items,
        SUM(pi.quantity_order * pi.unit_price) AS total_cost
    FROM purchase_order po
    JOIN supplier s ON po.supplier_id = s.supplier_id
    LEFT JOIN purchased_item pi ON po.purchase_id = pi.purchase_id
    WHERE po.order_date BETWEEN ? AND ?
    GROUP BY po.purchase_id
    ORDER BY po.order_date DESC
");

// Check if prepare succeeded
if (!$purchaseReportQuery) {
    die("Prepare failed: " . $conn->error);
}

$purchaseReportQuery->bind_param("ss", $start_date, $end_date);
$purchaseReportQuery->execute();
$purchaseReport = $purchaseReportQuery->get_result();

// Inventory Summary
$inventoryQuery = "SELECT 
    product_category,
    COUNT(*) as product_count,
    SUM(quantity) as total_quantity,
    SUM(quantity * selling_price) as inventory_value
    FROM product
    WHERE status = 'active'
    GROUP BY product_category
    ORDER BY inventory_value DESC";
$inventoryReport = $conn->query($inventoryQuery);

// Overall Statistics
$statsQuery = $conn->prepare("SELECT 
    (SELECT COUNT(*) FROM sale WHERE sale_date BETWEEN ? AND ?) as total_sales,
    (SELECT COALESCE(SUM(total_amount), 0) FROM sale WHERE sale_date BETWEEN ? AND ?) as total_revenue,
    (SELECT COUNT(*) FROM purchase_order WHERE order_date BETWEEN ? AND ?) as total_purchases,
    (SELECT COALESCE(SUM(pi.quantity_order * pi.unit_price), 0) 
     FROM purchased_item pi 
     JOIN purchase_order po ON pi.purchase_id = po.purchase_id 
     WHERE po.order_date BETWEEN ? AND ?) as total_purchase_cost");
$statsQuery->bind_param("ssssssss", $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
$statsQuery->execute();
$stats = $statsQuery->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Stock Management System</title>
    <link rel="stylesheet" href="../assets//style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Reports & Analytics</h1>
        </div>
        
        <!-- Date Range Filter -->
        <div class="table-container" style="margin-bottom: 30px;">
            <form method="GET" action="reports.php" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="<?php echo $start_date; ?>" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" 
                           value="<?php echo $end_date; ?>" required>
                </div>
                
                <button type="button" class="btn btn-success" onclick="window.print()">🖨️ Print Report</button>
            </form>
        </div>
        
        <!-- Summary Cards -->
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-icon blue">💰</div>
                <h3>Total Revenue</h3>
                <div class="value"><?php echo formatCurrency($stats['total_revenue']); ?></div>
            </div>
            
            <div class="card">
                <div class="card-icon green">📦</div>
                <h3>Total Sales</h3>
                <div class="value"><?php echo $stats['total_sales']; ?></div>
            </div>
            
            <div class="card">
                <div class="card-icon orange">🛒</div>
                <h3>Total Purchases</h3>
                <div class="value"><?php echo $stats['total_purchases']; ?></div>
            </div>
            
            <div class="card">
                <div class="card-icon red">💸</div>
                <h3>Purchase Cost</h3>
                <div class="value"><?php echo formatCurrency($stats['total_purchase_cost']); ?></div>
            </div>
        </div>
        
        <!-- Sales Report -->
        <div class="table-container">
            <div class="table-header">
                <h2>Daily Sales Report</h2>
            </div>
            
            <?php if ($salesReport && $salesReport->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Sales</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $salesReport->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo formatDate($row['date']); ?></td>
                        <td><?php echo $row['total_sales']; ?></td>
                        <td><?php echo formatCurrency($row['revenue']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #6c757d; padding: 20px;">No sales data for selected period.</p>
            <?php endif; ?>
        </div>
        
        <br>
        
        <!-- Top Selling Products -->
        <div class="table-container">
            <div class="table-header">
                <h2>Top Selling Products</h2>
            </div>
            
            <?php if ($topProducts && $topProducts->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Total Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $topProducts->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['product_category']); ?></td>
                        <td><?php echo $row['total_sold']; ?></td>
                        <td><?php echo formatCurrency($row['revenue']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #6c757d; padding: 20px;">No product sales data.</p>
            <?php endif; ?>
        </div>
        
        <br>
        
        <!-- Purchase Report -->
        <div class="table-container">
            <div class="table-header">
                <h2>Purchase Orders Report</h2>
            </div>
            
            <?php if ($purchaseReport && $purchaseReport->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>PO ID</th>
                        <th>Supplier</th>
                        <th>Order Date</th>
                        <th>Expected Delivery</th>
                        <th>Items</th>
                        <th>Total Cost</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $purchaseReport->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $row['purchase_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                        <td><?php echo formatDate($row['order_date']); ?></td>
                        <td><?php echo formatDate($row['expected_delivery']); ?></td>
                        <td><?php echo $row['items']; ?></td>
                        <td><?php echo formatCurrency($row['total_cost']); ?></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $row['status'] == 'completed' ? 'success' : 
                                    ($row['status'] == 'pending' ? 'warning' : 'info'); 
                            ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #6c757d; padding: 20px;">No purchase orders for selected period.</p>
            <?php endif; ?>
        </div>
        
        <br>
        
        <!-- Inventory Summary -->
        <div class="table-container">
            <div class="table-header">
                <h2>Inventory Summary by Category</h2>
            </div>
            
            <?php if ($inventoryReport && $inventoryReport->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Product Count</th>
                        <th>Total Quantity</th>
                        <th>Inventory Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_value = 0;
                    while ($row = $inventoryReport->fetch_assoc()): 
                        $total_value += $row['inventory_value'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['product_category']); ?></td>
                        <td><?php echo $row['product_count']; ?></td>
                        <td><?php echo $row['total_quantity']; ?></td>
                        <td><?php echo formatCurrency($row['inventory_value']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <tr style="background: #f8f9fa; font-weight: bold;">
                        <td colspan="3">Total Inventory Value:</td>
                        <td><?php echo formatCurrency($total_value); ?></td>
                    </tr>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #6c757d; padding: 20px;">No inventory data available.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php closeDBConnection($conn); ?>
</body>
</html>