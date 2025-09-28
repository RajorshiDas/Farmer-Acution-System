<?php
session_start();
include "config.php";
include "includes/navbar.php";

// Check if user is logged in as farmer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Farmer') {
    header("Location: auth/login.php");
    exit;
}

$farmer_id = $_SESSION['user_id'];
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $starting_bid = floatval($_POST['starting_bid']);
    $auction_duration = intval($_POST['auction_duration']);
    
    // Validate inputs
    if (empty($product_name) || empty($category) || $starting_bid <= 0) {
        $message = "Please fill in all required fields with valid values.";
    } else {
        // Insert product
        $stmt = $conn->prepare("INSERT INTO Products (farmer_id, product_name, description, category, starting_bid) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssd", $farmer_id, $product_name, $description, $category, $starting_bid);
        
        if ($stmt->execute()) {
            $product_id = $conn->insert_id;
            
            // Create auction for the product
            $start_time = date('Y-m-d H:i:s');
            $end_time = date('Y-m-d H:i:s', strtotime("+{$auction_duration} days"));
            
            $auction_stmt = $conn->prepare("INSERT INTO Auctions (product_id, start_time, end_time, current_highest_bid) VALUES (?, ?, ?, ?)");
            $auction_stmt->bind_param("issd", $product_id, $start_time, $end_time, $starting_bid);
            
            if ($auction_stmt->execute()) {
                $message = "Product and auction created successfully! <a href='farmer_dashboard.php' style='color:#28a745;'>View in dashboard</a>";
            } else {
                $message = "Product created but failed to create auction. Please try again.";
            }
        } else {
            $message = "Failed to create product. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Farmer Auction System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php renderNavbar(); ?>
    
    <div class="main-content">
        <div class="container">
            <?php renderPageHeader('Add New Product', 'Create a product listing and start an auction'); ?>
            
            <div class="form-container fade-in">
                <form method="POST">
                    <?php 
                    if (strpos($message, 'success') !== false) {
                        renderAlert($message, 'success');
                    } elseif (!empty($message)) {
                        renderAlert($message, 'error');
                    }
                    ?>
                    
                    <div class="form-group">
                        <label for="product_name">🌱 Product Name *</label>
                        <input type="text" name="product_name" id="product_name" class="form-control" 
                               value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>" 
                               placeholder="e.g., Fresh Organic Tomatoes - 10kg" required>
                        <small style="color: #666;">Be specific about quantity and quality</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">📝 Description</label>
                        <textarea name="description" id="description" class="form-control" rows="4" 
                                  placeholder="Describe your product: quality, origin, harvesting details, etc."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">🏷️ Category *</label>
                        <select name="category" id="category" class="form-control" required>
                            <option value="">Select a category...</option>
                            <option value="Vegetables" <?= (isset($_POST['category']) && $_POST['category'] == 'Vegetables') ? 'selected' : '' ?>>🥕 Vegetables</option>
                            <option value="Fruits" <?= (isset($_POST['category']) && $_POST['category'] == 'Fruits') ? 'selected' : '' ?>>🍎 Fruits</option>
                            <option value="Grains" <?= (isset($_POST['category']) && $_POST['category'] == 'Grains') ? 'selected' : '' ?>>🌾 Grains & Cereals</option>
                            <option value="Dairy" <?= (isset($_POST['category']) && $_POST['category'] == 'Dairy') ? 'selected' : '' ?>>🥛 Dairy Products</option>
                            <option value="Meat" <?= (isset($_POST['category']) && $_POST['category'] == 'Meat') ? 'selected' : '' ?>>🥩 Meat & Poultry</option>
                            <option value="Herbs" <?= (isset($_POST['category']) && $_POST['category'] == 'Herbs') ? 'selected' : '' ?>>🌿 Herbs & Spices</option>
                            <option value="Other" <?= (isset($_POST['category']) && $_POST['category'] == 'Other') ? 'selected' : '' ?>>📦 Other</option>
                        </select>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="starting_bid">💰 Starting Bid (Tk) *</label>
                            <input type="number" name="starting_bid" id="starting_bid" class="form-control" 
                                   step="0.01" min="1" 
                                   value="<?= htmlspecialchars($_POST['starting_bid'] ?? '') ?>" 
                                   placeholder="e.g., 500.00" required>
                            <small style="color: #666;">Minimum acceptable price</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="auction_duration">📅 Auction Duration *</label>
                            <select name="auction_duration" id="auction_duration" class="form-control" required>
                                <option value="1" <?= (isset($_POST['auction_duration']) && $_POST['auction_duration'] == '1') ? 'selected' : '' ?>>1 Day (Quick Sale)</option>
                                <option value="3" <?= (isset($_POST['auction_duration']) && $_POST['auction_duration'] == '3') ? 'selected' : '' ?>>3 Days</option>
                                <option value="7" <?= (!isset($_POST['auction_duration']) || $_POST['auction_duration'] == '7') ? 'selected' : '' ?>>7 Days (Recommended)</option>
                                <option value="14" <?= (isset($_POST['auction_duration']) && $_POST['auction_duration'] == '14') ? 'selected' : '' ?>>14 Days</option>
                                <option value="30" <?= (isset($_POST['auction_duration']) && $_POST['auction_duration'] == '30') ? 'selected' : '' ?>>30 Days (Long-term)</option>
                            </select>
                            <small style="color: #666;">How long buyers can bid</small>
                        </div>
                    </div>
                    
                    <div style="background: #e7f3ff; padding: 1rem; border-radius: 5px; margin: 1rem 0;">
                        <h4 style="color: #0c5460; margin-bottom: 0.5rem;">📋 What happens next?</h4>
                        <ul style="margin: 0; padding-left: 1.5rem; color: #0c5460;">
                            <li>Your product will be listed immediately</li>
                            <li>Auction starts right away</li>
                            <li>Buyers can place bids until the end time</li>
                            <li>You can monitor bids in your dashboard</li>
                            <li>Highest bidder wins when auction closes</li>
                        </ul>
                    </div>
                    
                    <button type="submit" class="btn btn-success" style="width: 100%;">
                        🚀 Create Product & Start Auction
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="farmer_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php renderFooter(); ?>
    
    <script>
        // Real-time validation
        document.getElementById('starting_bid').addEventListener('input', function() {
            const value = parseFloat(this.value);
            const submitBtn = document.querySelector('button[type="submit"]');
            
            if (value < 1 || isNaN(value)) {
                this.style.borderColor = '#dc3545';
                submitBtn.disabled = true;
            } else {
                this.style.borderColor = '#28a745';
                submitBtn.disabled = false;
            }
        });
    </script>
</body>
</html>