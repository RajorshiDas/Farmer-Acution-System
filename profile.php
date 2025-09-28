<?php
session_start();
include "config.php";
include "includes/navbar.php";

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$message = "";
$message_type = "info";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($phone) || empty($address)) {
        $message = "All fields except password are required.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } else {
        // Check if email already exists (but not for current user)
        if ($user_role == "Farmer") {
            $check_email = $conn->prepare("SELECT farmer_id FROM Farmers WHERE email = ? AND farmer_id != ?");
        } else {
            $check_email = $conn->prepare("SELECT buyer_id FROM Buyers WHERE email = ? AND buyer_id != ?");
        }
        $check_email->bind_param("si", $email, $user_id);
        $check_email->execute();
        $email_exists = $check_email->get_result()->num_rows > 0;
        
        if ($email_exists) {
            $message = "This email is already registered by another user.";
            $message_type = "error";
        } else {
            // Handle password change if provided
            $update_password = false;
            $hashed_password = "";
            
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $message = "Current password is required to change password.";
                    $message_type = "error";
                } elseif ($new_password !== $confirm_password) {
                    $message = "New passwords do not match.";
                    $message_type = "error";
                } elseif (strlen($new_password) < 6) {
                    $message = "New password must be at least 6 characters long.";
                    $message_type = "error";
                } else {
                    // Verify current password
                    if ($user_role == "Farmer") {
                        $verify_stmt = $conn->prepare("SELECT password FROM Farmers WHERE farmer_id = ?");
                    } else {
                        $verify_stmt = $conn->prepare("SELECT password FROM Buyers WHERE buyer_id = ?");
                    }
                    $verify_stmt->bind_param("i", $user_id);
                    $verify_stmt->execute();
                    $current_user = $verify_stmt->get_result()->fetch_assoc();
                    
                    if (!password_verify($current_password, $current_user['password'])) {
                        $message = "Current password is incorrect.";
                        $message_type = "error";
                    } else {
                        $update_password = true;
                        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                    }
                }
            }
            
            // Update user information if no errors
            if (empty($message)) {
                try {
                    if ($user_role == "Farmer") {
                        if ($update_password) {
                            $stmt = $conn->prepare("UPDATE Farmers SET name = ?, email = ?, phone = ?, address = ?, password = ? WHERE farmer_id = ?");
                            $stmt->bind_param("sssssi", $name, $email, $phone, $address, $hashed_password, $user_id);
                        } else {
                            $stmt = $conn->prepare("UPDATE Farmers SET name = ?, email = ?, phone = ?, address = ? WHERE farmer_id = ?");
                            $stmt->bind_param("ssssi", $name, $email, $phone, $address, $user_id);
                        }
                    } else {
                        if ($update_password) {
                            $stmt = $conn->prepare("UPDATE Buyers SET name = ?, email = ?, phone = ?, address = ?, password = ? WHERE buyer_id = ?");
                            $stmt->bind_param("sssssi", $name, $email, $phone, $address, $hashed_password, $user_id);
                        } else {
                            $stmt = $conn->prepare("UPDATE Buyers SET name = ?, email = ?, phone = ?, address = ? WHERE buyer_id = ?");
                            $stmt->bind_param("ssssi", $name, $email, $phone, $address, $user_id);
                        }
                    }
                    
                    if ($stmt->execute()) {
                        $_SESSION['name'] = $name; // Update session name
                        $message = "Profile updated successfully!" . ($update_password ? " Password has been changed." : "");
                        $message_type = "success";
                    } else {
                        $message = "Failed to update profile. Please try again.";
                        $message_type = "error";
                    }
                } catch (mysqli_sql_exception $e) {
                    $message = "Database error: " . $e->getMessage();
                    $message_type = "error";
                }
            }
        }
    }
}

