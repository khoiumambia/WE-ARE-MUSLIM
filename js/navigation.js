// Navigation Manager - Handles role-based navigation

const NAV_CONFIG = {
    guest: [
        { href: "index.html", label: "Home", icon: "fas fa-home" },
        { href: "shop.html", label: "Shop", icon: "fas fa-shopping-bag" },
        { href: "about.html", label: "About", icon: "fas fa-info-circle" },
        { href: "contact.html", label: "Contact", icon: "fas fa-envelope" },
        { href: "blog.html", label: "Blog", icon: "fas fa-blog" },
        { href: "login.html", label: "Login / Sign Up", icon: "fas fa-user", highlight: true }
    ],
    customer: [
        { href: "index.html", label: "Home", icon: "fas fa-home" },
        { href: "shop.html", label: "Shop", icon: "fas fa-shopping-bag" },
        { href: "compare.html", label: "Compare", icon: "fas fa-chart-line" },
        { href: "blog.html", label: "Blog", icon: "fas fa-blog" },
        { href: "order-tracking.html", label: "Track", icon: "fas fa-truck" },
        { href: "dashboard.html", label: "Dashboard", icon: "fas fa-user-circle" },
        { href: "about.html", label: "About", icon: "fas fa-info-circle" },
        { href: "contact.html", label: "Contact", icon: "fas fa-envelope" }
    ],
    admin: [
        { href: "admin-dashboard.html", label: "Dashboard", icon: "fas fa-chart-pie" },
        { href: "admin-homepage.html", label: "Homepage", icon: "fas fa-home" },
        { href: "admin-products.html", label: "Products", icon: "fas fa-boxes" },
        { href: "admin-orders.html", label: "Orders", icon: "fas fa-shopping-cart" },
        { href: "admin-users.html", label: "Users", icon: "fas fa-users" },
        { href: "admin-blog.html", label: "Blog", icon: "fas fa-blog" },
        { href: "admin-returns.html", label: "Returns", icon: "fas fa-undo-alt" },
        { href: "admin-coupons.html", label: "Coupons", icon: "fas fa-tag" },
        { href: "admin-settings.html", label: "Settings", icon: "fas fa-cogs" }
    ]
};

function getCurrentUser() {
    const userJson = localStorage.getItem('currentUser');
    if (!userJson) return null;
    try {
        return JSON.parse(userJson);
    } catch (e) {
        return null;
    }
}

function isAdmin() {
    const user = getCurrentUser();
    return user && user.role === 'admin';
}

function isLoggedIn() {
    return getCurrentUser() !== null;
}

