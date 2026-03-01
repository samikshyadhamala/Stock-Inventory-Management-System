<?php
require "../config/db_config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

checkLogin();
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$user_id = (int) $_SESSION['user_id'];

$conn = getDBConnection();

$success = $error = "";

/* ============================
   CREATE PURCHASE ORDER
============================ */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'create_po'
) {
    try {
        $conn->begin_transaction();

        $supplier_id = intval($_POST['supplier_id']);
        // Directly get the date from POST and validate format  
        $expected_delivery = $_POST['expected_delivery_date'] ?? '';
        if (!$expected_delivery || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expected_delivery)) {
            throw new Exception("Invalid expected delivery date.");
        }


        $status = sanitizeInput($_POST['status']);

        $products   = $_POST['products'] ?? [];
        $quantities = $_POST['quantities'] ?? [];
        $prices     = $_POST['prices'] ?? [];

        if (empty($products)) {
            throw new Exception("At least one product is required.");
        }

        $poStmt = $conn->prepare("
            INSERT INTO purchase_order
            (supplier_id, user_id, order_date, expected_delivery, status)
            VALUES (?, ?, NOW(), ?, ?)
        ");
        $poStmt->bind_param("iiss", $supplier_id, $user_id, $expected_delivery, $status);
        $poStmt->execute();



        $purchase_id = $conn->insert_id;

        /* Insert Purchased Items */
        for ($i = 0; $i < count($products); $i++) {
            $product_id = intval($products[$i]);
            $quantity   = intval($quantities[$i]);
            $price      = floatval($prices[$i]);

            if ($product_id <= 0 || $quantity <= 0 || $price <= 0) {
                throw new Exception("Invalid product data.");
            }

            $itemStmt = $conn->prepare("
                INSERT INTO purchased_item
                (purchase_id, product_id, quantity_order, unit_price)
                VALUES (?, ?, ?, ?)
            ");
            $itemStmt->bind_param("iiid", $purchase_id, $product_id, $quantity, $price);
            $itemStmt->execute();

            /* Update stock only if completed */
            if ($status === 'completed') {
                $updateStock = $conn->prepare("
                    UPDATE product
                    SET quantity = quantity + ?
                    WHERE product_id = ?
                ");
                $updateStock->bind_param("ii", $quantity, $product_id);
                $updateStock->execute();
            }
        }

        $conn->commit();
        $success = "Purchase Order created successfully. PO ID: #{$purchase_id}";

    } catch (Exception $e) {
        $conn->rollback();
        error_log($e->getMessage());
        $error = $e->getMessage();
    }
}

/* ============================
   UPDATE STATUS
============================ */
if (isset($_GET['update_status'], $_GET['status'])) {
    try {
        $po_id = intval($_GET['update_status']);
        $new_status = sanitizeInput($_GET['status']);

        $conn->begin_transaction();

        $updatePO = $conn->prepare("
            UPDATE purchase_order
            SET status = ?
            WHERE purchase_id = ?
        ");
        $updatePO->bind_param("si", $new_status, $po_id);
        $updatePO->execute();

        if ($new_status === 'completed') {
            $items = $conn->prepare("
                SELECT product_id, quantity_order
                FROM purchased_item
                WHERE purchase_id = ?
            ");
            $items->bind_param("i", $po_id);
            $items->execute();
            $result = $items->get_result();

            while ($row = $result->fetch_assoc()) {
                $updateQty = $conn->prepare("
                    UPDATE product
                    SET quantity = quantity + ?
                    WHERE product_id = ?
                ");
                $updateQty->bind_param("ii", $row['quantity_order'], $row['product_id']);
                $updateQty->execute();
            }
        }

        $conn->commit();
        $success = "Purchase order status updated.";

    } catch (Exception $e) {
        $conn->rollback();
        error_log($e->getMessage());
        $error = "Failed to update status.";
    }
}



/* ============================
   FETCH DATA
============================ */
$purchase_orders = $conn->query("
    SELECT 
        po.purchase_id,
        po.order_date,
        po.expected_delivery,
        po.status,
        s.supplier_name,
        COUNT(pi.purchase_item_id) AS item_count,
        GROUP_CONCAT(CONCAT(p.product_name, ' (', pi.quantity_order, ' ', p.unit, ')') SEPARATOR ', ') AS products_list
    FROM purchase_order po
    LEFT JOIN supplier s ON po.supplier_id = s.supplier_id
    LEFT JOIN purchased_item pi ON po.purchase_id = pi.purchase_id
    LEFT JOIN product p ON pi.product_id = p.product_id
    GROUP BY po.purchase_id
    ORDER BY po.order_date DESC
");


$suppliers = $conn->query("
    SELECT supplier_id, supplier_name
    FROM supplier
    WHERE status = 'active'
    ORDER BY supplier_name
");

$products = $conn->query("
    SELECT product_id, product_name, unit
    FROM product
    WHERE status = 'active'
    ORDER BY product_name
");
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - Stock Management System</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Purchase Orders</h1>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal()">+ New Purchase Order</button>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Purchase Orders List -->
        <div class="table-container">
            <div class="table-header">
                <h2>Purchase Orders</h2>
            </div>
            
            <?php if ($purchase_orders && $purchase_orders->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>PO ID</th>
                        <th>Supplier</th>
                        <th>Order Date</th>
                        <th>Expected Delivery</th>
                        <th>Items</th>
                         <th>Products</th>
                        <th>Status</th>
                        <th>Actions</th>
                       

                    </tr>
                </thead>
                <tbody>
                    <?php while ($po = $purchase_orders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $po['purchase_id']; ?></td>
                        <td><?php echo htmlspecialchars($po['supplier_name']); ?></td>
                        <td><?php echo formatDate($po['order_date']); ?></td>
                        <td>
                        <?php 
                        if (!empty($po['expected_delivery'])) {
                            echo date('d-m-Y', strtotime($po['expected_delivery']));
                        } else {
                            echo '-';
                        }
                        ?>
                        </td>

                        <td><?php echo $po['item_count']; ?> items</td>
                        <td><?php echo $po['products_list'] ? htmlspecialchars($po['products_list']) : '-'; ?> Product</td>

                        <td>
                            <span class="badge badge-<?php 
                                echo $po['status'] == 'completed' ? 'success' : 
                                    ($po['status'] == 'pending' ? 'warning' : 'info'); 
                            ?>">
                                <?php echo ucfirst($po['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="purchase_details.php?id=<?php echo $po['purchase_id']; ?>" 
                               class="btn btn-primary btn-sm">Details</a>
                            <?php if ($po['status'] != 'completed'): ?>
                            <a href="purchase_orders.php?update_status=<?php echo $po['purchase_id']; ?>&status=completed" 
                               class="btn btn-success btn-sm"
                               onclick="return confirm('Mark this purchase order as completed?')">Complete</a>
                            <?php endif; ?>
                        </td>
                        
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #6c757d; padding: 20px;">No purchase orders yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- New Purchase Order Modal -->
    <div id="poModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Create New Purchase Order</h2>
            
            <form method="POST" action="purchase_orders.php" id="poForm">
                <input type="hidden" name="action" value="create_po">
                
                <div class="form-group">
                    <label>Supplier *</label>
                    <select name="supplier_id" class="form-control" required>
                        <option value="">Select Supplier</option>
                        <?php 
                        $suppliers->data_seek(0);
                        while ($supplier = $suppliers->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $supplier['supplier_id']; ?>">
                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Expected Delivery Date *</label>
                <input type="date" name="expected_delivery_date" class="form-control" required
                    min="<?php echo date('Y-m-d'); ?>">

                </div>
                
                <div id="poItems">
                    <div class="po-item" style="display: grid; grid-template-columns: 3fr 1fr 1fr 50px; gap: 10px; margin-bottom: 15px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Product</label>
                            <select name="products[]" class="form-control" required>
                                <option value="">Select Product</option>
                                <?php 
                                $products->data_seek(0);
                                while ($product = $products->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $product['product_id']; ?>">
                                        <?php echo htmlspecialchars($product['product_name']); ?> 
                                        (<?php echo $product['unit']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Quantity</label>
                            <input type="number" name="quantities[]" class="form-control" 
                                   min="1" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Unit Price</label>
                            <input type="number" name="prices[]" class="form-control" 
                                   step="0.01" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removePOItem(this)">×</button>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-success btn-sm" onclick="addPOItem()" 
                        style="margin-bottom: 20px;">+ Add Item</button>
                
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-success">Create Purchase Order</button>
                <button type="button" class="btn btn-danger" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('poModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('poModal').style.display = 'none';
            document.getElementById('poForm').reset();
        }
        
        function addPOItem() {
            const container = document.getElementById('poItems');
            const firstItem = container.querySelector('.po-item');
            const newItem = firstItem.cloneNode(true);
            
            newItem.querySelectorAll('input, select').forEach(input => {
                input.value = '';
            });
            
            container.appendChild(newItem);
        }
        
        function removePOItem(button) {
            const container = document.getElementById('poItems');
            if (container.querySelectorAll('.po-item').length > 1) {
                button.closest('.po-item').remove();
            } else {
                alert('At least one item is required');
            }
        }
        
        window.onclick = function(event) {
            var modal = document.getElementById('poModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
    
    <?php closeDBConnection($conn); ?>
</body>
</html>