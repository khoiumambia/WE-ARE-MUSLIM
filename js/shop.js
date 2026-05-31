// Shop Page JavaScript
let products = [];
let cart = [];
let wishlist = [];
let appliedCoupon = null;
let currentStockProduct = null;

const coupons = [
    { code: "WELCOME10", discount: 10, minAmount: 500, type: "percent" },
    { code: "RAMADAN20", discount: 20, minAmount: 1000, type: "percent" },
    { code: "FREESHIP", discount: 60, minAmount: 2000, type: "fixed" },
    { code: "EIDSPECIAL", discount: 25, minAmount: 3000, type: "percent" }
];

// Helper function to get proper image URL
function getImageUrl(imagePath) {
    if (!imagePath) return 'https://placehold.co/300x250/e8ddd3/8B5E3C?text=No+Image';
    
    if (imagePath.startsWith('data:')) {
        return imagePath;
    }
    
    if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
        return imagePath;
    }
    
    if (imagePath.startsWith('/uploads/')) {
        return imagePath;
    }
    
    if (imagePath.startsWith('uploads/')) {
        return imagePath;
    }
    
    if (imagePath.match(/\.(jpg|jpeg|png|gif|webp)$/i)) {
        return 'uploads/' + imagePath;
    }
    
    return 'https://placehold.co/300x250/e8ddd3/8B5E3C?text=' + encodeURIComponent(imagePath.substring(0, 20));
}

// Load products from MySQL database via PHP API
async function loadShopData() {
    try {
        const grid = document.getElementById('productGrid');
        if (grid) {
            grid.innerHTML = '<div class="loading">Loading products from database...</div>';
        }
        
        const response = await fetch('api/get-products.php');
        const data = await response.json();
        
        if (data.success) {
            products = data.products;
            console.log(`Loaded ${products.length} products from database`);
        } else {
            console.error('API error:', data.error);
            products = [];
        }
        
        const currentUser = getCurrentUser();
        if (currentUser) {
            cart = JSON.parse(localStorage.getItem('attar_cart')) || [];
            wishlist = JSON.parse(localStorage.getItem('attar_wishlist')) || [];
        } else {
            cart = [];
            wishlist = [];
        }
        
        updateFilters();
        renderProducts();
        
    } catch (error) {
        console.error('Error loading products:', error);
        showToast('Failed to load products');
        products = [];
        updateFilters();
        renderProducts();
    }
}

function updateFilters() {
    const brands = [...new Set(products.map(p => p.brand))];
    const fragrances = [...new Set(products.map(p => p.fragrance))];

    const brandSelect = document.getElementById('brandFilter');
    if (brandSelect) {
        brandSelect.innerHTML = '<option value="">All Brands</option>' +
            brands.map(b => `<option value="${b}">${b}</option>`).join('');
    }

    const fragranceSelect = document.getElementById('fragranceFilter');
    if (fragranceSelect) {
        fragranceSelect.innerHTML = '<option value="">All Fragrances</option>' +
            fragrances.map(f => `<option value="${f}">${f}</option>`).join('');
    }
}

