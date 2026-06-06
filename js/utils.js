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

// ============================================
// ENHANCED CHAT FUNCTIONS
// ============================================

// Store chat history
let chatHistory = [];

function toggleChat() {
    const chatWindow = document.getElementById('chatWindow');
    if (chatWindow) {
        chatWindow.classList.toggle('open');
        if (chatWindow.classList.contains('open') && chatHistory.length === 0) {
            // Add welcome message when chat opens first time
            addBotMessage(getWelcomeMessage());
        }
    }
}

function sendChatMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    if (!message) return;

    // Add user message to chat
    addUserMessage(message);
    
    // Store in history
    chatHistory.push({ role: 'user', message: message, timestamp: new Date() });
    
    input.value = '';
    
    // Show typing indicator
    showTypingIndicator();
    
    // Simulate bot thinking time
    setTimeout(() => {
        hideTypingIndicator();
        const response = getEnhancedChatResponse(message);
        addBotMessage(response);
        chatHistory.push({ role: 'bot', message: response, timestamp: new Date() });
    }, 500);
}

function addUserMessage(message) {
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.innerHTML += `<div class="message user-message">${escapeHtml(message)}</div>`;
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function addBotMessage(message) {
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.innerHTML += `<div class="message bot-message">${message}</div>`;
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function showTypingIndicator() {
    const chatMessages = document.getElementById('chatMessages');
    const typingDiv = document.createElement('div');
    typingDiv.id = 'typingIndicator';
    typingDiv.className = 'message bot-message';
    typingDiv.innerHTML = '<em>Typing<span class="dot">.</span><span class="dot">.</span><span class="dot">.</span></em>';
    chatMessages.appendChild(typingDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Add dot animation style if not exists
    if (!document.getElementById('typingDotStyle')) {
        const style = document.createElement('style');
        style.id = 'typingDotStyle';
        style.textContent = `
            @keyframes typingDot {
                0%, 20% { opacity: 0; }
                50% { opacity: 1; }
                100% { opacity: 0; }
            }
            #typingIndicator .dot {
                animation: typingDot 1.4s infinite;
                opacity: 0;
            }
            #typingIndicator .dot:nth-child(1) { animation-delay: 0s; }
            #typingIndicator .dot:nth-child(2) { animation-delay: 0.2s; }
            #typingIndicator .dot:nth-child(3) { animation-delay: 0.4s; }
        `;
        document.head.appendChild(style);
    }
}

function hideTypingIndicator() {
    const indicator = document.getElementById('typingIndicator');
    if (indicator) indicator.remove();
}

function getWelcomeMessage() {
    return `🤲 <strong>Assalamu Alaikum! Welcome to #WE ARE MUSLIM</strong><br><br>
    I'm your fragrance assistant. I can help you with:<br><br>
    📋 Type <strong>"help"</strong> to see all features<br>
    🛍️ <strong>"products"</strong> - Browse our attars<br>
    📦 <strong>"track order"</strong> - Check order status<br>
    💰 <strong>"discounts"</strong> - Current offers<br>
    📞 <strong>"contact"</strong> - Reach our team<br><br>
    How may I assist you today?`;
}

function getEnhancedChatResponse(message) {
    const msg = message.toLowerCase().trim();
    
    // ============================================
    // HELP MENU
    // ============================================
    if (msg.includes('help') || msg.includes('menu') || msg.includes('what can you do')) {
        return `📋 <strong>✨ Help Menu - What I Can Do For You</strong><br><br>
        
        <strong>🛍️ PRODUCTS</strong><br>
        • "products" - Browse our attar collection<br>
        • "oudh" / "musk" / "rose" - Specific fragrance info<br>
        • "price [product]" - Check product pricing<br>
        • "recommendations" - Get personalized suggestions<br><br>
        
        <strong>📦 ORDERS</strong><br>
        • "track order" - Check your order status<br>
        • "order [ORD123]" - Track specific order<br>
        • "my orders" - View order history<br><br>
        
        <strong>🚚 SHIPPING</strong><br>
        • "shipping" - Delivery times and costs<br>
        • "free shipping" - Free delivery info<br>
        • "cod" - Cash on delivery details<br><br>
        
        <strong>🔄 RETURNS</strong><br>
        • "return policy" - How to return items<br>
        • "exchange" - Product exchange process<br>
        • "refund" - Refund timeline<br><br>
        
        <strong>💰 OFFERS</strong><br>
        • "discounts" / "coupons" - Current deals<br>
        • "tier" - Loyalty tier benefits<br><br>
        
        <strong>📞 CONTACT</strong><br>
        • "contact" / "support" - Get human assistance<br><br>
        
        Type your question or keyword above!`;
    }
    
    // ============================================
    // GREETINGS
    // ============================================
    const greetings = ['salam', 'hello', 'hi', 'hey', 'assalamu', 'walikum', 'good morning', 'good afternoon', 'good evening'];
    if (greetings.some(g => msg.includes(g))) {
        const hour = new Date().getHours();
        let timeGreeting = '';
        if (hour < 12) timeGreeting = 'Good morning';
        else if (hour < 18) timeGreeting = 'Good afternoon';
        else timeGreeting = 'Good evening';
        
        return `🤲 Wa Alaikum Assalam! ${timeGreeting}!<br><br>
        Welcome to #WE ARE MUSLIM - your destination for premium attars and fragrances.<br><br>
        How may I assist you today? Type <strong>"help"</strong> to see what I can do!`;
    }
    
    // ============================================
    // PRODUCT INFORMATION
    // ============================================
    if (msg.includes('product') || msg.includes('attar') || msg.includes('fragrance') || msg.includes('perfume')) {
        return `🌸 <strong>Our Premium Attar Collection</strong><br><br>
        We offer a wide range of traditional and modern fragrances:<br><br>
        <strong>🌿 Oudh Collection</strong><br>
        • Royal Oudh - Rich, woody, long-lasting (৳2,990)<br>
        • Oudh Al Misk - Premium Cambodian oudh (৳3,590)<br>
        • Amber Oudh - Warm, sensual blend (৳2,490)<br><br>
        
        <strong>🌹 Floral Attars</strong><br>
        • Rose Attar - Pure Damask rose (৳990)<br>
        • Jasmine Supreme - Exotic, blooming (৳1,290)<br>
        • Saffron Royale - Luxury saffron (৳2,190)<br><br>
        
        <strong>🪵 Classic Attars</strong><br>
        • Musk Al Haramain - Traditional musk (৳1,890)<br>
        • Sandalwood Classic - Pure Mysore sandalwood (৳1,590)<br><br>
        
        👉 Visit our <a href="shop.html" style="color: #8B5E3C;">Shop Page</a> to explore all products!`;
    }
    
    // Specific product queries
    if (msg.includes('oudh')) {
        return `🌿 <strong>Oudh Attars</strong><br><br>
        Our Oudh collection features premium, long-lasting fragrances:<br><br>
        • <strong>Royal Oudh</strong> (৳2,990) - Rich, woody, masculine scent<br>
        • <strong>Oudh Al Misk</strong> (৳3,590) - Premium Cambodian oudh blend<br>
        • <strong>Amber Oudh</strong> (৳2,490) - Warm, sensual, evening wear<br><br>
        ✨ All our oudh attars are 100% natural and alcohol-free.<br><br>
        Would you like to know more about any specific product?`;
    }
    
    if (msg.includes('musk')) {
        return `🦌 <strong>Musk Attars</strong><br><br>
        • <strong>Musk Al Haramain</strong> (৳1,890)<br>
        Traditional white musk that captivates the senses. Clean, powdery, and long-lasting.<br><br>
        • <strong>Oudh Al Misk</strong> (৳3,590)<br>
        Premium blend of Cambodian oudh and white musk - our bestseller!<br><br>
        Perfect for daily wear and special occasions.`;
    }
    
    if (msg.includes('rose')) {
        return `🌹 <strong>Rose Attar</strong> (৳990)<br><br>
        Pure rose petal attar extracted from Damask roses using traditional hydro-distillation.<br><br>
        ✨ Features:<br>
        • 100% natural, alcohol-free<br>
        • Romantic, feminine, timeless scent<br>
        • Long-lasting 8-10 hours<br>
        • Perfect for gifting<br><br>
        Would you like me to recommend similar fragrances?`;
    }
    
    if (msg.includes('sandalwood')) {
        return `🪵 <strong>Sandalwood Classic</strong> (৳1,590)<br><br>
        Pure sandalwood oil from Mysore, India. Known for its creamy, woody, and meditative aroma.<br><br>
        ✨ Benefits:<br>
        • Calming and grounding scent<br>
        • Used in spiritual practices<br>
        • Excellent base note that lasts all day<br>
        • Blends beautifully with other attars<br><br>
        A timeless classic loved by all!`;
    }
    
    // Recommendations
    if (msg.includes('recommend') || msg.includes('suggest') || msg.includes('best seller') || msg.includes('popular')) {
        return `⭐ <strong>Our Best Sellers & Recommendations</strong><br><br>
        
        <strong>🏆 Top Rated (4.9★)</strong><br>
        • Oudh Al Misk - Premium oudh-musk blend<br>
        • Musk Al Haramain - Classic white musk<br><br>
        
        <strong>🔥 Most Popular</strong><br>
        • Royal Oudh - Rich, woody signature scent<br>
        • Rose Attar - Pure, romantic floral<br><br>
        
        <strong>💎 Best Value</strong><br>
        • Sandalwood Classic - Affordable luxury<br>
        • Jasmine Supreme - Exotic yet budget-friendly<br><br>
        
        <strong>🎁 Gift Recommendations</strong><br>
        • Royal Oudh + Rose Attar gift set<br>
        • Oudh Al Misk in premium gift box<br><br>
        
        Which type of fragrance are you looking for?`;
    }
    
    // Price inquiry
    if (msg.includes('price') || msg.includes('cost') || msg.includes('how much') || msg.includes('৳')) {
        if (msg.includes('oudh')) {
            return `💰 <strong>Oudh Collection Pricing</strong><br><br>
            • Royal Oudh: ৳2,990<br>
            • Oudh Al Misk: ৳3,590<br>
            • Amber Oudh: ৳2,490<br><br>
            ✨ Free shipping on orders over ৳2,000!<br>
            💳 Cash on Delivery available nationwide.`;
        } else if (msg.includes('musk')) {
            return `💰 <strong>Musk Attar Pricing</strong><br><br>
            • Musk Al Haramain: ৳1,890<br>
            • Oudh Al Misk: ৳3,590 (Oudh + Musk blend)<br><br>
            🎁 Bundle offer: Buy both for ৳5,000 (save ৳480)!`;
        } else if (msg.includes('rose')) {
            return `💰 <strong>Rose Attar</strong> ৳990<br><br>
            🎁 Special offer: Buy 2 Rose Attars for ৳1,800 (save ৳180)!`;
        } else {
            return `💰 <strong>Attar Price Range</strong><br><br>
            • Entry Level: ৳990 - ৳1,590<br>
            • Premium: ৳1,890 - ৳2,490<br>
            • Luxury: ৳2,990 - ৳3,590+<br><br>
            Which product are you interested in? I can give you the exact price!`;
        }
    }
    
    // ============================================
    // ORDER TRACKING
    // ============================================
    // Check for order number pattern (ORD followed by numbers)
    const orderMatch = msg.match(/ord\d+/i);
    if (orderMatch) {
        const orderId = orderMatch[0].toUpperCase();
        return `📦 <strong>Order Tracking</strong><br><br>
        I found order #${orderId} in your message.<br><br>
        👉 <a href="order-tracking.html?order=${orderId}" style="color: #8B5E3C;">Click here to track your order</a><br><br>
        You can also:<br>
        • Check your <a href="dashboard.html" style="color: #8B5E3C;">Dashboard</a> for order history<br>
        • Contact support for assistance<br><br>
        Need help with something else?`;
    }
    
    if (msg.includes('track') || msg.includes('order status') || msg.includes('where is my order')) {
        return `📦 <strong>Track Your Order</strong><br><br>
        You can track your order in several ways:<br><br>
        1️⃣ <strong>Via Dashboard</strong><br>
        Login to your account and go to "My Orders"<br><br>
        2️⃣ <strong>Tracking Page</strong><br>
        Visit our <a href="order-tracking.html" style="color: #8B5E3C;">Order Tracking Page</a><br><br>
        3️⃣ <strong>Send Order Number</strong><br>
        Reply with your order number (starts with ORD)<br><br>
        4️⃣ <strong>Contact Support</strong><br>
        Email: info@wearemuslim.com<br><br>
        What's your order number? I can help track it!`;
    }
    
    if (msg.includes('my orders') || msg.includes('order history')) {
        return `📋 <strong>View Your Order History</strong><br><br>
        👉 <a href="dashboard.html" style="color: #8B5E3C;">Go to Your Dashboard</a><br><br>
        From your dashboard, you can:<br>
        • View all past orders<br>
        • Track current orders<br>
        • Request returns/exchanges<br>
        • Download invoices<br><br>
        Please login to your account first if you haven't already.`;
    }
    
    // ============================================
    // SHIPPING INFORMATION
    // ============================================
    if (msg.includes('shipping') || msg.includes('delivery') || msg.includes('delivered') || msg.includes('shipping time')) {
        return `🚚 <strong>Shipping Information</strong><br><br>
        
        <strong>📍 Delivery Areas</strong><br>
        • We deliver nationwide across Bangladesh<br>
        • International shipping coming soon<br><br>
        
        <strong>⏱️ Delivery Time</strong><br>
        • Dhaka: 2-3 business days<br>
        • Outside Dhaka: 3-5 business days<br>
        • Rural areas: 5-7 business days<br><br>
        
        <strong>💰 Shipping Costs</strong><br>
        • Free shipping on orders over ৳2,000<br>
        • Standard delivery: ৳60<br><br>
        
        <strong>📦 Order Processing</strong><br>
        • Orders processed within 24 hours<br>
        • Tracking number provided via email<br>
        • Cash on Delivery available everywhere<br><br>
        
        Need express delivery? Contact our support team!`;
    }
    
    if (msg.includes('free shipping')) {
        return `🎉 <strong>Free Shipping Offer</strong><br><br>
        ✨ Free delivery on all orders of ৳2,000 or more! ✨<br><br>
        
        <strong>How to get free shipping:</strong><br>
        1. Add products worth ৳2,000+ to cart<br>
        2. Proceed to checkout<br>
        3. Shipping charge will show as ৳0<br><br>
        
        Current cart total: Add items worth ৳2,000+<br><br>
        👉 <a href="shop.html" style="color: #8B5E3C;">Continue Shopping →</a>`;
    }
    
    if (msg.includes('cod') || msg.includes('cash on delivery')) {
        return `💵 <strong>Cash on Delivery (COD)</strong><br><br>
        Yes, we offer Cash on Delivery for all orders in Bangladesh!<br><br>
        
        <strong>How COD works:</strong><br>
        1. Place your order and select "Cash on Delivery"<br>
        2. Receive your package<br>
        3. Pay cash to the delivery person<br><br>
        
        <strong>Important Notes:</strong><br>
        • No advance payment required<br>
        • Please keep exact change ready<br>
        • Inspect package before paying<br>
        • COD available for all products<br><br>
        
        Ready to shop? 👉 <a href="shop.html" style="color: #8B5E3C;">Shop Now</a>`;
    }
    
    // ============================================
    // RETURNS & EXCHANGES
    // ============================================
    if (msg.includes('return') || msg.includes('exchange') || msg.includes('refund') || msg.includes('return policy')) {
        return `🔄 <strong>Returns & Exchanges Policy</strong><br><br>
        
        <strong>✅ Easy 7-Day Returns</strong><br>
        • Return within 7 days of delivery<br>
        • Product must be unused & in original packaging<br>
        • Full refund or exchange offered<br><br>
        
        <strong>📝 How to Request a Return:</strong><br>
        1. Login to your <a href="dashboard.html" style="color: #8B5E3C;">Dashboard</a><br>
        2. Go to "My Orders"<br>
        3. Click "Return/Exchange" on the order<br>
        4. Fill out the request form<br>
        5. Our team will review within 24-48 hours<br><br>
        
        <strong>💵 Refund Timeline:</strong><br>
        • Refunds processed within 5-7 business days<br>
        • Money returned to original payment method<br>
        • COD orders refunded via bKash/bank transfer<br><br>
        
        <strong>⚠️ Non-Returnable Items:</strong><br>
        • Used or opened products<br>
        • Items without original packaging<br>
        • Sale/discounted items (final sale)<br><br>
        
        Have a damaged item? Contact us immediately for free replacement!`;
    }
    
    // ============================================
    // DISCOUNTS & COUPONS
    // ============================================
    if (msg.includes('discount') || msg.includes('coupon') || msg.includes('offer') || msg.includes('sale') || msg.includes('promo')) {
        return `💰 <strong>Current Offers & Discounts</strong><br><br>
        
        <strong>🎁 New Customer Offer</strong><br>
        • Use code: <strong>WELCOME10</strong><br>
        • Get 10% off your first order<br>
        • Minimum order: ৳500<br><br>
        
        <strong>🚚 Free Shipping</strong><br>
        • On all orders over ৳2,000<br>
        • Automatic at checkout<br><br>
        
        <strong>⭐ Tier Discounts (Automatic)</strong><br>
        • Bronze (0-19,999 spent): 0% off<br>
        • Silver (20,000-49,999): 5% off<br>
        • Gold (50,000-99,999): 10% off<br>
        • Platinum (100,000+): 12% off<br><br>
        
        <strong>🌙 Seasonal Sales</strong><br>
        • Check homepage banner for current sales<br>
        • Follow us on social media for flash sales<br><br>
        
        <strong>🎯 Referral Program</strong><br>
        • Refer a friend, get ৳500 credit<br>
        • Your friend gets 10% off first order<br><br>
        
        Want to apply a coupon? Add items to cart and enter code at checkout!`;
    }
    
    if (msg.includes('tier') || msg.includes('loyalty') || msg.includes('member')) {
        return `⭐ <strong>Loyalty Tier Program</strong><br><br>
        
        <strong>How it works:</strong><br>
        • Earn tier upgrades based on total spent<br>
        • Automatic discounts on every order<br>
        • Tiers valid for 30 days, renewal on new purchase<br><br>
        
        <strong>🥉 Bronze (0 - 19,999 spent)</strong><br>
        • 0% discount<br>
        • Welcome to the family!<br><br>
        
        <strong>🥈 Silver (20,000 - 49,999 spent)</strong><br>
        • 5% discount on all orders<br>
        • Priority support<br><br>
        
        <strong>🥇 Gold (50,000 - 99,999 spent)</strong><br>
        • 10% discount on all orders<br>
        • Free shipping on all orders<br>
        • Early access to sales<br><br>
        
        <strong>💎 Platinum (100,000+ spent)</strong><br>
        • 12% discount on all orders<br>
        • Free express shipping<br>
        • Exclusive previews<br>
        • Dedicated support line<br><br>
        
        👉 <a href="dashboard.html" style="color: #8B5E3C;">Check your current tier in Dashboard</a>`;
    }
    
    // ============================================
    // CONTACT & SUPPORT
    // ============================================
    if (msg.includes('contact') || msg.includes('support') || msg.includes('human') || msg.includes('agent') || msg.includes('speak') || msg.includes('call')) {
        return `📞 <strong>Contact Customer Support</strong><br><br>
        
        <strong>📧 Email</strong><br>
        • General inquiries: info@wearemuslim.com<br>
        • Order issues: orders@wearemuslim.com<br>
        • Returns: returns@wearemuslim.com<br>
        • Response time: Within 24 hours<br><br>
        
        <strong>📞 Phone</strong><br>
        • Hotline: +880 1234 567890<br>
        • Hours: Saturday - Thursday, 10AM - 8PM<br>
        • Friday: Closed<br><br>
        
        <strong>💬 Live Chat</strong><br>
        • Available 24/7 for basic inquiries<br>
        • Complex issues forwarded to email<br><br>
        
        <strong>📱 Social Media</strong><br>
        • Facebook: @wearemuslim<br>
        • Instagram: @wearemuslim<br>
        • WhatsApp: +880 1234 567890<br><br>
        
        <strong>📍 Office Address</strong><br>
        House #42, Road #12, Banani, Dhaka-1213<br><br>
        
        How would you like to reach us? I can help connect you!`;
    }
    
    // ============================================
    // ACCOUNT HELP
    // ============================================
    if (msg.includes('login') || msg.includes('signup') || msg.includes('register') || msg.includes('account')) {
        return `👤 <strong>Account Help</strong><br><br>
        
        <strong>🔐 Login</strong><br>
        👉 <a href="login.html" style="color: #8B5E3C;">Click here to Login</a><br><br>
        
        <strong>📝 Create Account</strong><br>
        • Register with email or phone<br>
    • Get 10% off first order with code WELCOME10<br>
        • Track orders easily<br>
        • Save address for faster checkout<br>
        • Earn tier discounts<br><br>
        
        <strong>🔑 Forgot Password?</strong><br>
        • Click "Forgot Password" on login page<br>
        • Enter your email<br>
        • Follow reset instructions sent to your email<br><br>
        
        <strong>✏️ Update Profile</strong><br>
        • Login to your <a href="dashboard.html" style="color: #8B5E3C;">Dashboard</a><br>
        • Go to Profile tab<br>
        • Update name, phone, address<br><br>
        
        Need help? Reply with your specific issue!`;
    }
    
    // ============================================
    // THANK YOU / CLOSING
    // ============================================
    if (msg.includes('thank') || msg.includes('thanks') || msg.includes('good') || msg.includes('great') || msg.includes('awesome')) {
        return `🤲 <strong>Jazakallah Khair!</strong><br><br>
        Thank you for choosing #WE ARE MUSLIM.<br><br>
        Is there anything else I can help you with today?<br><br>
        • Type <strong>"help"</strong> to see all options<br>
        • Type <strong>"products"</strong> to browse attars<br>
        • Type <strong>"contact"</strong> to reach support<br><br>
        Have a blessed day! 🌙`;
    }
    
    // ============================================
    // SMALL TALK / CASUAL
    // ============================================
    if (msg.includes('how are you') || msg.includes('how r u')) {
        return `😊 I'm doing great, thank you for asking!<br><br>
        I'm here 24/7 to help you find the perfect fragrance. How can I assist you today?`;
    }
    
    if (msg.includes('what is your name') || msg.includes('who are you')) {
        return `🌸 I'm your fragrance assistant at #WE ARE MUSLIM!<br><br>
        I'm here to help you discover premium attars, track orders, answer questions, and make your shopping experience delightful.<br><br>
        What would you like to know?`;
    }
    
    if (msg.includes('bye') || msg.includes('goodbye') || msg.includes('tata')) {
        return `👋 Goodbye! Thank you for visiting #WE ARE MUSLIM.<br><br>
        Come back anytime for premium fragrances!<br><br>
        ✨ May you always smell wonderful! ✨`;
    }
    
    // ============================================
    // DEFAULT RESPONSE
    // ============================================
    return `🤲 <strong>Thank you for your message!</strong><br><br>
    
    I'm not sure I understood that completely. Here's what I can help with:<br><br>
    
    📋 Type <strong>"help"</strong> for complete menu<br>
    🛍️ <strong>"products"</strong> - Browse our attar collection<br>
    📦 <strong>"track order"</strong> - Check order status<br>
    💰 <strong>"discounts"</strong> - Current offers & coupons<br>
    📞 <strong>"contact"</strong> - Reach human support<br>
    🔄 <strong>"return policy"</strong> - Returns & exchanges<br><br>
    
    Could you please rephrase your question? I want to make sure I help you correctly!`;
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

document.addEventListener('DOMContentLoaded', function () {
    initCarousel();

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