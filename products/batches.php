<?php
require "../config/db_config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);
checkLogin();

$conn = getDBConnection();
$success = $error = "";

// Fetch active products
$productsQuery = "SELECT product_id, product_name FROM product WHERE status = 'active' ORDER BY product_name";
$products = $conn->query($productsQuery);

// Fetch purchase orders for dropdown
$purchasesQuery = "SELECT purchase_id, supplier_id, expected_delivery FROM purchase_order ORDER BY expected_delivery DESC";
$purchases = $conn->query($purchasesQuery);

// Handle Add/Edit Batch
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        $batch_no = sanitizeInput($_POST['batch_no']);
        $product_id = intval($_POST['product_id']);
        $manufacture_date = sanitizeInput($_POST['manufacture_date']);
        $expire_date = sanitizeInput($_POST['expire_date']);
        $quantity_received = intval($_POST['quantity_received']);
        $quantity_remaining = intval($_POST['quantity_remaining']);

        // Validate purchase_id
        if (!isset($_POST['purchase_id']) || empty($_POST['purchase_id'])) {
            throw new Exception("Please select a valid Purchase Order.");
        }
        $purchase_id = intval($_POST['purchase_id']);

        if ($_POST['action'] == 'add') {
            $stmt = $conn->prepare("
                INSERT INTO batch 
                (batch_no, product_id, purchase_id, manufacture_date, expire_date, quantity_received, quantity_remaining) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("siissii", $batch_no, $product_id, $purchase_id, $manufacture_date, $expire_date, $quantity_received, $quantity_remaining);
            
            if ($stmt->execute()) {
                $success = "Batch added successfully!";
            } else {
                throw new Exception("Failed to add batch.");
            }
        } elseif ($_POST['action'] == 'edit') {
            $batch_id = intval($_POST['batch_id']);
            $stmt = $conn->prepare("
                UPDATE batch 
                SET batch_no=?, product_id=?, purchase_id=?, manufacture_date=?, expire_date=?, quantity_received=?, quantity_remaining=? 
                WHERE batch_id=?
            ");
            $stmt->bind_param("siissiii", $batch_no, $product_id, $purchase_id, $manufacture_date, $expire_date, $quantity_received, $quantity_remaining, $batch_id);
            
            if ($stmt->execute()) {
                $success = "Batch updated successfully!";
            } else {
                throw new Exception("Failed to update batch.");
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Batch error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    try {
        $batch_id = intval($_GET['delete']);
        $stmt = $conn->prepare("DELETE FROM batch WHERE batch_id=?");
        $stmt->bind_param("i", $batch_id);
        if ($stmt->execute()) {
            $success = "Batch deleted successfully!";
        } else {
            throw new Exception("Failed to delete batch.");
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Delete error: " . $e->getMessage());
        $error = "Failed to delete batch.";
    }
}

// Get all batches with product info
$batchesQuery = "SELECT b.*, p.product_name, p.product_category 
                 FROM batch b 
                 JOIN product p ON b.product_id = p.product_id 
                 ORDER BY b.expire_date ASC";
$batches = $conn->query($batchesQuery);

// Get batch for editing
$editBatch = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $editQuery = $conn->prepare("SELECT * FROM batch WHERE batch_id=?");
    $editQuery->bind_param("i", $edit_id);
    $editQuery->execute();
    $editBatch = $editQuery->get_result()->fetch_assoc();
    $editQuery->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Batches - Stock Management System</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="header">
        <h1>Batch Management</h1>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openModal()">+ Add Batch</button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Batches List -->
    <div class="table-container">
        <div class="table-header">
            <h2>Product Batches</h2>
        </div>

        <?php if ($batches && $batches->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Batch ID</th>
                    <th>Batch No</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Purchase ID</th>
                    <th>Manufacture Date</th>
                    <th>Expiry Date</th>
                    <th>Received</th>
                    <th>Remaining</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($batch = $batches->fetch_assoc()): 
                    $today = date('Y-m-d');
                    $expiry_date = $batch['expire_date'];
                    $days_to_expiry = (strtotime($expiry_date) - strtotime($today)) / (60 * 60 * 24);

                    if ($days_to_expiry < 0) {
                        $status_badge = 'badge-danger';
                        $status_text = 'Expired';
                    } elseif ($days_to_expiry <= 30) {
                        $status_badge = 'badge-warning';
                        $status_text = 'Expiring Soon';
                    } else {
                        $status_badge = 'badge-success';
                        $status_text = 'Good';
                    }
                ?>
                <tr>
                    <td>#<?php echo $batch['batch_id']; ?></td>
                    <td><?php echo htmlspecialchars($batch['batch_no']); ?></td>
                    <td><?php echo htmlspecialchars($batch['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($batch['product_category']); ?></td>
                    <td>#<?php echo $batch['purchase_id']; ?></td>
                    <td><?php echo formatDate($batch['manufacture_date']); ?></td>
                    <td><?php echo formatDate($batch['expire_date']); ?></td>
                    <td><?php echo $batch['quantity_received']; ?></td>
                    <td><?php echo $batch['quantity_remaining']; ?></td>
                    <td>
                        <span class="badge <?php echo $status_badge; ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </td>
                    <td>
                        <a href="batches.php?edit=<?php echo $batch['batch_id']; ?>" 
                           class="btn btn-warning btn-sm">Edit</a>
                        <a href="batches.php?delete=<?php echo $batch['batch_id']; ?>" 
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Are you sure you want to delete this batch?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; color: #6c757d; padding: 20px;">No batches found.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="batchModal" class="modal" style="display: <?php echo $editBatch ? 'block' : 'none'; ?>;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2><?php echo $editBatch ? 'Edit Batch' : 'Add New Batch'; ?></h2>

        <form method="POST" action="batches.php">
            <input type="hidden" name="action" value="<?php echo $editBatch ? 'edit' : 'add'; ?>">
            <?php if ($editBatch): ?>
                <input type="hidden" name="batch_id" value="<?php echo $editBatch['batch_id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Batch Number *</label>
                <input type="text" name="batch_no" class="form-control" required
                       value="<?php echo $editBatch ? htmlspecialchars($editBatch['batch_no']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>Product *</label>
                <select name="product_id" class="form-control" required>
                    <option value="">Select Product</option>
                    <?php 
                    $products->data_seek(0);
                    while ($product = $products->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $product['product_id']; ?>"
                            <?php echo ($editBatch && $editBatch['product_id'] == $product['product_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($product['product_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Purchase Order *</label>
                <select name="purchase_id" class="form-control" required>
                    <option value="">Select Purchase</option>
                    <?php 
                    $purchases->data_seek(0);
                    while ($purchase = $purchases->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $purchase['purchase_id']; ?>"
                            <?php echo ($editBatch && $editBatch['purchase_id'] == $purchase['purchase_id']) ? 'selected' : ''; ?>>
                            #<?php echo $purchase['purchase_id']; ?> - <?php echo htmlspecialchars($purchase['supplier_id']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Manufacture Date *</label>
                <input type="date" name="manufacture_date" class="form-control" required
                       value="<?php echo $editBatch ? $editBatch['manufacture_date'] : ''; ?>">
            </div>

            <div class="form-group">
                <label>Expiry Date *</label>
                <input type="date" name="expire_date" class="form-control" required
                       value="<?php echo $editBatch ? $editBatch['expire_date'] : ''; ?>">
            </div>

            <div class="form-group">
                <label>Quantity Received *</label>
                <input type="number" name="quantity_received" class="form-control" required
                       value="<?php echo $editBatch ? $editBatch['quantity_received'] : ''; ?>">
            </div>

            <div class="form-group">
                <label>Quantity Remaining *</label>
                <input type="number" name="quantity_remaining" class="form-control" required
                       value="<?php echo $editBatch ? $editBatch['quantity_remaining'] : ''; ?>">
            </div>

            <button type="submit" class="btn btn-success">
                <?php echo $editBatch ? 'Update Batch' : 'Add Batch'; ?>
            </button>
            <button type="button" class="btn btn-danger" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('batchModal').style.display = 'block';
}
function closeModal() {
    window.location.href = 'batches.php';
}
window.onclick = function(event) {
    var modal = document.getElementById('batchModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php closeDBConnection($conn); ?>
</body>
</html>
