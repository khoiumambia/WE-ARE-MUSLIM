// Navigation Manager - Handles role-based navigation

const NAV_CONFIG = {
    // Links for non-authenticated users (guests)
    guest: [
        { href: "index.html", label: "Home", icon: "fas fa-home" },
        { href: "shop.html", label: "Shop", icon: "fas fa-shopping-bag" },
        { href: "about.html", label: "About", icon: "fas fa-info-circle" },
        { href: "contact.html", label: "Contact", icon: "fas fa-envelope" },
        { href: "blog.html", label: "Blog", icon: "fas fa-blog" },
        { href: "login.html", label: "Login / Sign Up", icon: "fas fa-user", highlight: true }
    ],

    // Links for regular customers
    customer: [
        { href: "index.html", label: "Home", icon: "fas fa-home" },
        { href: "shop.html", label: "Shop", icon: "fas fa-shopping-bag" },
        { href: "cart.html", label: "Cart", icon: "fas fa-shopping-cart" },
        { href: "compare.html", label: "Compare", icon: "fas fa-chart-line" },
        { href: "loyalty.html", label: "Loyalty", icon: "fas fa-star" },
        { href: "affiliate.html", label: "Affiliate", icon: "fas fa-hand-holding-usd" },
        { href: "blog.html", label: "Blog", icon: "fas fa-blog" },
        { href: "order-tracking.html", label: "Track", icon: "fas fa-truck" },
        { href: "dashboard.html", label: "Dashboard", icon: "fas fa-user-circle" },
        { href: "about.html", label: "About", icon: "fas fa-info-circle" },
        { href: "contact.html", label: "Contact", icon: "fas fa-envelope" }
    ],

    // Links for admin users - UPDATED: Removed Home, Added Users
    admin: [
        { href: "admin-dashboard.html", label: "Dashboard", icon: "fas fa-chart-pie" },
        { href: "admin-products.html", label: "Products", icon: "fas fa-boxes" },
        { href: "admin-orders.html", label: "Orders", icon: "fas fa-shopping-cart" },
        { href: "admin-users.html", label: "Users", icon: "fas fa-users" },
        { href: "admin-returns.html", label: "Returns", icon: "fas fa-undo-alt" },
        { href: "admin-settings.html", label: "Settings", icon: "fas fa-cogs" }
    ]
};

// Get current logged in user
function getCurrentUser() {
    const userJson = localStorage.getItem('currentUser');
    if (!userJson) return null;
    try {
        return JSON.parse(userJson);
    } catch (e) {
        return null;
    }
}

// Check if user is admin
function isAdmin() {
    const user = getCurrentUser();
    return user && user.role === 'admin';
}

// Check if user is logged in
function isLoggedIn() {
    return getCurrentUser() !== null;
}

// Get navigation links based on user role
function getNavLinks() {
    if (isAdmin()) {
        return NAV_CONFIG.admin;
    } else if (isLoggedIn()) {
        return NAV_CONFIG.customer;
    } else {
        return NAV_CONFIG.guest;
    }
}

// Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Update cart count in navigation
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

// Update wishlist count in navigation
function updateNavWishlistCount() {
    const wishlistSpan = document.getElementById('wishlistCountNav');
    if (wishlistSpan) {
        try {
            const wishlist = JSON.parse(localStorage.getItem('attar_wishlist')) || [];
            wishlistSpan.textContent = wishlist.length;
        } catch (e) { }
    }
}

// Update points display in navigation
function updateNavPointsCount() {
    const pointsSpan = document.getElementById('pointsCountNav');
    if (pointsSpan) {
        try {
            const points = parseInt(localStorage.getItem('loyalty_points')) || 0;
            pointsSpan.textContent = points;
        } catch (e) { }
    }
}

// Logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('currentUser');
        window.location.href = 'index.html';
    }
}

