<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<button id="hamburger" class="hamburger">=</button>
<div id="overlay"></div>
<div class="sidebar">
     <div class="login-header" style= "background: linear-gradient(135deg, #1e3d52, #1e3d52);color: white;">
            <i class="bi bi-box-seam fs-1"></i>
            <h1>StockFlow IMS</h1>
            <small>Inventory Management System</small>
        
        <p><?php echo htmlspecialchars($_SESSION['username']); ?></p>
    </div>
    
    <nav>
        <ul>
            <li>
                <a href="../dashboard/dashboard.php" class="<?php echo $current_page == '../dashboard/dashboard.php' ? 'active' : ''; ?>">
                    <i>📊</i> Dashboard
                </a>
            </li>
            <li>
                <a href="../products/products.php" class="<?php echo $current_page == '../products/products.php' ? 'active' : ''; ?>">
                    <i>📦</i> Products
                </a>
            </li>
            <li>
                <a href="../suppliers/suppliers.php" class="<?php echo $current_page == '../suppliers/suppliers.php' ? 'active' : ''; ?>">
                    <i>🏢</i> Suppliers
                </a>
            </li>
            <li>
                <a href="../purchases/purchase_orders.php" class="<?php echo $current_page == '../purchases/purchase_orders.php' ? 'active' : ''; ?>">
                    <i>🛒</i> Purchase Orders
                </a>
            </li>
            <li>
                <a href="../sales/sales.php" class="<?php echo $current_page == '../sales/sales.php' ? 'active' : ''; ?>">
                    <i>💰</i> Sales
                </a>
            </li>
            <li>
                <a href="../products/batches.php" class="<?php echo $current_page == '../products/batches.php' ? 'active' : ''; ?>">
                    <i>📋</i> Batches
                </a>
            </li>
            <li>
                <a href="../reports/reports.php" class="<?php echo $current_page == '../reports/reports.php' ? 'active' : ''; ?>">
                    <i>📈</i> Reports
                </a>
            </li>
            <li>
                <a href="../auth/logout.php" style="margin-top: 20px; color: #e74c3c;">
                    <i>🚪</i> Logout
                </a>
            </li>
        </ul>
    </nav>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const hamburger = document.getElementById("hamburger");
    const sidebar = document.querySelector(".sidebar");
    const overlay = document.getElementById("overlay");

    hamburger.addEventListener("click", function () {
        sidebar.classList.add("active");
        overlay.classList.add("active");
        hamburger.style.display = "none"; // hide =
    });

    overlay.addEventListener("click", function () {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
        hamburger.style.display = "block"; // show = again
    });
});
</script>
