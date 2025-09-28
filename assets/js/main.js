/* Additional JavaScript for enhanced functionality */

// Auto-refresh auction data every 30 seconds on auction pages
if (window.location.pathname.includes('auction.php')) {
    setInterval(function() {
        // Only refresh if user hasn't interacted recently
        if (document.hidden === false) {
            location.reload();
        }
    }, 30000);
}

// Enhanced form validation
document.addEventListener('DOMContentLoaded', function() {
    // Real-time bid validation
    const bidInput = document.getElementById('bid_amount');
    if (bidInput) {
        bidInput.addEventListener('input', function() {
            const currentValue = parseFloat(this.value);
            const minBid = parseFloat(this.getAttribute('min'));
            const submitButton = document.querySelector('#bidForm button[type="submit"]');
            
            if (currentValue <= minBid) {
                this.style.borderColor = '#dc3545';
                submitButton.disabled = true;
                submitButton.textContent = 'Bid too low';
            } else {
                this.style.borderColor = '#28a745';
                submitButton.disabled = false;
                submitButton.textContent = '🚀 Place Bid';
            }
        });
    }
    
    // Auto-format currency inputs
    const currencyInputs = document.querySelectorAll('input[type="number"][step="0.01"]');
    currencyInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    });
    
    // Enhanced form submission with loading states
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                const originalText = submitButton.textContent;
                submitButton.textContent = '⏳ Processing...';
                submitButton.disabled = true;
                
                // Re-enable after 5 seconds in case of network issues
                setTimeout(() => {
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
                }, 5000);
            }
        });
    });
});

// Notification system for real-time updates
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} fade-in`;
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 1000;
        min-width: 300px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Enhanced mobile menu functionality
document.addEventListener('click', function(event) {
    const navToggle = document.getElementById('nav-toggle');
    const navMenu = document.getElementById('nav-menu');
    
    if (navToggle && navMenu) {
        if (navToggle.contains(event.target)) {
            navMenu.classList.toggle('active');
        } else if (!navMenu.contains(event.target)) {
            navMenu.classList.remove('active');
        }
    }
});

// Countdown timer for auction end times
function startCountdown(endTime, elementId) {
    const countdownElement = document.getElementById(elementId);
    if (!countdownElement) return;
    
    const endDate = new Date(endTime).getTime();
    
    const timer = setInterval(() => {
        const now = new Date().getTime();
        const timeLeft = endDate - now;
        
        if (timeLeft <= 0) {
            countdownElement.innerHTML = '<span style="color: #dc3545;">⏰ Auction Ended</span>';
            clearInterval(timer);
            return;
        }
        
        const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
        
        let timeString = '';
        if (days > 0) timeString += `${days}d `;
        timeString += `${hours}h ${minutes}m ${seconds}s`;
        
        countdownElement.innerHTML = `⏰ ${timeString} remaining`;
        
        // Change color when time is running out
        if (timeLeft < 3600000) { // Less than 1 hour
            countdownElement.style.color = '#dc3545';
        } else if (timeLeft < 86400000) { // Less than 1 day
            countdownElement.style.color = '#ffc107';
        }
    }, 1000);
}