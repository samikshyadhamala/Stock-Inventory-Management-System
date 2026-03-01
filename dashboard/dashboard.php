<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "../config/db_config.php";
checkLogin();

$conn = getDBConnection();

try {

    // Total Products
    $totalProducts = $conn->query("SELECT COUNT(*) as total FROM product")->fetch_assoc()['total'];

    // Active Suppliers
    $totalSuppliers = $conn->query("SELECT COUNT(*) as total FROM supplier WHERE status='active'")->fetch_assoc()['total'];

    // Today's Sales
    $salesData = $conn->query("SELECT COUNT(*) as total, COALESCE(SUM(total_amount),0) as revenue FROM sale WHERE DATE(sale_date)=CURDATE()")->fetch_assoc();
    $todaySales = $salesData['total'];
    $todayRevenue = $salesData['revenue'];

    // Low Stock Products
    $lowStockCount = $conn->query("SELECT COUNT(*) as total FROM product WHERE quantity < 10")->fetch_assoc()['total'];

    // Recent Sales
    $recentSales = $conn->query("SELECT sale_id, sale_date, total_amount, payment, status FROM sale ORDER BY sale_date DESC LIMIT 5");

    // Low Stock Details
    $lowStockProducts = $conn->query("
        SELECT 
            pb.batch_id,
            pb.batch_no,
            pb.product_id,
            p.product_name,
            p.product_category,
            pb.quantity_remaining,
            p.unit
        FROM batch pb
        LEFT JOIN product p ON pb.product_id = p.product_id
        WHERE pb.quantity_remaining < 10  -- threshold for low stock
        ORDER BY pb.quantity_remaining ASC
        LIMIT 5
    ");


    // Recent Purchase Orders with expected delivery
    $recentPurchases = $conn->query("
        SELECT po.purchase_id, po.expected_delivery, po.status, s.supplier_name
        FROM purchase_order po
        LEFT JOIN supplier s ON po.supplier_id = s.supplier_id
        ORDER BY po.order_date DESC
        LIMIT 5
    ");

    // Upcoming Deliveries (next 3 days)
    $today = date('Y-m-d');
    $upcomingDeliveries = $conn->query("
        SELECT po.purchase_id, po.expected_delivery, po.status, s.supplier_name
        FROM purchase_order po
        LEFT JOIN supplier s ON po.supplier_id = s.supplier_id
        WHERE po.expected_delivery IS NOT NULL
          AND po.expected_delivery >= CURDATE()
        ORDER BY po.expected_delivery ASC
        LIMIT 5
    ");

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = "Error loading dashboard data.";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Stock Management System</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">

    <div class="header">
        <div>
            <h1>Dashboarrrrrd</h1>
            <p>Welcome back !</p>
        </div>
        <div>
            <?php echo date('l, d F Y'); ?>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="dashboard-cards">

        <div class="card">
            <h3>Total Products</h3>
            <div class="value"><?php echo $totalProducts; ?></div>
        </div>

        <div class="card">
            <h3>Active Suppliers</h3>
            <div class="value"><?php echo $totalSuppliers; ?></div>
        </div>

        <div class="card">
            <h3>Today's Revenue</h3>
            <div class="value"><?php echo formatCurrency($todayRevenue); ?></div>
        </div>

        <div class="card">
            <h3>Low Stock Items</h3>
            <div class="value"><?php echo $lowStockCount; ?></div>
        </div>

    </div>

    <!-- Recent Sales -->
    <div class="table-container">
        <div class="table-header">
        <h2>Recent Sales</h2>
    </div>

        <?php if ($recentSales && $recentSales->num_rows > 0): ?>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                </tr>
            </thead>
                <?php while ($sale = $recentSales->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $sale['sale_id']; ?></td>
                        <td><?php echo formatDate($sale['sale_date']); ?></td>
                        <td><?php echo formatCurrency($sale['total_amount']); ?></td>
                        <td><?php echo ucfirst($sale['payment']); ?></td>
                        <td><?php echo ucfirst($sale['status']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No sales recorded yet.</p>
        <?php endif; ?>
    </div>
        </br>

    <!-- Low Stock -->
    <div class="table-container">
        <div class="table-header">
        <h2>Low Stock Products </h2>
    </div>

        <?php if ($lowStockProducts && $lowStockProducts->num_rows > 0): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Unit</th>
                </tr>

                    <?php while ($batch = $lowStockProducts->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($batch['batch_no']); ?></td>
                <td><?php echo htmlspecialchars($batch['product_name']); ?></td>
                <td><?php echo htmlspecialchars($batch['product_category']); ?></td>
                <td><?php echo $batch['quantity_remaining']; ?></td>
                <td><?php echo htmlspecialchars($batch['unit']); ?></td>
            </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>All products are well stocked.</p>
        <?php endif; ?>
    </div>
        </br>
<?php
        // Upcoming Deliveries (next few days, excluding completed)
        $upcomingDeliveries = $conn->query("
            SELECT po.purchase_id, po.expected_delivery, po.status, s.supplier_name
            FROM purchase_order po
            LEFT JOIN supplier s ON po.supplier_id = s.supplier_id
            WHERE po.expected_delivery IS NOT NULL
            AND po.expected_delivery >= CURDATE()
            AND po.status != 'completed'
            ORDER BY po.expected_delivery ASC
            LIMIT 5
        ");
        ?>

        <div class="table-container">
            <div class="table-header">
        <h2>Upcoming Excpected Deliveries </h2>
    </div>
            <?php if ($upcomingDeliveries && $upcomingDeliveries->num_rows > 0): ?>
                <table>
                    <tr>
                        <th>PO ID</th>
                        <th>Supplier</th>
                        <th>Expected Delivery</th>
                        <th>Status</th>
                    </tr>

                    <?php while ($po = $upcomingDeliveries->fetch_assoc()): ?>
                        <?php 
                        $daysLeft = (strtotime($po['expected_delivery']) - strtotime(date('Y-m-d'))) / 86400;
                        
                        // Highlight only if delivery is today or within 1 day
                        $highlight = ($daysLeft <= 1) ? 'color: red;' : '';
                        ?>
                        <tr style="<?php echo $highlight; ?>">
                            <td>#<?php echo $po['purchase_id']; ?></td>
                            <td><?php echo htmlspecialchars($po['supplier_name']); ?></td>
                            <td><?php echo formatDate($po['expected_delivery']); ?></td>
                            <td><?php echo ucfirst($po['status']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>

            <?php else: ?>
                <p>No upcoming deliveries.</p>
            <?php endif; ?>
        </div>


        </div>




<?php closeDBConnection($conn); ?>

</body>
</html>
