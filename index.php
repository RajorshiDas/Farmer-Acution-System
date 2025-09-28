<?php
session_start();
include "config.php";

$result = $conn->query("SELECT a.auction_id, p.product_name, a.start_time, a.end_time, a.status, a.current_highest_bid 
                        FROM Auctions a 
                        JOIN Products p ON a.product_id = p.product_id
                        ORDER BY a.start_time DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Auction System - Home</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include "includes/navbar.php"; renderNavbar('home'); ?>
    
    <div class="main-content">
        <div class="container">
            <?php renderPageHeader('Active Auctions', 'Discover fresh produce from local farmers'); ?>
            
            <div class="table-container fade-in">
                <table>
                    <thead>
                        <tr>
                            <th>🌱 Product</th>
                            <th>⏰ Start Time</th>
                            <th>⌛ End Time</th>
                            <th>📊 Status</th>
                            <th>💰 Highest Bid</th>
                            <th>👁️ Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['product_name']) ?></strong></td>
                                <td><?= date('M d, Y H:i', strtotime($row['start_time'])) ?></td>
                                <td><?= date('M d, Y H:i', strtotime($row['end_time'])) ?></td>
                                <td><?= renderStatusBadge($row['status']) ?></td>
                                <td><strong><?= number_format($row['current_highest_bid'], 2) ?> Tk</strong></td>
                                <td><a href="auction.php?id=<?= $row['auction_id'] ?>" class="btn btn-primary btn-small">View Auction</a></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No active auctions found. Check back later!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php renderFooter(); ?>
</body>
</html>
