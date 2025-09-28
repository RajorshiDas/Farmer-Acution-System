# Farmer Auction System - Setup Instructions

## Prerequisites
1. **XAMPP, WAMP, or LAMP** - Local web server with Apache, MySQL, and PHP
2. **PHP 7.4 or higher**
3. **MySQL 5.7 or higher**

## Step-by-Step Setup

### 1. Install XAMPP (Recommended for Windows)
- Download XAMPP from: https://www.apachefriends.org/
- Install XAMPP in `C:\xampp\`
- Start Apache and MySQL from XAMPP Control Panel

### 2. Setup Database
1. Open your web browser and go to: `http://localhost/phpmyadmin`
2. Click "Import" tab
3. Choose the `database_schema.sql` file from your project folder
4. Click "Go" to execute the SQL script
   - This will create the database and all required tables
   - Sample data will be inserted for testing

### 3. Configure the Application
1. Move your project folder to: `C:\xampp\htdocs\farmer_auction_system`
2. Verify database connection in `config.php`:
   ```php
   $host = "localhost";
   $user = "root";
   $pass = "";  // Leave empty for default XAMPP
   $db   = "farmer_auction_system";
   ```

### 4. Run the Application
1. Open your web browser
2. Navigate to: `http://localhost/farmer_auction_system/`
3. You should see the auction listings page

## How to Test the Application

### Test User Accounts (Use these for login)
**Farmers:**
- Email: `john@example.com`, Password: `password123`
- Email: `jane@example.com`, Password: `password123`

**Buyers:**
- Email: `alice@example.com`, Password: `password123`
- Email: `bob@example.com`, Password: `password123`

### Testing Workflow:
1. **Register New Users**: Go to Register page and create new accounts
2. **Login**: Use existing test accounts or newly created ones
3. **View Auctions**: Browse active auctions on the home page
4. **Place Bids**: Login as a Buyer and place bids on auctions
5. **Close Auctions**: Use `close.php?id=[auction_id]` to close auctions
6. **View Results**: Check `bids.php?id=[auction_id]` for winners and payments

## Key Features Fixed

### ✅ Security Improvements
- **SQL Injection Prevention**: All queries use prepared statements
- **Password Security**: Passwords are hashed using PHP's `password_hash()`
- **Input Validation**: Proper validation for bid amounts and user inputs
- **XSS Protection**: All user outputs are properly escaped with `htmlspecialchars()`

### ✅ Session Management
- **Proper Session Handling**: All pages now start sessions correctly
- **User Authentication**: Login/logout functionality works properly
- **Role-based Access**: Buyers can bid, different permissions for different roles

### ✅ Modern User Interface
- **Responsive Design**: Mobile-friendly interface that works on all devices
- **Modern CSS Framework**: Clean, professional design with animations
- **Navigation Bar**: Easy access to all sections with user status display
- **Enhanced Forms**: Better form styling with real-time validation
- **Status Indicators**: Visual badges for auction status and payment status
- **Interactive Elements**: Hover effects, loading states, and smooth transitions

### ✅ Database Structure
- **Complete Schema**: All required tables with proper relationships
- **Foreign Keys**: Proper database constraints and relationships
- **Triggers**: Automatic winner selection and payment creation when auctions close
- **Sample Data**: Pre-populated data for testing

### ✅ Business Logic
- **Bid Validation**: Ensures bids are higher than current highest bid
- **Auction Status**: Only active auctions accept new bids
- **Winner Selection**: Automatic selection of highest bidder when auction closes
- **Payment Creation**: Automatic payment record creation

### ✅ Enhanced User Experience
- **Real-time Validation**: Form inputs are validated as you type
- **Loading States**: Visual feedback during form submissions
- **Mobile Navigation**: Hamburger menu for mobile devices
- **Accessibility**: Proper labels, semantic HTML, and keyboard navigation
- **Visual Feedback**: Success/error messages with proper styling

## File Structure
```
farmer_auction_system/
├── index.php          # Main auction listings page
├── auction.php        # Individual auction page with bidding
├── bids.php          # Auction results and payment info
├── close.php         # Close auction functionality
├── config.php        # Database configuration
├── database_schema.sql # Database creation script
├── setup_passwords.php # Password setup script
├── README.md         # Setup and usage instructions
├── assets/
│   ├── css/
│   │   └── style.css # Modern CSS stylesheet
│   └── js/
│       └── main.js   # Enhanced JavaScript functionality
├── includes/
│   └── navbar.php    # Navigation bar component
└── auth/
    ├── login.php     # User login
    ├── register.php  # User registration
    └── logout.php    # User logout
```

## Troubleshooting

### Common Issues:
1. **Database Connection Error**:
   - Ensure MySQL is running in XAMPP
   - Check if database exists in phpMyAdmin
   - Verify config.php settings

2. **Page Not Found (404)**:
   - Ensure project is in `htdocs` folder
   - Check URL spelling and case sensitivity

3. **Session Issues**:
   - Clear browser cookies/cache
   - Restart Apache server

4. **Permission Errors**:
   - Ensure proper file permissions on the project folder
   - On Linux/Mac: `chmod -R 755 farmer_auction_system`

## Production Deployment Notes
- Change database credentials in `config.php`
- Use environment variables for sensitive data
- Enable HTTPS for production
- Set proper error reporting levels
- Add additional input validation as needed

## Sample URLs for Testing
- Home: `http://localhost/farmer_auction_system/`
- Login: `http://localhost/farmer_auction_system/auth/login.php`
- Register: `http://localhost/farmer_auction_system/auth/register.php`
- View Auction: `http://localhost/farmer_auction_system/auction.php?id=1`
- Close Auction: `http://localhost/farmer_auction_system/close.php?id=1`
- View Results: `http://localhost/farmer_auction_system/bids.php?id=1`