function renderProducts() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const brand = document.getElementById('brandFilter')?.value || '';
    const fragrance = document.getElementById('fragranceFilter')?.value || '';
    const stockStatus = document.getElementById('stockFilter')?.value || 'all';
    const ratingFilter = document.getElementById('ratingFilter')?.value || 'all';
    const sortBy = document.getElementById('sortFilter')?.value || 'default';
    const maxPrice = parseInt(document.getElementById('priceSlider')?.value || 5000);

    let filtered = products.filter(p => {
        if (searchTerm && !p.name.toLowerCase().includes(searchTerm) && !p.brand.toLowerCase().includes(searchTerm)) return false;
        if (brand && p.brand !== brand) return false;
        if (fragrance && p.fragrance !== fragrance) return false;
        if (p.price > maxPrice) return false;
        if (stockStatus === 'in' && p.stock === 0) return false;
        if (stockStatus === 'low' && (p.stock === 0 || p.stock > 10)) return false;
        if (stockStatus === 'out' && p.stock > 0) return false;
        if (ratingFilter !== 'all' && p.ratings < parseInt(ratingFilter)) return false;
        return true;
    });

    if (sortBy === 'price_asc') filtered.sort((a, b) => a.price - b.price);
    else if (sortBy === 'price_desc') filtered.sort((a, b) => b.price - a.price);
    else if (sortBy === 'rating') filtered.sort((a, b) => b.ratings - a.ratings);

    const productCountSpan = document.getElementById('productCount');
    if (productCountSpan) {
        productCountSpan.innerHTML = `Showing ${filtered.length} of ${products.length} products`;
    }

    const grid = document.getElementById('productGrid');
    if (!grid) return;

    if (filtered.length === 0) {
        grid.innerHTML = '<div style="text-align: center; padding: 3rem;">No products found 😢</div>';
        return;
    }

    const isLoggedIn = getCurrentUser() !== null;

    grid.innerHTML = filtered.map(p => {
        const imageUrl = getImageUrl(p.image);
        return `
        <div class="product-card">
            ${p.stock === 0 ? '<div class="product-badge sold-out">Sold Out</div>' : ''}
            <div class="product-actions">
                <button onclick="event.stopPropagation(); addToWishlist(${p.id})"><i class="far fa-heart"></i></button>
                <button onclick="event.stopPropagation(); addToCompare(${p.id})"><i class="fas fa-chart-line"></i></button>
            </div>
            <img class="product-img" src="${imageUrl}" onclick="viewProduct(${p.id})" onerror="this.src='https://placehold.co/300x250/e8ddd3/8B5E3C?text=No+Image'">
            <div class="product-info" onclick="viewProduct(${p.id})">
                <div class="product-title">${escapeHtml(p.name)}</div>
                <div style="font-size: 0.8rem; color: #666;">${escapeHtml(p.brand)} | ${escapeHtml(p.fragrance)}</div>
                <div class="product-price">৳${parseFloat(p.price).toLocaleString()}</div>
                <div class="stock-status ${p.stock > 10 ? 'in-stock' : (p.stock > 0 ? 'low-stock' : 'out-of-stock')}">
                    ${p.stock > 10 ? '✓ In Stock' : (p.stock > 0 ? `⚠ Only ${p.stock} left` : '✗ Out of Stock')}
                </div>
                <div style="font-size: 0.8rem;">⭐ ${p.ratings || 0} (${p.reviews || 0} reviews)</div>
                ${p.stock > 0 ?
                    `<button class="btn btn-primary" style="width:100%; margin-top: 10px;" onclick="event.stopPropagation(); addToCart(${p.id})">
                        <i class="fas fa-shopping-cart"></i> ${isLoggedIn ? 'Add to Cart' : 'Login to Add to Cart'}
                    </button>` :
                    `<button class="btn" style="width:100%; margin-top: 10px; background: #6b7280; color: white;" onclick="event.stopPropagation(); showStockAlert(${p.id})">
                        <i class="fas fa-bell"></i> Notify Me
                    </button>`
                }
            </div>
        </div>
    `}).join('');
}

// FIXED: addToCart with NO loyalty points
function addToCart(productId) {
    if (!requireLogin('add items to cart')) return;
    
    const product = products.find(p => p.id === productId);
    if (!product) {
        showToast('Product not found');
        return;
    }
    
    if (product.stock === 0) { 
        showStockAlert(productId); 
        return; 
    }
    
    let currentCart = JSON.parse(localStorage.getItem('attar_cart')) || [];
    let item = currentCart.find(i => i.id === productId);
    
    if (item) {
        if (item.quantity < product.stock) {
            item.quantity++;
        } else {
            showToast('Not enough stock available!');
            return;
        }
    } else {
        currentCart.push({ 
            id: productId, 
            quantity: 1 
        });
    }
    
    localStorage.setItem('attar_cart', JSON.stringify(currentCart));
    
    // Update cart count in navigation
    if (typeof updateNavCartCount === 'function') {
        updateNavCartCount();
    }
    
    showToast(`${product.name} added to cart!`);
}

