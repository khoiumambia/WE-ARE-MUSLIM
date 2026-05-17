// Index page specific JavaScript
let products = [];
let wishlist = [];
let cart = [];

function loadIndexData() {
    const stored = localStorage.getItem('attar_products');
    if (stored) {
        products = JSON.parse(stored);
    } else {
        products = [
            { id: 1, name: "Oudh Royal", brand: "Arabian Oud", fragrance: "Woody Oud", price: 1200, stock: 15, image: "https://images.unsplash.com/photo-1594035910387-fea47794261f?w=300", ratings: 4.8, reviews: 124, isBestSeller: true },
            { id: 2, name: "Musk Al Mahabba", brand: "Swiss Arabian", fragrance: "Musk", price: 850, stock: 0, image: "https://images.unsplash.com/photo-1585386959984-a4155224a1ad?w=300", ratings: 4.6, reviews: 89, isBestSeller: true },
            { id: 3, name: "Rose De Makkah", brand: "Al Haramain", fragrance: "Rose", price: 1500, stock: 3, image: "https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?w=300", ratings: 4.9, reviews: 56, isBestSeller: false },
            { id: 4, name: "Amber Night", brand: "Rasasi", fragrance: "Amber", price: 2200, stock: 20, image: "https://images.unsplash.com/photo-1595428774223-ef52624120d2?w=300", ratings: 4.7, reviews: 203, isBestSeller: true }
        ];
        localStorage.setItem('attar_products', JSON.stringify(products));
    }

    // Only load cart and wishlist if user is logged in
    const currentUser = getCurrentUser();
    if (currentUser) {
        wishlist = JSON.parse(localStorage.getItem('attar_wishlist')) || [];
        cart = JSON.parse(localStorage.getItem('attar_cart')) || [];
    } else {
        wishlist = [];
        cart = [];
    }

    displayFeatured();
    displayBestSellers();
    displayBlogPosts();
    startCountdown();
}

function displayFeatured() {
    const featured = products.slice(0, 4);
    const grid = document.getElementById('featuredProducts');
    if (grid) {
        grid.innerHTML = featured.map(p => renderProductCard(p)).join('');
    }
}

function displayBestSellers() {
    const bestSellers = products.filter(p => p.isBestSeller).slice(0, 4);
    const grid = document.getElementById('bestSellers');
    if (grid) {
        grid.innerHTML = bestSellers.map(p => renderProductCard(p)).join('');
    }
}

function renderProductCard(p) {
    const isLoggedIn = getCurrentUser() !== null;
    
    return `
        <div class="product-card" onclick="viewProduct(${p.id})">
            ${p.isBestSeller ? '<div class="product-badge">🔥 Best Seller</div>' : ''}
            <div class="product-actions">
                <button onclick="event.stopPropagation(); addToWishlist(${p.id})"><i class="far fa-heart"></i></button>
                <button onclick="event.stopPropagation(); addToCompare(${p.id})"><i class="fas fa-chart-line"></i></button>
            </div>
            <img class="product-img" src="${p.image}" onerror="this.src='https://via.placeholder.com/300x250?text=Attar'">
            <div class="product-info">
                <div class="product-title">${escapeHtml(p.name)}</div>
                <div style="font-size: 0.8rem; color: #666;">${escapeHtml(p.brand)} | ${escapeHtml(p.fragrance)}</div>
                <div class="product-price">৳${p.price.toLocaleString()}</div>
                <div style="font-size: 0.8rem;">⭐ ${p.ratings} (${p.reviews} reviews)</div>
                <button class="btn btn-primary" style="width:100%; margin-top: 10px;" onclick="event.stopPropagation(); addToCart(${p.id})">
                    <i class="fas fa-shopping-cart"></i> ${isLoggedIn ? 'Add to Cart' : 'Login to Add to Cart'}
                </button>
            </div>
        </div>
    `;
}

