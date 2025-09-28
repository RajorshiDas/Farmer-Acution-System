<?php
session_start();
include "config.php";
include "includes/navbar.php";

// Check if user is logged in as buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Buyer') {
    header("Location: auth/login.php");
    exit;
}

$buyer_id = $_SESSION['user_id'];

// Fetch all bids by this buyer with auction and product details
$bids_query = $conn->prepare("
    SELECT 
        b.bid_id,
        b.bid_amount,
        b.bid_time,
        b.is_winning_bid,
        a.auction_id,
        a.status as auction_status,
        a.end_time,
        a.current_highest_bid,
        p.product_name,
        p.description,
        p.starting_bid,
        f.name as farmer_name,
        f.email as farmer_email,
        f.phone as farmer_phone,
        f.address as farmer_address
    FROM Bids b
    JOIN Auctions a ON b.auction_id = a.auction_id
    JOIN Products p ON a.product_id = p.product_id
    JOIN Farmers f ON p.farmer_id = f.farmer_id
    WHERE b.buyer_id = ?
    ORDER BY b.bid_time DESC
");

$bids_query->bind_param("i", $buyer_id);
$bids_query->execute();
$my_bids = $bids_query->get_result();

// Get summary statistics
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_bids,
        COUNT(CASE WHEN is_winning_bid = 1 THEN 1 END) as winning_bids,
        SUM(bid_amount) as total_bid_amount,
        MAX(bid_amount) as highest_bid
    FROM Bids 
    WHERE buyer_id = ?
");
$stats_query->bind_param("i", $buyer_id);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();

// Get active auctions where user has bid
$active_bids_query = $conn->prepare("
    SELECT DISTINCT a.auction_id, p.product_name, a.current_highest_bid, b.bid_amount,
           (b.bid_amount = a.current_highest_bid) as is_leading
    FROM Bids b
    JOIN Auctions a ON b.auction_id = a.auction_id
    JOIN Products p ON a.product_id = p.product_id
    WHERE b.buyer_id = ? AND a.status = 'Active'
    GROUP BY a.auction_id
    HAVING b.bid_amount = MAX(b.bid_amount)
    ORDER BY b.bid_time DESC
");
$active_bids_query->bind_param("i", $buyer_id);
$active_bids_query->execute();
$active_bids = $active_bids_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bids - Farmer Auction System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php renderNavbar('my_bids'); ?>
    
    <div class="main-content">
        <div class="container">
            <?php renderPageHeader('My Bidding History', 'Track all your bids and winning auctions'); ?>
            
            <!-- Statistics Cards -->
            <div class="auction-grid fade-in">
                <div class="card">
                    <h3>📊 Bidding Statistics</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                        <div style="text-align: center;">
                            <div class="current-bid"><?= $stats['total_bids'] ?></div>
                            <p>Total Bids Placed</p>
                        </div>
                        <div style="text-align: center;">
                            <div class="current-bid" style="color: #28a745;"><?= $stats['winning_bids'] ?></div>
                            <p>Auctions Won</p>
                        </div>
                    </div>
                    <hr style="margin: 1rem 0;">
                    <p><strong>💰 Total Amount Bid:</strong> <?= number_format($stats['total_bid_amount'] ?? 0, 2) ?> Tk</p>
                    <p><strong>🏆 Highest Single Bid:</strong> <?= number_format($stats['highest_bid'] ?? 0, 2) ?> Tk</p>
                </div>
                
                <div class="card">
                    <h3>🎯 Active Auctions</h3>
                    <?php if ($active_bids && $active_bids->num_rows > 0): ?>
                        <?php while($active = $active_bids->fetch_assoc()): ?>
                            <div style="border-left: 4px solid <?= $active['is_leading'] ? '#28a745' : '#ffc107' ?>; padding-left: 1rem; margin-bottom: 1rem;">
                                <h4 style="margin-bottom: 0.5rem;">
                                    <a href="auction.php?id=<?= $active['auction_id'] ?>" style="color: #2c5530; text-decoration: none;">
                                        <?= htmlspecialchars($active['product_name']) ?>
                                    </a>
                                </h4>
                                <p style="margin: 0;">
                                    <?php if ($active['is_leading']): ?>
                                        <span class="status-badge status-active">🏆 Leading</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">💡 Outbid</span>
                                    <?php endif; ?>
                                    Your bid: <?= number_format($active['bid_amount'], 2) ?> Tk | 
                                    Current highest: <?= number_format($active['current_highest_bid'], 2) ?> Tk
                                </p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #666; text-align: center;">No active bids found. <a href="index.php">Browse auctions</a> to start bidding!</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- All Bids History -->
            <div class="table-container fade-in">
                <h3 style="padding: 1rem; background: linear-gradient(135deg, #2c5530, #4a7c59); color: white; margin: 0;">
                    📋 Complete Bidding History
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>🛍️ Product</th>
                            <th>👨‍🌾 Farmer</th>
                            <th>💰 My Bid</th>
                            <th>🏆 Highest Bid</th>
                            <th>📅 Bid Time</th>
                            <th>📊 Auction Status</th>
                            <th>🎯 Result</th>
                            <th>🔗 Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($my_bids && $my_bids->num_rows > 0): ?>
                            <?php while($bid = $my_bids->fetch_assoc()): ?>
                            <tr <?= $bid['is_winning_bid'] ? 'style="background-color: #d4edda;"' : '' ?>>
                                <td>
                                    <strong><?= htmlspecialchars($bid['product_name']) ?></strong>
                                    <?php if ($bid['description']): ?>
                                        <br><small style="color: #666;"><?= htmlspecialchars(substr($bid['description'], 0, 50)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($bid['farmer_name']) ?></td>
                                <td>
                                    <strong><?= number_format($bid['bid_amount'], 2) ?> Tk</strong>
                                    <?php if ($bid['bid_amount'] == $bid['current_highest_bid'] && $bid['auction_status'] == 'Active'): ?>
                                        <br><small style="color: #28a745;">💪 Leading!</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format($bid['current_highest_bid'], 2) ?> Tk</td>
                                <td><?= date('M d, Y H:i', strtotime($bid['bid_time'])) ?></td>
                                <td><?= renderStatusBadge($bid['auction_status']) ?></td>
                                <td>
                                    <?php if ($bid['is_winning_bid']): ?>
                                        <span class="status-badge status-active">🏆 Won</span>
                                    <?php elseif ($bid['auction_status'] == 'Closed'): ?>
                                        <span class="status-badge status-closed">❌ Lost</span>
                                    <?php elseif ($bid['bid_amount'] == $bid['current_highest_bid']): ?>
                                        <span class="status-badge status-active">💪 Leading</span>
                                    <?php elseif ($bid['auction_status'] == 'Active'): ?>
                                        <span class="status-badge status-pending">⏳ Outbid</span>
                                    <?php else: ?>
                                        <span style="color: #666;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="auction.php?id=<?= $bid['auction_id'] ?>" class="btn btn-primary btn-small">
                                        View Auction
                                    </a>
                                    <?php if ($bid['auction_status'] == 'Closed'): ?>
                                        <a href="bids.php?id=<?= $bid['auction_id'] ?>" class="btn btn-secondary btn-small" style="margin-top: 0.25rem;">
                                            View Results
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($bid['is_winning_bid']): ?>
                                        <div style="margin-top: 0.5rem; padding: 0.5rem; background: #e7f3ff; border-radius: 3px; font-size: 0.8rem;">
                                            <strong>📞 Contact Farmer:</strong><br>
                                            <strong>📧</strong> <?= htmlspecialchars($bid['farmer_email']) ?><br>
                                            <strong>📱</strong> <?= htmlspecialchars($bid['farmer_phone']) ?><br>
                                            <strong>🏠</strong> <?= htmlspecialchars($bid['farmer_address']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div style="padding: 2rem;">
                                        <h4>No bids placed yet!</h4>
                                        <p>Start bidding on auctions to see your history here.</p>
                                        <a href="index.php" class="btn btn-primary">Browse Auctions</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($my_bids && $my_bids->num_rows > 0): ?>
            <div class="text-center mt-3">
                <a href="index.php" class="btn btn-primary">🔍 Browse More Auctions</a>
                <button onclick="window.print()" class="btn btn-secondary">🖨️ Print History</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php renderFooter(); ?>
    
    <script src="assets/js/main.js"></script>
    <script>
        // Auto-refresh every 60 seconds for active bids
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                location.reload();
            }
        }, 60000);
        
        // Highlight winning bids with animation
        document.addEventListener('DOMContentLoaded', function() {
            const winningRows = document.querySelectorAll('tr[style*="background-color: #d4edda"]');
            winningRows.forEach(row => {
                row.style.animation = 'fadeIn 0.8s ease-out';
            });
        });
    </script>
</body>
</html>