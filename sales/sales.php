<?php
require "../config/db_config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

checkLogin();
$conn = getDBConnection();
$success = $error = "";

// Handle New Sale
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_sale') {
    try {
        $conn->begin_transaction();
        
        $payment = sanitizeInput($_POST['payment']);
        $status = sanitizeInput($_POST['status']);
        $products = $_POST['products'];
        $quantities = $_POST['quantities'];
        $prices = $_POST['prices'];
        
        $total_amount = 0;

        // Validate stock availability
        for ($i = 0; $i < count($products); $i++) {
            $product_id = intval($products[$i]);
            $quantity = intval($quantities[$i]);
            
            $checkStmt = $conn->prepare("SELECT quantity FROM product WHERE product_id = ?");
            $checkStmt->bind_param("i", $product_id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $product = $result->fetch_assoc();
            
            if (!$product || $product['quantity'] < $quantity) {
                throw new Exception("Insufficient stock for product ID: $product_id");
            }
            
            $total_amount += floatval($prices[$i]) * $quantity;
        }

        // Insert sale
        $saleStmt = $conn->prepare("INSERT INTO sale (sale_date, total_amount, payment, status) VALUES (NOW(), ?, ?, ?)");
        $saleStmt->bind_param("dss", $total_amount, $payment, $status);
        $saleStmt->execute();
        $sale_id = $conn->insert_id;

        // Insert sale items and update stock per batch
        for ($i = 0; $i < count($products); $i++) {
            $product_id = intval($products[$i]);
            $quantity = intval($quantities[$i]);
            $price = floatval($prices[$i]);

            // Get a batch with enough quantity_remaining (FIFO)
            $batchStmt = $conn->prepare("
                SELECT batch_id, quantity_remaining 
                FROM batch 
                WHERE product_id = ? AND quantity_remaining >= ? 
                ORDER BY manufacture_date ASC 
                LIMIT 1
            ");
            $batchStmt->bind_param("ii", $product_id, $quantity);
            $batchStmt->execute();
            $batchResult = $batchStmt->get_result();
            $batch = $batchResult->fetch_assoc();

            if (!$batch) {
                throw new Exception("No batch with enough stock for product ID: $product_id");
            }

            $batch_id = $batch['batch_id'];

            // Insert sale_item with batch_id
            $itemStmt = $conn->prepare("
                INSERT INTO sale_item (sale_id, product_id, batch_id, quantity_sold, sale_price)
                VALUES (?, ?, ?, ?, ?)
            ");
            $itemStmt->bind_param("iiiid", $sale_id, $product_id, $batch_id, $quantity, $price);
            $itemStmt->execute();

            // Update batch quantity_remaining
            $updateBatch = $conn->prepare("UPDATE batch SET quantity_remaining = quantity_remaining - ? WHERE batch_id = ?");
            $updateBatch->bind_param("ii", $quantity, $batch_id);
            $updateBatch->execute();

            // Update product quantity
            $updateProduct = $conn->prepare("UPDATE product SET quantity = quantity - ? WHERE product_id = ?");
            $updateProduct->bind_param("ii", $quantity, $product_id);
            $updateProduct->execute();
        }

        $conn->commit();
        $success = "Sale created successfully! Sale ID: #$sale_id";

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Sale error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Get all sales
$salesQuery = "SELECT s.*, COUNT(si.sale_item_id) as item_count 
               FROM sale s 
               LEFT JOIN sale_item si ON s.sale_id = si.sale_id 
               GROUP BY s.sale_id 
               ORDER BY s.sale_date DESC";
$sales = $conn->query($salesQuery);

// Get products for dropdown
$productsQuery = "SELECT product_id, product_name, selling_price, quantity FROM product WHERE status = 'active' ORDER BY product_name";
$products = $conn->query($productsQuery);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales - Stock Management System</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Sales Management</h1>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal()">+ New Sale</button>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Sales List -->
        <div class="table-container">
            <div class="table-header">
                <h2>Sales History</h2>
            </div>
            
            <?php if ($sales && $sales->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Items</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($sale = $sales->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $sale['sale_id']; ?></td>
                        <td><?php echo formatDate($sale['sale_date']); ?></td>
                        <td><?php echo formatCurrency($sale['total_amount']); ?></td>
                        <td><?php echo $sale['item_count']; ?> items</td>
                        <td><?php echo ucfirst($sale['payment']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $sale['status'] == 'completed' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($sale['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="sale_details.php?id=<?php echo $sale['sale_id']; ?>" 
                               class="btn btn-primary btn-sm">View Details</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #6c757d; padding: 20px;">No sales recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- New Sale Modal -->
    <div id="saleModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Create New Sale</h2>
            
            <form method="POST" action="sales.php" id="saleForm">
                <input type="hidden" name="action" value="create_sale">
                
                <div id="saleItems">
                    <div class="sale-item" style="display: grid; grid-template-columns: 3fr 1fr 1fr 50px; gap: 10px; margin-bottom: 15px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Product</label>
                            <select name="products[]" class="form-control product-select" required onchange="updatePrice(this)">
                                <option value="">Select Product</option>
                                <?php 
                                $products->data_seek(0);
                                while ($product = $products->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $product['product_id']; ?>" 
                                            data-price="<?php echo $product['selling_price']; ?>"
                                            data-stock="<?php echo $product['quantity']; ?>">
                                        <?php echo htmlspecialchars($product['product_name']); ?> 
                                        (Stock: <?php echo $product['quantity']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Quantity</label>
                            <input type="number" name="quantities[]" class="form-control quantity-input" 
                                   min="1" required onchange="calculateTotal()">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Price</label>
                            <input type="number" name="prices[]" class="form-control price-input" 
                                   step="0.01" required readonly>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeSaleItem(this)" 
                                    style="margin-top: 0;">×</button>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-success btn-sm" onclick="addSaleItem()" 
                        style="margin-bottom: 20px;">+ Add Item</button>
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <h3 style="margin: 0;">Total: <span id="totalAmount">Rs. 0.00</span></h3>
                </div>
                
                <div class="form-group">
                    <label>Payment Method *</label>
                    <select name="payment" class="form-control" required>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="mobile">Mobile Payment</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" class="form-control" required>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-success">Complete Sale</button>
                <button type="button" class="btn btn-danger" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('saleModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('saleModal').style.display = 'none';
            document.getElementById('saleForm').reset();
            // Reset to single item
            const container = document.getElementById('saleItems');
            const items = container.querySelectorAll('.sale-item');
            for (let i = 1; i < items.length; i++) {
                items[i].remove();
            }
            calculateTotal();
        }
        
        function addSaleItem() {
            const container = document.getElementById('saleItems');
            const firstItem = container.querySelector('.sale-item');
            const newItem = firstItem.cloneNode(true);
            
            // Reset values
            newItem.querySelectorAll('input, select').forEach(input => {
                input.value = '';
            });
            
            container.appendChild(newItem);
        }
        
        function removeSaleItem(button) {
            const container = document.getElementById('saleItems');
            if (container.querySelectorAll('.sale-item').length > 1) {
                button.closest('.sale-item').remove();
                calculateTotal();
            } else {
                alert('At least one item is required');
            }
        }
        
        function updatePrice(select) {
            const option = select.options[select.selectedIndex];
            const price = option.getAttribute('data-price');
            const stock = option.getAttribute('data-stock');
            
            const saleItem = select.closest('.sale-item');
            const priceInput = saleItem.querySelector('.price-input');
            const quantityInput = saleItem.querySelector('.quantity-input');
            
            priceInput.value = price || '';
            quantityInput.max = stock || '';
            
            calculateTotal();
        }
        
        function calculateTotal() {
            let total = 0;
            const items = document.querySelectorAll('.sale-item');
            
            items.forEach(item => {
                const quantity = parseFloat(item.querySelector('.quantity-input').value) || 0;
                const price = parseFloat(item.querySelector('.price-input').value) || 0;
                total += quantity * price;
            });
            
            document.getElementById('totalAmount').textContent = 'Rs. ' + total.toFixed(2);
        }
        
        // Add event listeners for quantity changes
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('quantity-input')) {
                const saleItem = e.target.closest('.sale-item');
                const select = saleItem.querySelector('.product-select');
                const option = select.options[select.selectedIndex];
                const maxStock = parseInt(option.getAttribute('data-stock'));
                
                if (parseInt(e.target.value) > maxStock) {
                    alert('Quantity cannot exceed available stock: ' + maxStock);
                    e.target.value = maxStock;
                }
                
                calculateTotal();
            }
        });
        
        window.onclick = function(event) {
            var modal = document.getElementById('saleModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
    
    <?php closeDBConnection($conn); ?>
</body>
</html>