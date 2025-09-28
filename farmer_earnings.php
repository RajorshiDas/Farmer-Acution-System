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

// Get detailed earnings data
$earnings_query = $conn->prepare("
    SELECT 
        p.product_name,
        p.category,
        p.starting_bid,
        a.auction_id,
        a.start_time,
        a.end_time,
        a.current_highest_bid,
        (a.current_highest_bid - p.starting_bid) as profit,
        bu.name as buyer_name,
        bu.email as buyer_email,
        bu.phone as buyer_phone,
        CASE 
            WHEN a.current_highest_bid > p.starting_bid THEN 'Profit'
            WHEN a.current_highest_bid = p.starting_bid THEN 'Break-even'
            ELSE 'Loss'
        END as profit_status
    FROM Products p
    JOIN Auctions a ON p.product_id = a.product_id
    LEFT JOIN (
        SELECT DISTINCT b1.auction_id, bu.buyer_id, bu.name, bu.email, bu.phone
        FROM Bids b1
        JOIN Buyers bu ON b1.buyer_id = bu.buyer_id
        WHERE b1.is_winning_bid = 1
    ) bu ON a.auction_id = bu.auction_id
    WHERE p.farmer_id = ? AND a.status = 'Closed'
    ORDER BY a.end_time DESC
");
$earnings_query->bind_param("i", $farmer_id);
$earnings_query->execute();
$earnings = $earnings_query->get_result();

// Get summary statistics
$summary_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_closed_auctions,
        SUM(a.current_highest_bid) as total_revenue,
        SUM(p.starting_bid) as total_investment,
        SUM(a.current_highest_bid - p.starting_bid) as total_profit,
        AVG(a.current_highest_bid) as avg_sale_price,
        MAX(a.current_highest_bid) as highest_sale,
        MIN(a.current_highest_bid) as lowest_sale,
        COUNT(CASE WHEN a.current_highest_bid > p.starting_bid THEN 1 END) as profitable_auctions,
        COUNT(CASE WHEN a.current_highest_bid = p.starting_bid THEN 1 END) as breakeven_auctions
    FROM Products p
    JOIN Auctions a ON p.product_id = a.product_id
    WHERE p.farmer_id = ? AND a.status = 'Closed'
");
$summary_query->bind_param("i", $farmer_id);
$summary_query->execute();
$summary = $summary_query->get_result()->fetch_assoc();

// Get monthly earnings
$monthly_query = $conn->prepare("
    SELECT 
        DATE_FORMAT(a.end_time, '%Y-%m') as month,
        COUNT(*) as auctions_closed,
        SUM(a.current_highest_bid) as monthly_revenue,
        SUM(a.current_highest_bid - p.starting_bid) as monthly_profit
    FROM Products p
    JOIN Auctions a ON p.product_id = a.product_id
    WHERE p.farmer_id = ? AND a.status = 'Closed'
    GROUP BY DATE_FORMAT(a.end_time, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
$monthly_query->bind_param("i", $farmer_id);
$monthly_query->execute();
$monthly_data = $monthly_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Earnings - Farmer Auction System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php renderNavbar('earnings'); ?>
    
    <div class="main-content">
        <div class="container">
            <?php renderPageHeader('My Earnings Report', 'Detailed breakdown of your sales and profits'); ?>
            
            <!-- Summary Statistics -->
            <div class="auction-grid fade-in">
                <div class="card">
                    <h3>💰 Total Earnings Summary</h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 1rem;">
                        <div>
                            <div class="current-bid" style="color: #28a745;"><?= number_format($summary['total_revenue'] ?? 0, 2) ?> Tk</div>
                            <p>Total Revenue</p>
                        </div>
                        <div>
                            <div class="current-bid" style="color: #007bff;"><?= number_format($summary['total_profit'] ?? 0, 2) ?> Tk</div>
                            <p>Total Profit</p>
                        </div>
                    </div>
                    <hr style="margin: 1rem 0;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; font-size: 0.9rem;">
                        <div>
                            <strong>🏆 Best Sale:</strong><br><?= number_format($summary['highest_sale'] ?? 0, 2) ?> Tk
                        </div>
                        <div>
                            <strong>📊 Average Sale:</strong><br><?= number_format($summary['avg_sale_price'] ?? 0, 2) ?> Tk
                        </div>
                        <div>
                            <strong>📈 Success Rate:</strong><br><?= $summary['total_closed_auctions'] > 0 ? round(($summary['profitable_auctions']/$summary['total_closed_auctions'])*100, 1) : 0 ?>%
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <h3>📊 Performance Breakdown</h3>
                    <div style="margin-top: 1rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Profitable Auctions</span>
                            <strong style="color: #28a745;"><?= $summary['profitable_auctions'] ?? 0 ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Break-even Auctions</span>
                            <strong style="color: #ffc107;"><?= $summary['breakeven_auctions'] ?? 0 ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Total Closed Auctions</span>
                            <strong><?= $summary['total_closed_auctions'] ?? 0 ?></strong>
                        </div>
                        <hr style="margin: 1rem 0;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>Profit Margin</span>
                            <strong style="color: #007bff;">
                                <?= $summary['total_revenue'] > 0 ? round(($summary['total_profit']/$summary['total_revenue'])*100, 1) : 0 ?>%
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Earnings -->
            <?php if ($monthly_data && $monthly_data->num_rows > 0): ?>
            <div class="table-container fade-in">
                <h3 style="padding: 1rem; background: linear-gradient(135deg, #2c5530, #4a7c59); color: white; margin: 0;">
                    📅 Monthly Earnings Breakdown
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>📅 Month</th>
                            <th>📦 Auctions Closed</th>
                            <th>💰 Revenue</th>
                            <th>📈 Profit</th>
                            <th>📊 Avg. per Auction</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($month = $monthly_data->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= date('F Y', strtotime($month['month'].'-01')) ?></strong></td>
                            <td><?= $month['auctions_closed'] ?></td>
                            <td><strong><?= number_format($month['monthly_revenue'], 2) ?> Tk</strong></td>
                            <td style="color: <?= $month['monthly_profit'] >= 0 ? '#28a745' : '#dc3545' ?>;">
                                <strong><?= number_format($month['monthly_profit'], 2) ?> Tk</strong>
                            </td>
                            <td><?= number_format($month['monthly_revenue']/$month['auctions_closed'], 2) ?> Tk</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Detailed Auction Results -->
            <div class="table-container fade-in">
                <h3 style="padding: 1rem; background: linear-gradient(135deg, #2c5530, #4a7c59); color: white; margin: 0;">
                    📋 Detailed Auction Results
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>🌱 Product</th>
                            <th>🏷️ Category</th>
                            <th>💰 Starting → Final</th>
                            <th>📈 Profit/Loss</th>
                            <th>🏆 Winner</th>
                            <th>📅 Sold Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($earnings && $earnings->num_rows > 0): ?>
                            <?php while($earning = $earnings->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($earning['product_name']) ?></strong></td>
                                <td><?= htmlspecialchars($earning['category']) ?></td>
                                <td>
                                    <?= number_format($earning['starting_bid'], 2) ?> Tk → 
                                    <strong><?= number_format($earning['current_highest_bid'], 2) ?> Tk</strong>
                                </td>
                                <td>
                                    <span style="color: <?= $earning['profit'] >= 0 ? '#28a745' : '#dc3545' ?>;">
                                        <strong><?= $earning['profit'] >= 0 ? '+' : '' ?><?= number_format($earning['profit'], 2) ?> Tk</strong>
                                        <br><small>(<?= $earning['profit_status'] ?>)</small>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($earning['buyer_name']): ?>
                                        <strong><?= htmlspecialchars($earning['buyer_name']) ?></strong>
                                        <br><small><?= htmlspecialchars($earning['buyer_email']) ?></small>
                                        <br><small>📱 <?= htmlspecialchars($earning['buyer_phone']) ?></small>
                                    <?php else: ?>
                                        <span style="color: #666;">No bids received</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($earning['end_time'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div style="padding: 2rem;">
                                        <h4>No closed auctions yet!</h4>
                                        <p>Your earnings will appear here once you complete some auctions.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="text-center mt-3">
                <a href="farmer_dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
                <a href="add_product.php" class="btn btn-success">➕ Add New Product</a>
            </div>
        </div>
    </div>
    
    <?php renderFooter(); ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>