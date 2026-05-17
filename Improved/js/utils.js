// Utility functions for all pages

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = 'toast';
    const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
    toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatPrice(price, currency = 'BDT') {
    if (currency === 'BDT') return `৳${price.toLocaleString()}`;
    return `$${price.toLocaleString()}`;
}

// Authentication check functions
function requireLogin(actionName, redirectToLogin = true) {
    const user = getCurrentUser();
    if (!user) {
        showToast(`⚠️ Please login to ${actionName}`, 'error');
        if (redirectToLogin) {
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 1500);
        }
        return false;
    }
    return true;
}

function isLoggedInAndRedirect() {
    const user = getCurrentUser();
    if (!user) {
        showToast('⚠️ Please login to continue', 'error');
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 1500);
        return false;
    }
    return true;
}

// Carousel functions
let currentSlide = 0;
let slides = [];

function initCarousel() {
    slides = document.querySelectorAll('.carousel-slide');
    if (slides.length > 0) {
        setInterval(() => changeSlide(1), 5000);
    }
}

function changeSlide(direction) {
    if (!slides.length) return;
    slides[currentSlide].classList.remove('active');
    currentSlide = (currentSlide + direction + slides.length) % slides.length;
    slides[currentSlide].classList.add('active');
}

// Chat functions
function toggleChat() {
    const chatWindow = document.getElementById('chatWindow');
    if (chatWindow) chatWindow.classList.toggle('open');
}

function sendChatMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    if (!message) return;

    const chatMessages = document.getElementById('chatMessages');
    chatMessages.innerHTML += `<div class="message user-message">${escapeHtml(message)}</div>`;
    input.value = '';
    chatMessages.scrollTop = chatMessages.scrollHeight;

    setTimeout(() => {
        let response = getChatResponse(message.toLowerCase());
        chatMessages.innerHTML += `<div class="message bot-message">${response}</div>`;
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }, 500);
}

function getChatResponse(message) {
    if (message.includes('help')) {
        return `📋 Here's what I can help with:<br><br>
        • Track order - Send your order ID<br>
        • Discount code - Get current offers<br>
        • Product recommendations - Tell me what you like<br>
        • Loyalty points - Check your balance<br>
        • Shipping info - Delivery times<br><br>
        What would you like to know?`;
    } else if (message.includes('track') || message.includes('order')) {
        return `🔍 Go to our Order Tracking page or send your order ID starting with "ORD".`;
    } else if (message.includes('discount') || message.includes('coupon')) {
        return `🎉 Current offers:<br><br>
        • Use code <strong>WELCOME10</strong> for 10% off first order<br>
        • Use code <strong>RAMADAN20</strong> for 20% off orders over ৳2000<br>
        • Free shipping on orders over ৳2000`;
    } else if (message.includes('point') || message.includes('loyalty')) {
        const points = localStorage.getItem('loyalty_points') || 0;
        return `⭐ You have <strong>${points}</strong> loyalty points!<br><br>
        100 points = ৳10 discount | Earn 1 point per ৳100 spent`;
    } else {
        return `Thank you for your message! 🙏 Type 'help' to see what I can assist you with!`;
    }
}

// Search and newsletter
function searchProducts() {
    const query = document.getElementById('searchInput')?.value.toLowerCase();
    if (query) {
        localStorage.setItem('searchQuery', query);
        window.location.href = 'shop.html';
    }
}

function subscribeNewsletter() {
    const email = document.getElementById('newsletterEmail')?.value;
    if (email && email.includes('@')) {
        let subscribers = JSON.parse(localStorage.getItem('newsletter_subscribers')) || [];
        if (!subscribers.includes(email)) {
            subscribers.push(email);
            localStorage.setItem('newsletter_subscribers', JSON.stringify(subscribers));
            showToast('Subscribed! Check your email for 15% off coupon.');
            document.getElementById('newsletterEmail').value = '';
        } else {
            showToast('Already subscribed!');
        }
    } else {
        showToast('Please enter a valid email');
    }
}

function showReferralModal() {
    const user = getCurrentUser();
    if (!user) {
        showToast('Please login to refer friends');
        window.location.href = 'login.html';
        return;
    }
    const referralCode = user.email.split('@')[0] + Math.floor(Math.random() * 1000);
    alert(`📢 Referral Program\n\nShare this code: ${referralCode}\n\nWhen they sign up and make first purchase of ৳1000+, you both get ৳500 credit!`);
}

function showGiftWrapModal() {
    const user = getCurrentUser();
    if (!user) {
        showToast('Please login to add gift wrap');
        window.location.href = 'login.html';
        return;
    }
    alert(`🎁 Gift Wrapping Service\n\nAdd gift wrapping to any order for just ৳50!\n\nIncludes: Premium gift box, Handwritten message card, Ribbon decoration`);
}

function toggleVoiceSearch() {
    if ('webkitSpeechRecognition' in window) {
        const recognition = new webkitSpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.onresult = function (event) {
            const transcript = event.results[0][0].transcript;
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.value = transcript;
                searchProducts();
            }
        };
        recognition.start();
        const voiceStatus = document.getElementById('voiceSearchStatus');
        if (voiceStatus) voiceStatus.innerHTML = '🎤 Listening... Speak now';
        setTimeout(() => {
            if (voiceStatus) voiceStatus.innerHTML = '';
        }, 3000);
    } else {
        showToast('Voice search not supported in your browser');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    initCarousel();

    // Close modals on escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal, .stock-modal, .cart-sidebar');
            modals.forEach(modal => {
                if (modal.classList && modal.classList.contains('open')) {
                    modal.classList.remove('open');
                }
            });
            const overlay = document.getElementById('cartOverlay');
            if (overlay && overlay.classList && overlay.classList.contains('open')) {
                overlay.classList.remove('open');
            }
        }
    });
});

// Make functions global - NOTE: requireLogin is NOT exported from here to avoid duplicate
window.showToast = showToast;
window.formatDate = formatDate;
window.changeSlide = changeSlide;
window.toggleChat = toggleChat;
window.sendChatMessage = sendChatMessage;
window.searchProducts = searchProducts;
window.subscribeNewsletter = subscribeNewsletter;
window.showReferralModal = showReferralModal;
window.showGiftWrapModal = showGiftWrapModal;
window.toggleVoiceSearch = toggleVoiceSearch;