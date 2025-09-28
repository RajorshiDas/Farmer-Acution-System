<?php
session_start();
include "config.php";

$auction_id = $_GET['id'] ?? 0;

// Fetch auction info with prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT a.*, p.product_name, p.description, p.starting_bid, p.category,
                         f.name as farmer_name, f.email as farmer_email, f.phone as farmer_phone, f.address as farmer_address
                         FROM Auctions a 
                         JOIN Products p ON a.product_id = p.product_id
                         JOIN Farmers f ON p.farmer_id = f.farmer_id
                         WHERE a.auction_id = ?");
$stmt->bind_param("i", $auction_id);
$stmt->execute();
$auction = $stmt->get_result()->fetch_assoc();

if (!$auction) {
    die("Auction not found.");
}

// Handle new bid
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id']) && $_SESSION['role'] == 'Buyer') {
    $buyer_id = $_SESSION['user_id'];
    $bid_amount = floatval($_POST['bid_amount']);

    // Validate bid amount
    if ($bid_amount <= $auction['current_highest_bid']) {
        echo "<p style='color: red;'>Bid must be higher than current highest bid!</p>";
    } elseif ($auction['status'] !== 'Active') {
        echo "<p style='color: red;'>This auction is no longer active!</p>";
    } else {
        // Insert bid
        $stmt = $conn->prepare("INSERT INTO Bids (auction_id, buyer_id, bid_amount) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $auction_id, $buyer_id, $bid_amount);
        
        if ($stmt->execute()) {
            // Update current highest bid
            $update_stmt = $conn->prepare("UPDATE Auctions SET current_highest_bid = ? WHERE auction_id = ?");
            $update_stmt->bind_param("di", $bid_amount, $auction_id);
            $update_stmt->execute();
            
            echo "<p style='color: green;'>Bid placed successfully!</p>";
            // Refresh auction data
            $stmt = $conn->prepare("SELECT a.*, p.product_name, p.description, p.starting_bid 
                                     FROM Auctions a 
                                     JOIN Products p ON a.product_id = p.product_id
                                     WHERE a.auction_id = ?");
            $stmt->bind_param("i", $auction_id);
            $stmt->execute();
            $auction = $stmt->get_result()->fetch_assoc();
        } else {
            echo "<p style='color: red;'>Error placing bid. Please try again.</p>";
        }
    }
}

// Get all bids
$bids_stmt = $conn->prepare("SELECT b.bid_amount, b.bid_time, u.name 
                      FROM Bids b 
                      JOIN Buyers u ON b.buyer_id = u.buyer_id
                      WHERE b.auction_id = ? 
                      ORDER BY b.bid_amount DESC");
$bids_stmt->bind_param("i", $auction_id);
$bids_stmt->execute();
$bids = $bids_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($auction['product_name']) ?> - Farmer Auction System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include "includes/navbar.php"; renderNavbar(); ?>
    
    <div class="main-content">
        <div class="container">
            <?php renderPageHeader($auction['product_name'], $auction['description']); ?>
            
            <div class="auction-grid">
                <!-- Auction Details Card -->
                <div class="card fade-in">
                    <h3>📊 Auction Details</h3>
                    <div style="margin: 1rem 0;">
                        <p><strong>🏷️ Category:</strong> <?= htmlspecialchars($auction['category']) ?></p>
                        <p><strong>Status:</strong> <?= renderStatusBadge($auction['status']) ?></p>
                        <p><strong>💵 Starting Bid:</strong> <?= number_format($auction['starting_bid'], 2) ?> Tk</p>
                        <p><strong>🏆 Current Highest:</strong> <span class="current-bid"><?= number_format($auction['current_highest_bid'], 2) ?> Tk</span></p>
                        <p><strong>⏰ Started:</strong> <?= date('M d, Y H:i', strtotime($auction['start_time'])) ?></p>
                        <p><strong>⌛ Ends:</strong> <?= date('M d, Y H:i', strtotime($auction['end_time'])) ?></p>
                    </div>
                </div>
                
                <!-- Farmer Information Card -->
                <div class="card fade-in">
                    <h3>👨‍🌾 Farmer Information</h3>
                    <div style="margin: 1rem 0; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                        <p><strong>📝 Name:</strong> <?= htmlspecialchars($auction['farmer_name']) ?></p>
                        <p><strong>📧 Email:</strong> <?= htmlspecialchars($auction['farmer_email']) ?></p>
                        <p><strong>📱 Phone:</strong> <?= htmlspecialchars($auction['farmer_phone']) ?></p>
                        <p><strong>🏠 Address:</strong> <?= htmlspecialchars($auction['farmer_address']) ?></p>
                    </div>
                    <div class="alert alert-info" style="margin-top: 1rem;">
                        <strong>💡 Note:</strong> Contact the farmer directly for product quality questions or delivery arrangements after winning the auction.
                    </div>
                </div>
            </div>
            
            <div class="auction-grid" style="margin-top: 2rem;">')
                <!-- Bidding Section -->
                <div class="card fade-in">
                    <h3>💰 Place Your Bid</h3>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Buyer'): ?>
                        <?php if ($auction['status'] == 'Active'): ?>
                        <div class="bid-section">
                            <form method="POST" id="bidForm">
                                <div class="form-group">
                                    <label for="bid_amount">Your Bid Amount (Tk)</label>
                                    <input type="number" name="bid_amount" id="bid_amount" class="form-control" 
                                           step="0.01" min="<?= $auction['current_highest_bid'] + 0.01 ?>" 
                                           placeholder="Enter amount higher than <?= number_format($auction['current_highest_bid'], 2) ?>" required>
                                    <small style="color: #666;">Minimum bid: <?= number_format($auction['current_highest_bid'] + 0.01, 2) ?> Tk</small>
                                </div>
                                <button type="submit" class="btn btn-success" style="width: 100%;">🚀 Place Bid</button>
                            </form>
                            <p style="margin-top: 1rem; color: #666; text-align: center;">
                                Logged in as: <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>
                            </p>
                        </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                🚫 This auction is <?= $auction['status'] ?>. No more bids can be placed.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            🔐 You must <a href="auth/login.php" style="color: #2c5530; font-weight: 500;">login as a Buyer</a> to place a bid.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Bidding History -->
            <div class="table-container fade-in" style="margin-top: 2rem;">
                <h3 style="padding: 1rem; background: linear-gradient(135deg, #2c5530, #4a7c59); color: white; margin: 0;">
                    📈 Bidding History
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>👤 Buyer</th>
                            <th>💰 Bid Amount</th>
                            <th>⏰ Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bids && $bids->num_rows > 0): ?>
                            <?php while($b = $bids->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($b['name']) ?></td>
                                <td><strong><?= number_format($b['bid_amount'], 2) ?> Tk</strong></td>
                                <td><?= date('M d, Y H:i', strtotime($b['bid_time'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">No bids placed yet. Be the first to bid!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="text-center mt-3">
                <a href="index.php" class="btn btn-secondary">← Back to Auctions</a>
            </div>
        </div>
    </div>
    
    <?php renderFooter(); ?>
    
    <script>
        // Form validation
        document.getElementById('bidForm')?.addEventListener('submit', function(e) {
            const bidAmount = parseFloat(document.getElementById('bid_amount').value);
            const minBid = <?= $auction['current_highest_bid'] + 0.01 ?>;
            
            if (bidAmount <= minBid - 0.01) {
                e.preventDefault();
                alert('Your bid must be higher than the current highest bid!');
                return false;
            }
            
            // Add loading state
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '🔄 Placing Bid...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
