<?php
function renderNavbar($current_page = '') {
    $user_logged_in = isset($_SESSION['user_id']);
    $user_name = $_SESSION['name'] ?? '';
    $user_role = $_SESSION['role'] ?? '';
    ?>
    <nav class="navbar">
        <div class="nav-container">
            <a href="<?= $user_logged_in ? '/farmer_auction_system/index.php' : '/farmer_auction_system/index.php' ?>" class="nav-brand">
                🌾 Farmer Auction System
            </a>
            
            <div class="nav-toggle" id="nav-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <ul class="nav-menu" id="nav-menu">
                <li class="nav-item">
                    <a href="/farmer_auction_system/index.php" class="<?= $current_page === 'home' ? 'active' : '' ?>">
                        🏠 Home
                    </a>
                </li>
                
                <?php if ($user_logged_in): ?>
                    <?php if ($user_role === 'Farmer'): ?>
                        <li class="nav-item">
                            <a href="/farmer_auction_system/farmer_dashboard.php" class="<?= $current_page === 'farmer_dashboard' ? 'active' : '' ?>">
                                📊 My Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/farmer_auction_system/add_product.php" class="<?= $current_page === 'add_product' ? 'active' : '' ?>">
                                ➕ Add Product
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/farmer_auction_system/farmer_earnings.php" class="<?= $current_page === 'earnings' ? 'active' : '' ?>">
                                💰 My Earnings
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($user_role === 'Buyer'): ?>
                        <li class="nav-item">
                            <a href="/farmer_auction_system/my_bids.php" class="<?= $current_page === 'my_bids' ? 'active' : '' ?>">
                                💰 My Bids
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a href="/farmer_auction_system/profile.php" class="<?= $current_page === 'profile' ? 'active' : '' ?>">
                            ⚙️ Profile
                        </a>
                    </li>
                    
                    <li class="nav-item nav-user-info">
                        👤 <?= htmlspecialchars($user_name) ?> (<?= $user_role ?>)
                    </li>
                    <li class="nav-item">
                        <a href="/farmer_auction_system/auth/logout.php" class="btn btn-secondary btn-small">
                            🚪 Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="/farmer_auction_system/auth/login.php" class="<?= $current_page === 'login' ? 'active' : '' ?>">
                            🔐 Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/farmer_auction_system/auth/register.php" class="btn btn-primary btn-small">
                            📝 Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <script>
        // Mobile navigation toggle
        document.getElementById('nav-toggle').addEventListener('click', function() {
            document.getElementById('nav-menu').classList.toggle('active');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const navToggle = document.getElementById('nav-toggle');
            const navMenu = document.getElementById('nav-menu');
            
            if (!navToggle.contains(event.target) && !navMenu.contains(event.target)) {
                navMenu.classList.remove('active');
            }
        });
    </script>
    <?php
}

function renderPageHeader($title, $description = '') {
    ?>
    <div class="page-header fade-in">
        <div class="container">
            <h1><?= htmlspecialchars($title) ?></h1>
            <?php if ($description): ?>
                <p><?= htmlspecialchars($description) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function renderAlert($message, $type = 'info') {
    if (!empty($message)) {
        ?>
        <div class="alert alert-<?= $type ?> fade-in">
            <?= $message ?>
        </div>
        <?php
    }
}

function renderStatusBadge($status) {
    $class = '';
    $icon = '';
    
    switch(strtolower($status)) {
        case 'active':
            $class = 'status-active';
            $icon = '🟢';
            break;
        case 'closed':
            $class = 'status-closed';
            $icon = '🔴';
            break;
        case 'pending':
            $class = 'status-pending';
            $icon = '🟡';
            break;
        default:
            $class = 'status-pending';
            $icon = '⚪';
    }
    
    return "<span class='status-badge {$class}'>{$icon} {$status}</span>";
}

function renderFooter() {
    ?>
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Farmer Auction System. Connecting farmers with buyers through fair auctions.</p>
        </div>
    </footer>
    <?php
}
?>