// Render navigation bar
function renderNavigation() {
    const container = document.getElementById('navigation-placeholder');
    if (!container) return;

    const user = getCurrentUser();
    const isAdminUser = isAdmin();
    const isLoggedInUser = isLoggedIn();
    const navLinks = getNavLinks();

    // Count cart items (only for customers)
    let cartCount = 0;
    let wishlistCount = 0;
    let pointsCount = 0;

    try {
        const cart = JSON.parse(localStorage.getItem('attar_cart')) || [];
        cartCount = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);

        const wishlist = JSON.parse(localStorage.getItem('attar_wishlist')) || [];
        wishlistCount = wishlist.length;

        pointsCount = parseInt(localStorage.getItem('loyalty_points')) || 0;
    } catch (e) { }

    let html = `
        <nav class="navbar">
            <div class="nav-container">
                <div class="logo" onclick="window.location.href='index.html'">
                    #WE ARE MUSLIM
                    <div class="logo-sub">PREMIUM ATTARS & FRAGRANCES</div>
                </div>
                <div class="nav-links">
    `;

    // Add navigation links
    for (const link of navLinks) {
        const isActive = window.location.pathname.includes(link.href.replace('.html', ''));
        const highlightClass = link.highlight ? 'nav-highlight' : '';
        html += `<a href="${link.href}" class="${isActive ? 'active' : ''} ${highlightClass}">
                    <i class="${link.icon}"></i> ${link.label}
                </a>`;
    }

    // Add cart and wishlist for customers (non-admin logged in users)
    if (isLoggedInUser && !isAdminUser) {
        html += `
            <div class="cart-icon" onclick="window.location.href='cart.html'">
                <i class="fas fa-shopping-bag"></i>
                <span class="cart-count" id="cartCountNav">${cartCount}</span>
            </div>
            <div class="wishlist-icon" onclick="window.location.href='wishlist.html'">
                <i class="far fa-heart"></i>
                <span class="wishlist-count" id="wishlistCountNav">${wishlistCount}</span>
            </div>
            <div class="points-icon" onclick="window.location.href='loyalty.html'">
                <i class="fas fa-star"></i>
                <span class="points-count" id="pointsCountNav">${pointsCount}</span>
            </div>
            <div class="user-menu">
                <i class="fas fa-user-circle"></i>
                <div class="user-dropdown">
                    <div class="user-info">
                        <strong>${escapeHtml(user.name || user.email)}</strong>
                        <small>Customer</small>
                    </div>
                    <a href="dashboard.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="cart.html"><i class="fas fa-shopping-cart"></i> Cart</a>
                    <a href="wishlist.html"><i class="fas fa-heart"></i> Wishlist</a>
                    <a href="#" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        `;
    } else if (isAdminUser) {
        html += `
            <div class="user-menu admin-menu">
                <i class="fas fa-user-shield"></i>
                <span>Admin: ${escapeHtml(user.name)}</span>
                <div class="user-dropdown">
                    <a href="admin-dashboard.html"><i class="fas fa-chart-pie"></i> Dashboard</a>
                    <a href="admin-products.html"><i class="fas fa-boxes"></i> Products</a>
                    <a href="admin-orders.html"><i class="fas fa-shopping-cart"></i> Orders</a>
                    <a href="admin-users.html"><i class="fas fa-users"></i> Users</a>
                    <a href="admin-returns.html"><i class="fas fa-undo-alt"></i> Returns</a>
                    <a href="admin-settings.html"><i class="fas fa-cogs"></i> Settings</a>
                    <a href="#" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        `;
    }

    html += `
                </div>
            </div>
        </nav>
    `;

    container.innerHTML = html;
}

// Initialize navigation on page load
document.addEventListener('DOMContentLoaded', function () {
    renderNavigation();

    // Listen for storage events to update counts across tabs
    window.addEventListener('storage', function (e) {
        if (e.key === 'attar_cart') updateNavCartCount();
        if (e.key === 'attar_wishlist') updateNavWishlistCount();
        if (e.key === 'loyalty_points') updateNavPointsCount();
        if (e.key === 'currentUser') renderNavigation();
    });
});