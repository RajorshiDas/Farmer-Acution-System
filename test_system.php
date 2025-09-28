<?php
// Quick system test to check if everything is working

echo "<h2>🔍 Farmer Auction System - Health Check</h2>";

// Test 1: Database connection
echo "<h3>1. Database Connection Test</h3>";
try {
    include "config.php";
    if ($conn->connect_error) {
        echo "❌ Database connection failed: " . $conn->connect_error . "<br>";
    } else {
        echo "✅ Database connection successful<br>";
        
        // Test if tables exist
        $tables = ['Farmers', 'Buyers', 'Products', 'Auctions', 'Bids'];
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo "✅ Table '$table' exists<br>";
            } else {
                echo "❌ Table '$table' missing<br>";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 2: PHP File Syntax
echo "<h3>2. PHP File Syntax Check</h3>";
$files_to_check = [
    'index.php',
    'config.php', 
    'auth/login.php',
    'auth/register.php',
    'farmer_dashboard.php',
    'add_product.php',
    'auction.php',
    'my_bids.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $output = [];
        exec("php -l $file 2>&1", $output, $return_code);
        if ($return_code === 0) {
            echo "✅ $file - syntax OK<br>";
        } else {
            echo "❌ $file - syntax error: " . implode(' ', $output) . "<br>";
        }
    } else {
        echo "⚠️ $file - file not found<br>";
    }
}

echo "<hr>";

// Test 3: Required directories and files
echo "<h3>3. File Structure Check</h3>";
$required_items = [
    'assets/css/style.css' => 'file',
    'assets/js/main.js' => 'file',
    'includes/navbar.php' => 'file',
    'auth/' => 'dir',
    'assets/' => 'dir',
    'includes/' => 'dir'
];

foreach ($required_items as $item => $type) {
    if ($type === 'dir') {
        if (is_dir($item)) {
            echo "✅ Directory '$item' exists<br>";
        } else {
            echo "❌ Directory '$item' missing<br>";
        }
    } else {
        if (file_exists($item)) {
            echo "✅ File '$item' exists<br>";
        } else {
            echo "❌ File '$item' missing<br>";
        }
    }
}

echo "<hr>";

// Test 4: Sample data check
echo "<h3>4. Sample Data Check</h3>";
if (isset($conn)) {
    $farmers = $conn->query("SELECT COUNT(*) as count FROM Farmers");
    $buyers = $conn->query("SELECT COUNT(*) as count FROM Buyers");
    $products = $conn->query("SELECT COUNT(*) as count FROM Products");
    $auctions = $conn->query("SELECT COUNT(*) as count FROM Auctions");
    
    if ($farmers) {
        $farmer_count = $farmers->fetch_assoc()['count'];
        echo "✅ Farmers in database: $farmer_count<br>";
    }
    if ($buyers) {
        $buyer_count = $buyers->fetch_assoc()['count'];
        echo "✅ Buyers in database: $buyer_count<br>";
    }
    if ($products) {
        $product_count = $products->fetch_assoc()['count'];
        echo "✅ Products in database: $product_count<br>";
    }
    if ($auctions) {
        $auction_count = $auctions->fetch_assoc()['count'];
        echo "✅ Auctions in database: $auction_count<br>";
    }
}

echo "<hr>";

// Test 5: Password verification
echo "<h3>5. Password System Test</h3>";
$test_password = 'password123';
$test_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

if (password_verify($test_password, $test_hash)) {
    echo "✅ Password hashing system working correctly<br>";
} else {
    echo "❌ Password hashing system has issues<br>";
}

echo "<hr>";
echo "<h3>🎯 Quick Actions</h3>";
echo "<p>";
echo "<a href='index.php' style='background:#28a745; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; margin:5px;'>🏠 Go to Main Page</a>";
echo "<a href='auth/login.php' style='background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; margin:5px;'>🔐 Test Login</a>";
echo "<a href='setup_passwords.php' style='background:#ffc107; color:black; padding:10px 20px; text-decoration:none; border-radius:5px; margin:5px;'>🔧 Setup Passwords</a>";
echo "</p>";

echo "<hr>";
echo "<p style='color:#666; font-size:0.9rem;'><strong>Note:</strong> Delete this test file (test_system.php) after confirming everything works.</p>";
?>