// Fetch current user information
if ($user_role == "Farmer") {
    $stmt = $conn->prepare("SELECT name, email, phone, address, created_at FROM Farmers WHERE farmer_id = ?");
} else {
    $stmt = $conn->prepare("SELECT name, email, phone, address, created_at FROM Buyers WHERE buyer_id = ?");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();

if (!$user_info) {
    die("User not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Farmer Auction System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php renderNavbar('profile'); ?>
    
    <div class="main-content">
        <div class="container">
            <?php renderPageHeader('My Profile Settings', 'Update your personal information and password'); ?>
            
            <div class="auction-grid fade-in">
                <!-- Profile Information Card -->
                <div class="card">
                    <h3>👤 Account Information</h3>
                    <div style="margin: 1rem 0; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                        <p><strong>📝 Account Type:</strong> <?= $user_role ?></p>
                        <p><strong>🆔 User ID:</strong> #<?= $user_id ?></p>
                        <p><strong>📅 Member Since:</strong> <?= date('F j, Y', strtotime($user_info['created_at'])) ?></p>
                    </div>
                </div>
                
                <!-- Update Form -->
                <div class="card">
                    <h3>✏️ Update Information</h3>
                    <form method="POST" style="margin-top: 1rem;">
                        <?php renderAlert($message, $message_type); ?>
                        
                        <div class="form-group">
                            <label for="name">👤 Full Name *</label>
                            <input type="text" name="name" id="name" class="form-control" 
                                   value="<?= htmlspecialchars($user_info['name']) ?>" 
                                   placeholder="Enter your full name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">📧 Email Address *</label>
                            <input type="email" name="email" id="email" class="form-control" 
                                   value="<?= htmlspecialchars($user_info['email']) ?>" 
                                   placeholder="Enter your email address" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">📱 Phone Number *</label>
                            <input type="text" name="phone" id="phone" class="form-control" 
                                   value="<?= htmlspecialchars($user_info['phone']) ?>" 
                                   placeholder="Enter your phone number" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">🏠 Address *</label>
                            <textarea name="address" id="address" class="form-control" rows="3" 
                                      placeholder="Enter your complete address" required><?= htmlspecialchars($user_info['address']) ?></textarea>
                        </div>
                        
                        <hr style="margin: 2rem 0;">
                        <h4>🔒 Change Password (Optional)</h4>
                        <p style="color: #666; font-size: 0.9rem;">Leave password fields empty if you don't want to change your password.</p>
                        
                        <div class="form-group">
                            <label for="current_password">🔐 Current Password</label>
                            <input type="password" name="current_password" id="current_password" class="form-control" 
                                   placeholder="Enter your current password">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="new_password">🆕 New Password</label>
                                <input type="password" name="new_password" id="new_password" class="form-control" 
                                       placeholder="Enter new password" minlength="6">
                                <small style="color: #666;">Minimum 6 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">✅ Confirm New Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                                       placeholder="Confirm new password">
                            </div>
                        </div>
                        
                        <div style="margin-top: 2rem;">
                            <button type="submit" class="btn btn-success" style="width: 100%;">
                                💾 Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <?php if ($user_role == "Farmer"): ?>
                    <a href="farmer_dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
                <?php else: ?>
                    <a href="my_bids.php" class="btn btn-primary">← Back to My Bids</a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">🏠 Home</a>
            </div>
        </div>
    </div>
    
    <?php renderFooter(); ?>
    
    <script>
        // Real-time password validation
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const submitBtn = document.querySelector('button[type="submit"]');
            
            if (password.length > 0 && password.length < 6) {
                this.style.borderColor = '#dc3545';
                submitBtn.disabled = true;
            } else {
                this.style.borderColor = password.length >= 6 ? '#28a745' : '';
                submitBtn.disabled = false;
            }
        });
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword.length > 0 && newPassword !== confirmPassword) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = newPassword === confirmPassword && confirmPassword.length > 0 ? '#28a745' : '';
            }
        });
        
        // Form submission validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;
            
            if (newPassword && !currentPassword) {
                alert('Please enter your current password to change your password.');
                e.preventDefault();
                return;
            }
            
            if (newPassword !== confirmPassword) {
                alert('New passwords do not match.');
                e.preventDefault();
                return;
            }
            
            if (newPassword && newPassword.length < 6) {
                alert('New password must be at least 6 characters long.');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>