function addToWishlist(productId) {
    if (!requireLogin('add items to wishlist')) return;
    const product = products.find(p => p.id === productId);
    let currentWishlist = JSON.parse(localStorage.getItem('attar_wishlist')) || [];
    if (!currentWishlist.includes(productId)) {
        currentWishlist.push(productId);
        localStorage.setItem('attar_wishlist', JSON.stringify(currentWishlist));
        if (typeof updateNavWishlistCount === 'function') {
            updateNavWishlistCount();
        }
        showToast(`${product.name} added to wishlist! ❤️`);
    } else {
        showToast('Already in wishlist');
    }
}

function addToCompare(productId) {
    if (!requireLogin('compare products')) return;
    
    let compareList = JSON.parse(localStorage.getItem('attar_compare')) || [];
    
    if (compareList.includes(productId)) {
        showToast('Already in compare list');
        return;
    }
    
    if (compareList.length >= 4) {
        showToast('Can compare up to 4 products only');
        return;
    }
    
    compareList.push(productId);
    localStorage.setItem('attar_compare', JSON.stringify(compareList));
    showToast('Added to compare!');
}

function goToCompare() {
    const compareList = JSON.parse(localStorage.getItem('attar_compare')) || [];
    if (compareList.length === 0) {
        showToast('No products in compare list. Add some products first!');
        return;
    }
    window.location.href = 'compare.html';
}

function viewProduct(productId) {
    localStorage.setItem('viewProductId', productId);
    window.location.href = 'products.html';
}

function showStockAlert(productId) {
    currentStockProduct = products.find(p => p.id === productId);
    const email = prompt(`Notify me when ${currentStockProduct.name} is back in stock.\n\nEnter your email:`);
    if (email && email.includes('@')) {
        let waitlist = JSON.parse(localStorage.getItem('stock_waitlist')) || [];
        waitlist.push({
            productId: currentStockProduct.id,
            productName: currentStockProduct.name,
            email: email,
            date: new Date().toISOString(),
            notified: false
        });
        localStorage.setItem('stock_waitlist', JSON.stringify(waitlist));
        showToast(`We'll notify you when ${currentStockProduct.name} is back!`);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    loadShopData();

    document.getElementById('applyFilters')?.addEventListener('click', () => {
        renderProducts();
    });

    document.getElementById('resetFilters')?.addEventListener('click', () => {
        const brandFilter = document.getElementById('brandFilter');
        const fragranceFilter = document.getElementById('fragranceFilter');
        const stockFilter = document.getElementById('stockFilter');
        const ratingFilter = document.getElementById('ratingFilter');
        const sortFilter = document.getElementById('sortFilter');
        const priceSlider = document.getElementById('priceSlider');
        const searchInput = document.getElementById('searchInput');

        if (brandFilter) brandFilter.value = '';
        if (fragranceFilter) fragranceFilter.value = '';
        if (stockFilter) stockFilter.value = 'all';
        if (ratingFilter) ratingFilter.value = 'all';
        if (sortFilter) sortFilter.value = 'default';
        if (priceSlider) priceSlider.value = '5000';
        if (searchInput) searchInput.value = '';

        const priceValue = document.getElementById('priceValue');
        if (priceValue) priceValue.innerText = '৳5000';

        renderProducts();
    });

    document.getElementById('searchInput')?.addEventListener('keyup', () => {
        renderProducts();
    });

    document.getElementById('priceSlider')?.addEventListener('input', (e) => {
        const priceValue = document.getElementById('priceValue');
        if (priceValue) priceValue.innerText = '৳' + e.target.value;
        renderProducts();
    });
});

// Make functions globally available
window.addToCart = addToCart;
window.addToWishlist = addToWishlist;
window.addToCompare = addToCompare;
window.goToCompare = goToCompare;
window.viewProduct = viewProduct;
window.showStockAlert = showStockAlert;