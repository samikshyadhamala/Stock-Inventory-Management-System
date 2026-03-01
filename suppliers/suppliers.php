<?php
require "../config/db_config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);
    
checkLogin();

$conn = getDBConnection();
$success = $error = "";

// Handle Add/Edit Supplier
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        $supplier_name = sanitizeInput($_POST['supplier_name']);
        $supplier_address = sanitizeInput($_POST['supplier_address']);
        $supplier_phone = sanitizeInput($_POST['supplier_phone']);
        $supplier_email = sanitizeInput($_POST['supplier_email']);
        $contact_person = sanitizeInput($_POST['contact_person']);
        $status = sanitizeInput($_POST['status']);
        $created_at = sanitizeInput($_POST['created_at'] ?? date('Y-m-d H:i:s'));

        
        if ($_POST['action'] == 'add') {
            $stmt = $conn->prepare(
            "INSERT INTO supplier 
            (supplier_name, supplier_address, supplier_phone, supplier_email, contact_person, status, 'created_at')
            VALUES (?, ?, ?, ?, ?, ?, ?)" );

            $stmt->bind_param(
            "sssssss",
            $supplier_name,
            $supplier_address,
            $supplier_phone,
            $supplier_email,
            $contact_person,
            $status,
            $created_at);

            
            if ($stmt->execute()) {
                $success = "Supplier added successfully!";
            } else {
                throw new Exception("Failed to add supplier.");
            }
        } elseif ($_POST['action'] == 'edit') {
            $supplier_id = intval($_POST['supplier_id']);
            $stmt = $conn->prepare("UPDATE supplier SET supplier_name=?, supplier_address=?, supplier_phone=?, supplier_email=?, contact_person=?, status=?, 'created_at'=? WHERE supplier_id=?");
            $stmt->bind_param(
                "sssssssi",  
                $supplier_name, 
                $supplier_address, 
                $supplier_phone, 
                $supplier_email, 
                $contact_person, 
                $status, 
                $created_at, 
                $supplier_id
            );
            
            if ($stmt->execute()) {
                $success = "Supplier updated successfully!";
            } else {
                throw new Exception("Failed to update supplier.");
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Supplier error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    try {
        $supplier_id = intval($_GET['delete']);
        $stmt = $conn->prepare("DELETE FROM supplier WHERE supplier_id=?");
        $stmt->bind_param("i", $supplier_id);
        
        if ($stmt->execute()) {
            $success = "Supplier deleted successfully!";
        } else {
            throw new Exception("Failed to delete supplier.");
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Delete error: " . $e->getMessage());
        $error = "Cannot delete supplier. They may have related records.";
    }
}

// Search
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$whereClause = "WHERE 1=1";
if ($search) {
    $whereClause .= " AND (supplier_name LIKE '%" . $conn->real_escape_string($search) . "%' 
                     OR contact_person LIKE '%" . $conn->real_escape_string($search) . "%'
                     OR supplier_email LIKE '%" . $conn->real_escape_string($search) . "%')";
}

// Get all suppliers
$suppliersQuery = "SELECT * FROM supplier $whereClause ORDER BY supplier_id DESC";
$suppliers = $conn->query($suppliersQuery);

// Get supplier for editing
$editSupplier = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $editQuery = $conn->prepare("SELECT * FROM supplier WHERE supplier_id=?");
    $editQuery->bind_param("i", $edit_id);
    $editQuery->execute();
    $editSupplier = $editQuery->get_result()->fetch_assoc();
    $editQuery->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - Stock Management System</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Suppliers Management</h1>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal()">+ Add Supplier</button>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Search -->
        <div class="table-container">
            <form method="GET" action="suppliers.php" style="margin-bottom: 20px;">
                <div class="search-box">
                    <i>🔍</i>
                    <input type="text" name="search" placeholder="Search suppliers..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </form>
            
            <?php if ($suppliers && $suppliers->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Supplier Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th> Action<th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $supplier['supplier_id']; ?></td>
                        <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['supplier_phone']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['supplier_email']); ?></td>
                        <td><?php echo htmlspecialchars(substr($supplier['supplier_address'], 0, 30)); ?>...</td>
                        <td><?php echo date('Y-m-d H:i', strtotime($supplier['created_at'])); ?></td>

                        <td>
                            <span class="badge badge-<?php echo $supplier['status'] == 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($supplier['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="suppliers.php?edit=<?php echo $supplier['supplier_id']; ?>" 
                               class="btn btn-warning btn-sm">Edit</a>
                            <a href="suppliers.php?delete=<?php echo $supplier['supplier_id']; ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Are you sure you want to delete this supplier?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #6c757d; padding: 20px;">No suppliers found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="supplierModal" class="modal" style="display: <?php echo $editSupplier ? 'block' : 'none'; ?>;">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2><?php echo $editSupplier ? 'Edit Supplier' : 'Add New Supplier'; ?></h2>
            
            <form method="POST" action="suppliers.php">
                <input type="hidden" name="action" value="<?php echo $editSupplier ? 'edit' : 'add'; ?>">
                <?php if ($editSupplier): ?>
                    <input type="hidden" name="supplier_id" value="<?php echo $editSupplier['supplier_id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Supplier Name *</label>
                    <input type="text" name="supplier_name" class="form-control" required
                           value="<?php echo $editSupplier ? htmlspecialchars($editSupplier['supplier_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Contact Person *</label>
                    <input type="text" name="contact_person" class="form-control" required
                           value="<?php echo $editSupplier ? htmlspecialchars($editSupplier['contact_person']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Phone *</label>
                    <input type="text" name="supplier_phone" class="form-control" required
                           value="<?php echo $editSupplier ? htmlspecialchars($editSupplier['supplier_phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="supplier_email" class="form-control" required
                           value="<?php echo $editSupplier ? htmlspecialchars($editSupplier['supplier_email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Address *</label>
                    <textarea name="supplier_address" class="form-control" rows="3" required><?php echo $editSupplier ? htmlspecialchars($editSupplier['supplier_address']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" class="form-control" required>
                        <option value="active" <?php echo ($editSupplier && $editSupplier['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($editSupplier && $editSupplier['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Created At *</label>
                    <input type="datetime-local"
                        name="created_at"
                        class="form-control"
                        value="<?php
                            if ($editSupplier) {
                                echo date('Y-m-d\TH:i', strtotime($editSupplier['created_at']));
                            } else {
                                echo date('Y-m-d\TH:i');
                            }
                        ?>"
                        required>
                </div>

                
                <button type="submit" class="btn btn-success">
                    <?php echo $editSupplier ? 'Update Supplier' : 'Add Supplier'; ?>
                </button>
                <button type="button" class="btn btn-danger" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('supplierModal').style.display = 'block';
        }
        
        function closeModal() {
            window.location.href = 'suppliers.php';
        }
        
        window.onclick = function(event) {
            var modal = document.getElementById('supplierModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
    
    <?php closeDBConnection($conn); ?>
</body>
</html>