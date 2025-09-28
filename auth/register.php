<?php
session_start();
include "../config.php";

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role']; // Farmer or Buyer
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    if ($role == "Farmer") {
        $stmt = $conn->prepare("INSERT INTO Farmers (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $password, $phone, $address);
    } else {
        $stmt = $conn->prepare("INSERT INTO Buyers (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $password, $phone, $address);
    }

    try {
        if ($stmt->execute()) {
            $message = "Registration successful! <a href='login.php' style='color: #28a745; font-weight: 500;'>Login here</a>";
        } else {
            $message = "Registration failed. Please try again.";
        }
    } catch (mysqli_sql_exception $e) {
        // Handle duplicate email error
        if ($e->getCode() == 1062) { // Duplicate entry error code
            $message = "An account with this email already exists. <a href='login.php' style='color: #2c5530; font-weight: 500;'>Login instead</a>";
        } else {
            $message = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Farmer Auction System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include "../includes/navbar.php"; renderNavbar('register'); ?>
    
    <div class="main-content">
        <div class="container">
            <?php renderPageHeader('Create Your Account', 'Join our community of farmers and buyers'); ?>
            
            <div class="form-container fade-in">
                <form method="POST">
                    <?php 
                    if (strpos($message, 'successful') !== false) {
                        renderAlert($message, 'success');
                    } elseif (!empty($message)) {
                        renderAlert($message, 'error');
                    }
                    ?>
                    
                    <div class="form-group">
                        <label for="role">💼 I want to register as</label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="">Choose your role...</option>
                            <option value="Buyer" <?= (isset($_POST['role']) && $_POST['role'] == 'Buyer') ? 'selected' : '' ?>>🛍️ Buyer - I want to buy products</option>
                            <option value="Farmer" <?= (isset($_POST['role']) && $_POST['role'] == 'Farmer') ? 'selected' : '' ?>>🌾 Farmer - I want to sell products</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">👤 Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" 
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                               placeholder="Enter your full name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">✉️ Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                               placeholder="Enter your email address" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">📱 Phone Number</label>
                        <input type="tel" name="phone" id="phone" class="form-control" 
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" 
                               placeholder="Enter your phone number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">🏠 Address</label>
                        <textarea name="address" id="address" class="form-control" rows="3" 
                                  placeholder="Enter your full address" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">🔒 Password</label>
                        <input type="password" name="password" id="password" class="form-control" 
                               placeholder="Create a secure password" required minlength="6">
                        <small style="color: #666; font-size: 0.85rem;">Password must be at least 6 characters long</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">🎉 Create Account</button>
                </form>
                
                <div class="text-center mt-3">
                    <p>Already have an account? <a href="login.php" style="color: #2c5530; font-weight: 500;">Login here</a></p>
                    
                    <hr style="margin: 1.5rem 0;">
                    
                    <div style="background: #e7f3ff; padding: 1rem; border-radius: 5px; text-align: left;">
                        <h4 style="color: #0c5460; margin-bottom: 0.5rem;">🧪 Test Accounts Available</h4>
                        <p style="font-size: 0.9rem; color: #0c5460; margin-bottom: 0.5rem;">
                            <strong>Don't need to register?</strong> Use these existing test accounts:
                        </p>
                        <p style="font-size: 0.85rem; color: #666;">
                            <strong>Farmers:</strong> john@example.com, jane@example.com<br>
                            <strong>Buyers:</strong> alice@example.com, bob@example.com<br>
                            <strong>Password for all:</strong> password123
                        </p>
                        <p style="font-size: 0.8rem; color: #856404;">
                            💡 If you want to create a new account, use a different email address.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php renderFooter(); ?>
</body>
</html>
