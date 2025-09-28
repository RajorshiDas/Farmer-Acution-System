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

// Handle adding new product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $starting_bid = floatval($_POST['starting_bid']);
    $auction_duration = intval($_POST['auction_duration']);
    
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
            $message = "Product and auction created successfully!";
        } else {
            $message = "Product created but failed to create auction.";
        }
    } else {
        $message = "Failed to create product. Please try again.";
    }
}

// Handle closing auction
if (isset($_POST['close_auction'])) {
    $auction_id = $_POST['auction_id'];
    $stmt = $conn->prepare("UPDATE Auctions SET status='Closed' WHERE auction_id=? AND product_id IN (SELECT product_id FROM Products WHERE farmer_id=?)");
    $stmt->bind_param("ii", $auction_id, $farmer_id);
    
    if ($stmt->execute()) {
        $message = "Auction closed successfully!";
    } else {
        $message = "Failed to close auction.";
    }
}

// Fetch farmer's products and their auctions with buyer details
$products_query = $conn->prepare("
    SELECT 
        p.product_id,
        p.product_name,
        p.description,
        p.category,
        p.starting_bid,
        p.created_at,
        a.auction_id,
        a.start_time,
        a.end_time,
        a.status,
        a.current_highest_bid,
        (SELECT COUNT(*) FROM Bids b WHERE b.auction_id = a.auction_id) as bid_count,
        winner.name as winner_name,
        winner.email as winner_email,
        winner.phone as winner_phone,
        winner.address as winner_address
    FROM Products p
    LEFT JOIN Auctions a ON p.product_id = a.product_id
    LEFT JOIN (
        SELECT DISTINCT
            b1.auction_id,
            bu.name,
            bu.email, 
            bu.phone,
            bu.address
        FROM Bids b1
        JOIN Buyers bu ON b1.buyer_id = bu.buyer_id
        WHERE b1.is_winning_bid = 1
    ) winner ON a.auction_id = winner.auction_id
    WHERE p.farmer_id = ?
    ORDER BY p.created_at DESC, a.start_time DESC
");

$products_query->bind_param("i", $farmer_id);
$products_query->execute();
$products = $products_query->get_result();

// Get statistics
$stats_query = $conn->prepare("
    SELECT 
        COUNT(DISTINCT p.product_id) as total_products,
        COUNT(DISTINCT a.auction_id) as total_auctions,
        COUNT(DISTINCT CASE WHEN a.status = 'Active' THEN a.auction_id END) as active_auctions,
        COUNT(DISTINCT CASE WHEN a.status = 'Closed' THEN a.auction_id END) as closed_auctions,
        COALESCE(SUM(CASE WHEN a.status = 'Closed' THEN a.current_highest_bid ELSE 0 END), 0) as total_sales,
        COALESCE(AVG(CASE WHEN a.status = 'Closed' THEN a.current_highest_bid END), 0) as avg_sale_price,
        COUNT(DISTINCT CASE WHEN a.status = 'Closed' AND a.current_highest_bid > p.starting_bid THEN a.auction_id END) as profitable_auctions
    FROM Products p
    LEFT JOIN Auctions a ON p.product_id = a.product_id
    WHERE p.farmer_id = ?
");
$stats_query->bind_param("i", $farmer_id);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - Farmer Auction System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php renderNavbar('farmer_dashboard'); ?>
    
    <div class="main-content">
        <div class="container">
            <?php renderPageHeader('Farmer Dashboard', 'Manage your products and auctions'); ?>
            
            <?php renderAlert($message, strpos($message, 'success') !== false ? 'success' : 'info'); ?>
            
            <!-- Statistics Cards -->
            <div class="auction-grid fade-in">
                <div class="card">
                    <h3>📊 Your Statistics</h3>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1rem;">
                        <div style="text-align: center;">
                            <div class="current-bid"><?= $stats['total_products'] ?></div>
                            <p>Products Listed</p>
                        </div>
                        <div style="text-align: center;">
                            <div class="current-bid" style="color: #28a745;"><?= $stats['active_auctions'] ?></div>
                            <p>Active Auctions</p>
                        </div>
                        <div style="text-align: center;">
                            <div class="current-bid" style="color: #007bff;"><?= $stats['closed_auctions'] ?></div>
                            <p>Closed Auctions</p>
                        </div>
                    </div>
                    <hr style="margin: 1rem 0;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                        <div>
                            <p><strong>💰 Total Earnings:</strong> <span style="color: #28a745; font-size: 1.2em;"><?= number_format($stats['total_sales'] ?? 0, 2) ?> Tk</span></p>
                            <p><strong>� Average Sale:</strong> <?= number_format($stats['avg_sale_price'] ?? 0, 2) ?> Tk</p>
                        </div>
                        <div>
                            <p><strong>🎯 Profitable Auctions:</strong> <?= $stats['profitable_auctions'] ?></p>
                            <p><strong>📊 Success Rate:</strong> <?= $stats['closed_auctions'] > 0 ? round(($stats['profitable_auctions']/$stats['closed_auctions'])*100, 1) : 0 ?>%</p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Add Product Form -->
                <div class="card">
                    <h3>➕ Add New Product</h3>
                    <form method="POST" style="margin-top: 1rem;">
                        <input type="hidden" name="add_product" value="1">
                        
                        <div class="form-group">
                            <label>🌱 Product Name</label>
                            <input type="text" name="product_name" class="form-control" placeholder="e.g., Fresh Tomatoes" required>
                        </div>
                        
                        <div class="form-group">
                            <label>📝 Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Brief description..."></textarea>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                            <div class="form-group">
                                <label>🏷️ Category</label>
                                <select name="category" class="form-control" required>
                                    <option value="">Select...</option>
                                    <option value="Vegetables">Vegetables</option>
                                    <option value="Fruits">Fruits</option>
                                    <option value="Grains">Grains</option>
                                    <option value="Dairy">Dairy</option>
                                    <option value="Meat">Meat</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>💰 Starting Bid (Tk)</label>
                                <input type="number" name="starting_bid" class="form-control" step="0.01" min="1" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>📅 Auction Duration</label>
                            <select name="auction_duration" class="form-control" required>
                                <option value="1">1 Day</option>
                                <option value="3">3 Days</option>
                                <option value="7" selected>7 Days</option>
                                <option value="14">14 Days</option>
                                <option value="30">30 Days</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-success" style="width: 100%;">🚀 Create Product & Start Auction</button>
                    </form>
                </div>
            </div>
            
            <!-- Products and Auctions Table -->
            <div class="table-container fade-in">
                <h3 style="padding: 1rem; background: linear-gradient(135deg, #2c5530, #4a7c59); color: white; margin: 0;">
                    🌾 My Products & Auctions
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>🌱 Product</th>
                            <th>🏷️ Category</th>
                            <th>💰 Starting/Current Bid</th>
                            <th>📊 Status</th>
                            <th>📅 End Time</th>
                            <th>💬 Bids</th>
                            <th>🔗 Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products && $products->num_rows > 0): ?>
                            <?php while($product = $products->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($product['product_name']) ?></strong>
                                    <?php if ($product['description']): ?>
                                        <br><small style="color: #666;"><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['category']) ?></td>
                                <td>
                                    <strong><?= number_format($product['starting_bid'], 2) ?> Tk</strong>
                                    <?php if ($product['auction_id'] && $product['current_highest_bid'] > $product['starting_bid']): ?>
                                        <br><span style="color: #28a745;">Current: <?= number_format($product['current_highest_bid'], 2) ?> Tk</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['auction_id']): ?>
                                        <?= renderStatusBadge($product['status']) ?>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">📝 No Auction</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['auction_id']): ?>
                                        <?= date('M d, Y H:i', strtotime($product['end_time'])) ?>
                                        <?php if ($product['status'] == 'Active'): ?>
                                            <br><small id="countdown-<?= $product['auction_id'] ?>">⏰ Loading...</small>
                                            <script>
                                                startCountdown('<?= $product['end_time'] ?>', 'countdown-<?= $product['auction_id'] ?>');
                                            </script>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #666;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['auction_id']): ?>
                                        <strong><?= $product['bid_count'] ?></strong> bids
                                        <?php if ($product['bid_count'] > 0): ?>
                                            <br><a href="bids.php?id=<?= $product['auction_id'] ?>" style="font-size: 0.8rem;">View bids</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #666;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['auction_id']): ?>
                                        <a href="auction.php?id=<?= $product['auction_id'] ?>" class="btn btn-primary btn-small">👁️ View</a>
                                        
                                        <?php if ($product['status'] == 'Active'): ?>
                                            <form method="POST" style="display: inline; margin-top: 0.25rem;" 
                                                  onsubmit="return confirm('Are you sure you want to close this auction? This action cannot be undone.')">
                                                <input type="hidden" name="auction_id" value="<?= $product['auction_id'] ?>">
                                                <button type="submit" name="close_auction" class="btn btn-danger btn-small">🔐 Close Auction</button>
                                            </form>
                                        <?php elseif ($product['status'] == 'Closed'): ?>
                                            <a href="bids.php?id=<?= $product['auction_id'] ?>" class="btn btn-success btn-small">📊 Results</a>
                                            
                                            <?php if ($product['winner_name']): ?>
                                                <div style="margin-top: 0.5rem; padding: 0.5rem; background: #e7f3ff; border-radius: 3px; font-size: 0.8rem;">
                                                    <strong>🏆 Winner:</strong> <?= htmlspecialchars($product['winner_name']) ?><br>
                                                    <strong>📧 Email:</strong> <?= htmlspecialchars($product['winner_email']) ?><br>
                                                    <strong>📱 Phone:</strong> <?= htmlspecialchars($product['winner_phone']) ?><br>
                                                    <strong>🏠 Address:</strong> <?= htmlspecialchars($product['winner_address']) ?>
                                                </div>
                                            <?php else: ?>
                                                <div style="margin-top: 0.5rem; padding: 0.5rem; background: #fff3cd; border-radius: 3px; font-size: 0.8rem;">
                                                    <strong>⚠️ No bids received</strong>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #666; font-size: 0.8rem;">Create auction first</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div style="padding: 2rem;">
                                        <h4>No products yet!</h4>
                                        <p>Add your first product using the form above to start selling.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="text-center mt-3">
                <a href="index.php" class="btn btn-primary">🏠 View All Auctions</a>
                <a href="auth/logout.php" class="btn btn-secondary">🚪 Logout</a>
            </div>
        </div>
    </div>
    
    <?php renderFooter(); ?>
    
    <script src="assets/js/main.js"></script>
    <script>
        // Auto-refresh every 60 seconds for active auctions
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                location.reload();
            }
        }, 60000);
    </script>
</body>
</html>