function getNavLinks() {
    if (isAdmin()) {
        return NAV_CONFIG.admin;
    } else if (isLoggedIn()) {
        return NAV_CONFIG.customer;
    } else {
        return NAV_CONFIG.guest;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function updateNavCartCount() {
    const cartCountSpan = document.getElementById('cartCountNav');
    if (cartCountSpan) {
        try {
            const cart = JSON.parse(localStorage.getItem('attar_cart')) || [];
            const count = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
            cartCountSpan.textContent = count;
        } catch (e) { }
    }
}

function updateNavWishlistCount() {
    const wishlistSpan = document.getElementById('wishlistCountNav');
    if (wishlistSpan) {
        try {
            const wishlist = JSON.parse(localStorage.getItem('attar_wishlist')) || [];
            wishlistSpan.textContent = wishlist.length;
        } catch (e) { }
    }
}

async function getUserTier(email) {
    try {
        const response = await fetch(`api/get-user-tier.php?email=${encodeURIComponent(email)}`);
        const result = await response.json();
        if (result.success) {
            return { 
                tier: result.tier, 
                discount: result.discount, 
                total_spent: result.total_spent,
                expiry: result.expiry
            };
        }
        return { tier: 'Bronze', discount: 0, total_spent: 0, expiry: null };
    } catch (error) {
        console.error('Error getting tier:', error);
        return { tier: 'Bronze', discount: 0, total_spent: 0, expiry: null };
    }
}

function getTierInfo(tier) {
    switch(tier) {
        case 'Platinum':
            return { class: 'tier-platinum', icon: '💎', name: 'Platinum', discount: 12 };
        case 'Gold':
            return { class: 'tier-gold', icon: '🥇', name: 'Gold', discount: 10 };
        case 'Silver':
            return { class: 'tier-silver', icon: '🥈', name: 'Silver', discount: 5 };
        default:
            return { class: 'tier-bronze', icon: '🥉', name: 'Bronze', discount: 0 };
    }
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('currentUser');
        sessionStorage.clear();
        window.location.href = 'index.html';
    }
}

async function renderNavigation() {
    const container = document.getElementById('navigation-placeholder');
    if (!container) return;

    const user = getCurrentUser();
    const isAdminUser = isAdmin();
    const isLoggedInUser = isLoggedIn();
    const navLinks = getNavLinks();

    let cartCount = 0;
    let wishlistCount = 0;
    let userTier = 'Bronze';
    let tierIcon = '🥉';
    let tierDiscount = 0;

    try {
        const cart = JSON.parse(localStorage.getItem('attar_cart')) || [];
        cartCount = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);

        const wishlist = JSON.parse(localStorage.getItem('attar_wishlist')) || [];
        wishlistCount = wishlist.length;

        if (user && user.email && !isAdminUser) {
            const tierData = await getUserTier(user.email);
            userTier = tierData.tier;
            tierDiscount = tierData.discount;
            const tierInfo = getTierInfo(userTier);
            tierIcon = tierInfo.icon;
        }
    } catch (e) { 
        console.error('Error getting user data:', e);
    }

    let logoHtml = '';
    if (isAdminUser) {
        logoHtml = `
            <div class="logo" style="cursor: default;">
                #WE ARE MUSLIM
                <div class="logo-sub">ADMIN PANEL</div>
            </div>
        `;
    } else {
        logoHtml = `
            <div class="logo" onclick="window.location.href='index.html'">
                #WE ARE MUSLIM
                <div class="logo-sub">PREMIUM ATTARS & FRAGRANCES</div>
            </div>
        `;
    }

    let html = `
        <nav class="navbar">
            <div class="nav-container">
                ${logoHtml}
                <div class="nav-links">
    `;

    for (const link of navLinks) {
        const isActive = window.location.pathname.includes(link.href.replace('.html', ''));
        const highlightClass = link.highlight ? 'nav-highlight' : '';
        html += `<a href="${link.href}" class="${isActive ? 'active' : ''} ${highlightClass}">
                    <i class="${link.icon}"></i> ${link.label}
                </a>`;
    }

    if (isLoggedInUser) {
        if (isAdminUser) {
            // Admin navigation - NO CART ICON
            html += `
                <div class="user-menu admin-menu">
                    <i class="fas fa-user-shield"></i>
                    <span>Admin: ${escapeHtml(user.name)}</span>
                    <div class="user-dropdown">
                        <a href="admin-dashboard.html"><i class="fas fa-chart-pie"></i> Dashboard</a>
                        <a href="admin-homepage.html"><i class="fas fa-home"></i> Homepage</a>
                        <a href="admin-products.html"><i class="fas fa-boxes"></i> Products</a>
                        <a href="admin-orders.html"><i class="fas fa-shopping-cart"></i> Orders</a>
                        <a href="admin-users.html"><i class="fas fa-users"></i> Users</a>
                        <a href="admin-blog.html"><i class="fas fa-blog"></i> Blog</a>
                        <a href="admin-returns.html"><i class="fas fa-undo-alt"></i> Returns</a>
                        <a href="admin-coupons.html"><i class="fas fa-tag"></i> Coupons</a>
                        <a href="admin-settings.html"><i class="fas fa-cogs"></i> Settings</a>
                        <a href="#" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            `;
        } else {
            // Customer navigation - WITH cart and wishlist icons
            html += `
                <div class="cart-icon" onclick="window.location.href='cart.html'">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="cart-count" id="cartCountNav">${cartCount}</span>
                </div>
                <div class="wishlist-icon" onclick="window.location.href='wishlist.html'">
                    <i class="far fa-heart"></i>
                    <span class="wishlist-count" id="wishlistCountNav">${wishlistCount}</span>
                </div>
                <div class="user-menu">
                    <i class="fas fa-user-circle"></i>
                    <div class="user-dropdown">
                        <div class="user-info">
                            <strong>${escapeHtml(user.name || user.email)}</strong>
                            <small>${tierIcon} ${userTier} Member (${tierDiscount}% off)</small>
                        </div>
                        <a href="dashboard.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <a href="cart.html"><i class="fas fa-shopping-cart"></i> Cart</a>
                        <a href="wishlist.html"><i class="fas fa-heart"></i> Wishlist</a>
                        <a href="#" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            `;
        }
    }

    html += `
                </div>
            </div>
        </nav>
    `;

    container.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function () {
    renderNavigation();

    window.addEventListener('storage', function (e) {
        if (e.key === 'attar_cart') updateNavCartCount();
        if (e.key === 'attar_wishlist') updateNavWishlistCount();
        if (e.key === 'currentUser') renderNavigation();
    });
});

window.getUserTier = getUserTier;
window.getTierInfo = getTierInfo;
window.updateNavCartCount = updateNavCartCount;
window.updateNavWishlistCount = updateNavWishlistCount;