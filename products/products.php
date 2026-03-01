<?php
require "../config/db_config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

checkLogin();

$conn = getDBConnection();
$success = $error = "";

// Handle Add/Edit Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        $product_name = sanitizeInput($_POST['product_name']);
        $product_category = sanitizeInput($_POST['product_category']);
        $unit = sanitizeInput($_POST['unit']);
        $selling_price = floatval($_POST['selling_price']);
        $reorder_level = intval($_POST['reorder_level']);
        $quantity = intval($_POST['quantity']);
        $status = sanitizeInput($_POST['status']);
        
        if ($_POST['action'] == 'add') {
            $stmt = $conn->prepare("INSERT INTO product (product_name, product_category, unit, selling_price, reorder_level, quantity, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdiis", $product_name, $product_category, $unit, $selling_price, $reorder_level, $quantity, $status);
            
            if ($stmt->execute()) {
                $success = "Product added successfully!";
            } else {
                throw new Exception("Failed to add product.");
            }
        } elseif ($_POST['action'] == 'edit') {
            $product_id = intval($_POST['product_id']);
            $stmt = $conn->prepare("UPDATE product SET product_name=?, product_category=?, unit=?, selling_price=?, reorder_level=?, quantity=?, status=? WHERE product_id=?");
            $stmt->bind_param("sssdiisi", $product_name, $product_category, $unit, $selling_price, $reorder_level, $quantity, $status, $product_id);
            
            if ($stmt->execute()) {
                $success = "Product updated successfully!";
            } else {
                throw new Exception("Failed to update product.");
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Product error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    try {
        $product_id = intval($_GET['delete']);
        $stmt = $conn->prepare("DELETE FROM product WHERE product_id=?");
        $stmt->bind_param("i", $product_id);
        
        if ($stmt->execute()) {
            $success = "Product deleted successfully!";
        } else {
            throw new Exception("Failed to delete product.");
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Delete error: " . $e->getMessage());
        $error = "Cannot delete product. It may be referenced in other records.";
    }
}

// Search and Filter
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';

$whereClause = "WHERE 1=1";
if ($search) {
    $whereClause .= " AND (product_name LIKE '%" . $conn->real_escape_string($search) . "%' 
                     OR product_category LIKE '%" . $conn->real_escape_string($search) . "%')";
}
if ($category_filter) {
    $whereClause .= " AND product_category = '" . $conn->real_escape_string($category_filter) . "'";
}

// Get all products
$productsQuery = "SELECT * FROM product $whereClause ORDER BY product_id DESC";
$products = $conn->query($productsQuery);

// Get categories for filter
$categoriesQuery = "SELECT DISTINCT product_category FROM product ORDER BY product_category";
$categories = $conn->query($categoriesQuery);

// Get product for editing
$editProduct = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $editQuery = $conn->prepare("SELECT * FROM product WHERE product_id=?");
    $editQuery->bind_param("i", $edit_id);
    $editQuery->execute();
    $editProduct = $editQuery->get_result()->fetch_assoc();
    $editQuery->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Stock Management System</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Products Management</h1>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openModal()">+ Add Product</button>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Search and Filter -->
        <div class="table-container">
            <form method="GET" action="products.php" style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                <div class="search-box">
                    <i>🔍</i>
                    <input type="text" name="search" placeholder="Search products..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <select name="category" class="form-control" style="width: 200px;">
                    <option value="">All Categories</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($cat['product_category']); ?>"
                                <?php echo $category_filter == $cat['product_category'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['product_category']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <a href="../export_excel.php" class="btn btn-success"> 📊 Export Excel</a>
                <button onclick="window.print()">🖨️ Print</button>

                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="products.php" class="btn btn-warning">Reset</a>
            </form>
            
            <?php if ($products && $products->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $products->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $product['product_id']; ?></td>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['product_category']); ?></td>
                        <td><?php echo htmlspecialchars($product['unit']); ?></td>
                        <td><?php echo formatCurrency($product['selling_price']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $product['quantity'] < $product['reorder_level'] ? 'danger' : 'success'; ?>">
                                <?php echo $product['quantity']; ?>
                            </span>
                        </td>
                        <td><?php echo $product['reorder_level']; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $product['status'] == 'active' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($product['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="products.php?edit=<?php echo $product['product_id']; ?>" 
                               class="btn btn-warning btn-sm">Edit</a>
                            <a href="products.php?delete=<?php echo $product['product_id']; ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #6c757d; padding: 20px;">No products found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="productModal" class="modal" style="display: <?php echo $editProduct ? 'block' : 'none'; ?>;">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h2>
            
            <form method="POST" action="products.php">
                <input type="hidden" name="action" value="<?php echo $editProduct ? 'edit' : 'add'; ?>">
                <?php if ($editProduct): ?>
                    <input type="hidden" name="product_id" value="<?php echo $editProduct['product_id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="product_name" class="form-control" required
                           value="<?php echo $editProduct ? htmlspecialchars($editProduct['product_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Category *</label>
                    <input type="text" name="product_category" class="form-control" required
                           value="<?php echo $editProduct ? htmlspecialchars($editProduct['product_category']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Unit *</label>
                    <select name="unit" class="form-control" required>
                        <option value="">Select Unit</option>
                        <?php 
                        $units = ['kg', 'g', 'l', 'ml', 'pcs', 'box', 'pack'];
                        foreach ($units as $unit): 
                        ?>
                            <option value="<?php echo $unit; ?>" 
                                    <?php echo ($editProduct && $editProduct['unit'] == $unit) ? 'selected' : ''; ?>>
                                <?php echo strtoupper($unit); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Selling Price *</label>
                    <input type="number" name="selling_price" class="form-control" step="0.01" required
                           value="<?php echo $editProduct ? $editProduct['selling_price'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Quantity *</label>
                    <input type="number" name="quantity" class="form-control" required
                           value="<?php echo $editProduct ? $editProduct['quantity'] : '0'; ?>">
                </div>
                
                <div class="form-group">
                    <label>Reorder Level *</label>
                    <input type="number" name="reorder_level" class="form-control" required
                           value="<?php echo $editProduct ? $editProduct['reorder_level'] : '10'; ?>">
                </div>
                
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" class="form-control" required>
                        <option value="active" <?php echo ($editProduct && $editProduct['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($editProduct && $editProduct['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-success">
                    <?php echo $editProduct ? 'Update Product' : 'Add Product'; ?>
                </button>
                <button type="button" class="btn btn-danger" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('productModal').style.display = 'block';
        }
        
        function closeModal() {
            window.location.href = 'products.php';
        }
        
        window.onclick = function(event) {
            var modal = document.getElementById('productModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
    
    <?php closeDBConnection($conn); ?>
</body>
</html>