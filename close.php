<?php
session_start();
include "config.php";
include "includes/navbar.php";

$id = $_GET['id'] ?? 0;
$success = false;
$message = "";

if ($id > 0) {
    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("UPDATE Auctions SET status='Closed' WHERE auction_id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success = true;
        $message = "Auction has been successfully closed! The system has automatically selected the winner and created payment records.";
    } else {
        $message = "Error closing auction. Please try again.";
    }
} else {
    $message = "Invalid auction ID.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction Closed - Farmer Auction System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php renderNavbar(); ?>
    
    <div class="main-content">
        <div class="container">
            <?php renderPageHeader('Auction Management', 'Close auction and finalize results'); ?>
            
            <div class="card fade-in text-center">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h3>✅ Success!</h3>
                        <p><?= $message ?></p>
                    </div>
                    
                    <div style="margin: 2rem 0;">
                        <a href="bids.php?id=<?= $id ?>" class="btn btn-primary">📊 View Auction Results</a>
                        <a href="index.php" class="btn btn-secondary">← Back to Auctions</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <h3>❌ Error!</h3>
                        <p><?= $message ?></p>
                    </div>
                    
                    <div style="margin: 2rem 0;">
                        <a href="index.php" class="btn btn-primary">← Back to Auctions</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php renderFooter(); ?>
</body>
</html>
