<?php
session_start();
include "config.php";

$id = $_GET['id'] ?? 0;

// Use prepared statements to prevent SQL injection
$bids_stmt = $conn->prepare("SELECT b.bid_amount, b.is_winning_bid, u.name, u.email, u.phone, u.address, b.bid_time 
                      FROM Bids b 
                      JOIN Buyers u ON b.buyer_id = u.buyer_id
                      WHERE b.auction_id = ? 
                      ORDER BY b.bid_amount DESC");
$bids_stmt->bind_param("i", $id);
$bids_stmt->execute();
$bids = $bids_stmt->get_result();

$payments_stmt = $conn->prepare("SELECT p.*, f.name as farmer_name, b.name as buyer_name 
                                FROM Payments p 
                                JOIN Farmers f ON p.farmer_id = f.farmer_id 
                                JOIN Buyers b ON p.buyer_id = b.buyer_id 
                                WHERE p.auction_id = ?");
$payments_stmt->bind_param("i", $id);
$payments_stmt->execute();
$payments = $payments_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction Results - Farmer Auction System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include "includes/navbar.php"; renderNavbar(); ?>
    
    <div class="main-content">
        <div class="container">
            <?php renderPageHeader('Auction Results', 'View all bids and payment information'); ?>
            
            <!-- Bidding Results -->
            <div class="table-container fade-in">
                <h3 style="padding: 1rem; background: linear-gradient(135deg, #2c5530, #4a7c59); color: white; margin: 0;">
                    🏆 All Bids for Auction #<?= $id ?>
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>👤 Buyer</th>
                            <th>💰 Amount</th>
                            <th>⏰ Time</th>
                            <th>🏆 Winner</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bids && $bids->num_rows > 0): ?>
                            <?php while($b = $bids->fetch_assoc()): ?>
                            <tr <?= $b['is_winning_bid'] ? 'style="background-color: #d4edda; font-weight: bold;"' : '' ?>>
                                <td>
                                    <?= htmlspecialchars($b['name']) ?>
                                    <?php if ($b['is_winning_bid']): ?>
                                        <div style="margin-top: 0.5rem; font-size: 0.8rem; font-weight: normal;">
                                            <strong>📧</strong> <?= htmlspecialchars($b['email']) ?><br>
                                            <strong>📱</strong> <?= htmlspecialchars($b['phone']) ?><br>
                                            <strong>🏠</strong> <?= htmlspecialchars($b['address']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= number_format($b['bid_amount'], 2) ?> Tk</strong></td>
                                <td><?= date('M d, Y H:i', strtotime($b['bid_time'])) ?></td>
                                <td>
                                    <?php if ($b['is_winning_bid']): ?>
                                        <span class="status-badge status-active">🏆 Winner</span>
                                    <?php else: ?>
                                        <span style="color: #666;">–</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No bids found for this auction.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Payment Information -->
            <div class="card fade-in" style="margin-top: 2rem;">
                <h3>💳 Payment Information</h3>
                <?php if ($payments && $payments->num_rows > 0): ?>
                    <?php while($p = $payments->fetch_assoc()): ?>
                        <div class="alert alert-info" style="margin: 1rem 0;">
                            <h4 style="margin-bottom: 0.5rem;">💰 Payment Required</h4>
                            <p><strong>🛍️ Buyer:</strong> <?= htmlspecialchars($p['buyer_name']) ?></p>
                            <p><strong>🌾 Farmer:</strong> <?= htmlspecialchars($p['farmer_name']) ?></p>
                            <p><strong>💵 Amount:</strong> <?= number_format($p['amount'], 2) ?> Tk</p>
                            <p><strong>📊 Status:</strong> 
                                <span class="status-badge <?= $p['status'] == 'Completed' ? 'status-active' : ($p['status'] == 'Pending' ? 'status-pending' : 'status-closed') ?>">
                                    <?= $p['status'] == 'Completed' ? '✅' : ($p['status'] == 'Pending' ? '⏳' : '❌') ?> <?= $p['status'] ?>
                                </span>
                            </p>
                            <small style="color: #666;">Payment created on: <?= date('M d, Y H:i', strtotime($p['created_at'])) ?></small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p>📋 No payment information available for this auction yet.</p>
                        <p><small>Payments are automatically created when an auction is closed.</small></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-3">
                <a href="index.php" class="btn btn-primary">← Back to Auctions</a>
                <a href="close.php?id=<?= $id ?>" class="btn btn-danger" 
                   onclick="return confirm('Are you sure you want to close this auction? This action cannot be undone.')">
                    🔒 Close Auction
                </a>
            </div>
        </div>
    </div>
    
    <?php renderFooter(); ?>
</body>
</html>
