<?php
session_start();
include "../config.php";

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'];
    $email = $_POST['email'];
    $password = $_POST['password'];

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
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $role;
            $_SESSION['name'] = $row['name'];
            header("Location: ../index.php");
            exit;
        } else {
            $message = "Invalid email or password.";
        }
    } else {
        $message = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Farmer Auction System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include "../includes/navbar.php"; renderNavbar('login'); ?>
    
    <div class="main-content">
        <div class="container">
            <?php renderPageHeader('Login to Your Account', 'Access your farmer or buyer dashboard'); ?>
            
            <div class="form-container fade-in">
                <form method="POST">
                    <?php renderAlert($message, !empty($message) && strpos($message, 'Invalid') !== false ? 'error' : 'info'); ?>
                    
                    <div class="form-group">
                        <label for="role">💼 Select Your Role</label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="">Choose your role...</option>
                            <option value="Buyer" <?= (isset($_POST['role']) && $_POST['role'] == 'Buyer') ? 'selected' : '' ?>>🛍️ Buyer</option>
                            <option value="Farmer" <?= (isset($_POST['role']) && $_POST['role'] == 'Farmer') ? 'selected' : '' ?>>🌾 Farmer</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">✉️ Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                               placeholder="Enter your email address" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">🔒 Password</label>
                        <input type="password" name="password" id="password" class="form-control" 
                               placeholder="Enter your password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">🚀 Login</button>
                </form>
                
                <div class="text-center mt-3">
                    <p>Don't have an account? <a href="register.php" style="color: #2c5530; font-weight: 500;">Register here</a></p>
                    <hr style="margin: 1rem 0;">
                    <p><strong>Test Accounts:</strong></p>
                    <p style="font-size: 0.9rem; color: #666;">
                        <strong>Buyer:</strong> alice@example.com | <strong>Farmer:</strong> john@example.com<br>
                        <strong>Password:</strong> password123
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <?php renderFooter(); ?>
</body>
</html>