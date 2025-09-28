<?php
// This script will properly hash passwords and update the database
// Run this ONCE after setting up the database

include "config.php";

echo "<h2>Password Setup for Farmer Auction System</h2>";

// Password to hash (same for all test users)
$plainPassword = "password123";
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

echo "<p><strong>Generated hash for 'password123':</strong><br>";
echo "<code style='background:#f5f5f5; padding:5px;'>" . $hashedPassword . "</code></p>";

// Test the hash immediately
if (password_verify($plainPassword, $hashedPassword)) {
    echo "<p style='color:green;'>✅ Hash verification test: PASSED</p>";
} else {
    echo "<p style='color:red;'>❌ Hash verification test: FAILED</p>";
}

echo "<hr>";

// Update farmer passwords
$farmers = ['john@example.com', 'jane@example.com'];
foreach ($farmers as $email) {
    $stmt = $conn->prepare("UPDATE Farmers SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $email);
    if ($stmt->execute()) {
        echo "✅ Updated password for farmer: <strong>$email</strong><br>";
        
        // Verify the update worked
        $check_stmt = $conn->prepare("SELECT password FROM Farmers WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result()->fetch_assoc();
        
        if ($result && password_verify($plainPassword, $result['password'])) {
            echo "&nbsp;&nbsp;&nbsp;🔑 Password verification: <span style='color:green;'>SUCCESS</span><br>";
        } else {
            echo "&nbsp;&nbsp;&nbsp;❌ Password verification: <span style='color:red;'>FAILED</span><br>";
        }
    } else {
        echo "❌ Failed to update farmer: $email<br>";
    }
}

echo "<br>";

// Update buyer passwords
$buyers = ['alice@example.com', 'bob@example.com'];
foreach ($buyers as $email) {
    $stmt = $conn->prepare("UPDATE Buyers SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $email);
    if ($stmt->execute()) {
        echo "✅ Updated password for buyer: <strong>$email</strong><br>";
        
        // Verify the update worked
        $check_stmt = $conn->prepare("SELECT password FROM Buyers WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result()->fetch_assoc();
        
        if ($result && password_verify($plainPassword, $result['password'])) {
            echo "&nbsp;&nbsp;&nbsp;🔑 Password verification: <span style='color:green;'>SUCCESS</span><br>";
        } else {
            echo "&nbsp;&nbsp;&nbsp;❌ Password verification: <span style='color:red;'>FAILED</span><br>";
        }
    } else {
        echo "❌ Failed to update buyer: $email<br>";
    }
}

echo "<hr>";
echo "<h3>🎉 Password Setup Complete!</h3>";
echo "<p><strong>Test login with any of these accounts using password:</strong> <code style='background:#e7f3ff; padding:5px;'>password123</code></p>";

echo "<div style='background:#f8f9fa; padding:15px; border-left:4px solid #28a745; margin:15px 0;'>";
echo "<h4>Test Accounts:</h4>";
echo "<strong>Farmers:</strong><br>";
echo "• john@example.com / password123<br>";
echo "• jane@example.com / password123<br><br>";
echo "<strong>Buyers:</strong><br>";
echo "• alice@example.com / password123<br>";
echo "• bob@example.com / password123<br>";
echo "</div>";

echo "<p style='margin-top:20px;'>";
echo "<a href='index.php' style='background:#28a745; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>🏠 Go to Main Page</a> ";
echo "<a href='auth/login.php' style='background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>🔐 Test Login</a>";
echo "</p>";

echo "<hr>";
echo "<p style='color:#dc3545;'><strong>Security Note:</strong> Delete this file (setup_passwords.php) after running it once for security.</p>";
?>