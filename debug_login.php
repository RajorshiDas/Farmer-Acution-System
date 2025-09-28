<?php
// Debug script to check password issues
// Delete this file after fixing the issue for security

include "config.php";

echo "<h2>🔍 Login Debug Tool</h2>";

if ($_POST) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    echo "<h3>Testing login for: $email as $role</h3>";
    
    // Check if user exists
    if ($role == "Farmer") {
        $sql = "SELECT farmer_id AS id, name, password FROM Farmers WHERE email = ?";
    } else {
        $sql = "SELECT buyer_id AS id, name, password FROM Buyers WHERE email = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "✅ User found in database<br>";
        echo "<strong>User ID:</strong> " . $row['id'] . "<br>";
        echo "<strong>Name:</strong> " . $row['name'] . "<br>";
        echo "<strong>Stored Hash:</strong> <code style='background:#f5f5f5; padding:5px; word-break:break-all;'>" . $row['password'] . "</code><br>";
        
        // Test password verification
        if (password_verify($password, $row['password'])) {
            echo "<p style='color:green; font-size:18px;'>🎉 PASSWORD VERIFICATION: SUCCESS!</p>";
            echo "<p>The login should work. If it's still failing, check for typos in the login form.</p>";
        } else {
            echo "<p style='color:red; font-size:18px;'>❌ PASSWORD VERIFICATION: FAILED!</p>";
            echo "<p>The password hash in database doesn't match. Run setup_passwords.php to fix it.</p>";
            
            // Show what the correct hash should be
            $correctHash = password_hash($password, PASSWORD_DEFAULT);
            echo "<p><strong>Correct hash for '$password' would be:</strong><br>";
            echo "<code style='background:#e7f3ff; padding:5px; word-break:break-all;'>" . $correctHash . "</code></p>";
        }
    } else {
        echo "<p style='color:red;'>❌ User not found in database!</p>";
        echo "<p>Check if the email exists in the " . ($role == "Farmer" ? "Farmers" : "Buyers") . " table.</p>";
    }
    
    echo "<hr>";
}
?>

<form method="POST" style="background:#f8f9fa; padding:20px; border-radius:5px; max-width:400px;">
    <h3>Test Login Credentials</h3>
    
    <p>
        <label>Role:</label><br>
        <select name="role" required style="width:100%; padding:8px;">
            <option value="Buyer">Buyer</option>
            <option value="Farmer">Farmer</option>
        </select>
    </p>
    
    <p>
        <label>Email:</label><br>
        <input type="email" name="email" value="john@example.com" required style="width:100%; padding:8px;">
    </p>
    
    <p>
        <label>Password:</label><br>
        <input type="text" name="password" value="password123" required style="width:100%; padding:8px;">
    </p>
    
    <button type="submit" style="background:#007bff; color:white; padding:10px 20px; border:none; border-radius:3px;">
        🔍 Test Login
    </button>
</form>

<div style="background:#fff3cd; padding:15px; border-left:4px solid #ffc107; margin:20px 0;">
    <h4>Quick Fix Steps:</h4>
    <ol>
        <li>Run <a href="setup_passwords.php">setup_passwords.php</a> to generate proper password hashes</li>
        <li>Try logging in with: <strong>john@example.com</strong> / <strong>password123</strong> as Farmer</li>
        <li>Or try: <strong>alice@example.com</strong> / <strong>password123</strong> as Buyer</li>
        <li>Delete this debug file after fixing the issue</li>
    </ol>
</div>

<p>
    <a href="setup_passwords.php" style="background:#28a745; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;">🔧 Run Password Setup</a>
    <a href="auth/login.php" style="background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;">🔐 Try Login Page</a>
</p>