function displayBlogPosts() {
    const blogPosts = [
        { title: "How to Choose the Perfect Attar", image: "https://images.unsplash.com/photo-1594035910387-fea47794261f?w=300", excerpt: "A complete guide to selecting fragrances...", date: "Dec 15, 2024" },
        { title: "The History of Oudh in Islamic Culture", image: "https://images.unsplash.com/photo-1585386959984-a4155224a1ad?w=300", excerpt: "Discover the rich tradition...", date: "Dec 10, 2024" },
        { title: "Layering Scents: A Beginner's Guide", image: "https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?w=300", excerpt: "Create your signature scent...", date: "Dec 5, 2024" }
    ];

    const container = document.getElementById('blogPosts');
    if (container) {
        container.innerHTML = blogPosts.map(post => `
            <div class="product-card" onclick="window.location.href='blog.html'">
                <img class="product-img" src="${post.image}" style="height: 200px;">
                <div class="product-info">
                    <div class="product-title">${escapeHtml(post.title)}</div>
                    <p style="font-size: 0.8rem; color: #666; margin: 0.5rem 0;">${escapeHtml(post.excerpt)}</p>
                    <small style="color: var(--text-light);">📅 ${post.date}</small>
                    <button class="btn btn-primary" style="width:100%; margin-top: 10px;">Read More →</button>
                </div>
            </div>
        `).join('');
    }
}

function addToCart(productId) {
    // CHECK LOGIN REQUIREMENT
    if (!requireLogin('add items to cart')) {
        return;
    }

    const product = products.find(p => p.id === productId);
    if (!product) return;

    if (product.stock === 0) {
        showToast('Sorry, out of stock!');
        return;
    }

    let cart = JSON.parse(localStorage.getItem('attar_cart')) || [];
    let item = cart.find(i => i.id === productId);

    if (item) {
        if (item.quantity < product.stock) {
            item.quantity++;
        } else {
            showToast('Not enough stock!');
            return;
        }
    } else {
        cart.push({ id: productId, quantity: 1 });
    }

    localStorage.setItem('attar_cart', JSON.stringify(cart));
    updateNavCartCount();

    let points = parseInt(localStorage.getItem('loyalty_points')) || 0;
    points += Math.floor(product.price / 100);
    localStorage.setItem('loyalty_points', points);
    updateNavPointsCount();

    showToast(`${product.name} added to cart! +${Math.floor(product.price / 100)} points`);
}

function addToWishlist(productId) {
    // CHECK LOGIN REQUIREMENT
    if (!requireLogin('add items to wishlist')) {
        return;
    }

    const product = products.find(p => p.id === productId);
    let wishlist = JSON.parse(localStorage.getItem('attar_wishlist')) || [];

    if (!wishlist.includes(productId)) {
        wishlist.push(productId);
        localStorage.setItem('attar_wishlist', JSON.stringify(wishlist));
        updateNavWishlistCount();
        showToast(`${product.name} added to wishlist! ❤️`);
    } else {
        showToast('Already in wishlist');
    }
}

function addToCompare(productId) {
    // CHECK LOGIN REQUIREMENT
    if (!requireLogin('compare products')) {
        return;
    }

    let compareList = JSON.parse(localStorage.getItem('attar_compare')) || [];
    if (!compareList.includes(productId) && compareList.length < 4) {
        compareList.push(productId);
        localStorage.setItem('attar_compare', JSON.stringify(compareList));
        showToast('Added to compare!');
    } else if (compareList.length >= 4) {
        showToast('Can compare up to 4 products only');
    } else {
        showToast('Already in compare list');
    }
}

function viewProduct(id) {
    localStorage.setItem('viewProductId', id);
    window.location.href = 'products.html';
}

function startCountdown() {
    const saleEnd = new Date();
    saleEnd.setDate(saleEnd.getDate() + 3);

    function updateCountdown() {
        const now = new Date();
        const diff = saleEnd - now;

        if (diff <= 0) {
            const countdownDiv = document.getElementById('countdown');
            if (countdownDiv) countdownDiv.innerHTML = 'SALE ENDED!';
            return;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (86400000)) / 3600000);
        const minutes = Math.floor((diff % 3600000) / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);

        const countdownDiv = document.getElementById('countdown');
        if (countdownDiv) {
            countdownDiv.innerHTML = `
                <span class="countdown-item">${days}d</span>
                <span class="countdown-item">${hours}h</span>
                <span class="countdown-item">${minutes}m</span>
                <span class="countdown-item">${seconds}s</span>
            `;
        }
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
}

// Initialize AOS
AOS.init({ duration: 800, once: true });

// Load data on page load
document.addEventListener('DOMContentLoaded', function () {
    loadIndexData();
});

// Make functions global
window.addToCart = addToCart;
window.addToWishlist = addToWishlist;
window.addToCompare = addToCompare;
window.viewProduct = viewProduct;