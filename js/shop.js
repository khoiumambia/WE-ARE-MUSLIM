// Shop Page JavaScript
let products = [];
let cart = [];
let wishlist = [];
let appliedCoupon = null;
let currentStockProduct = null;
let currentPage = 1;
const productsPerPage = 12;

const coupons = [
    { code: "WELCOME10", discount: 10, minAmount: 500 },
    { code: "RAMADAN20", discount: 20, minAmount: 1000 },
    { code: "FREESHIP", discount: 5, minAmount: 2000 },
    { code: "EIDSPECIAL", discount: 25, minAmount: 3000 }
];

// Load products from Supabase via Netlify Function
async function loadShopData() {
    try {
        // Show loading state
        const grid = document.getElementById('productGrid');
        if (grid) {
            grid.innerHTML = '<div class="loading">Loading products from database...</div>';
        }
        
        // Fetch products from Netlify Function
        const response = await fetch('/.netlify/functions/get-products');
        const data = await response.json();
        
        // Extract products (API returns { success: true, count: 40, products: [...] })
        if (data.success && data.products) {
            products = data.products;
        } else if (Array.isArray(data)) {
            products = data;
        } else {
            products = [];
        }
        
        console.log(`Loaded ${products.length} products from database`);
        
        // Store products in localStorage as backup
        localStorage.setItem('attar_products', JSON.stringify(products));
        
        // Only load cart and wishlist if user is logged in
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
        console.error('Error loading products from API:', error);
        
        // Fallback to localStorage if API fails
        const stored = localStorage.getItem('attar_products');
        if (stored) {
            products = JSON.parse(stored);
        } else {
            // Fallback default products
            products = [
                { id: 1, name: "Oudh Royal", brand: "Arabian Oud", fragrance: "Woody Oud", price: 1200, stock: 15, image: "https://images.unsplash.com/photo-1594035910387-fea47794261f?w=300", ratings: 4.8, reviews: 124 },
                { id: 2, name: "Musk Al Mahabba", brand: "Swiss Arabian", fragrance: "Musk", price: 850, stock: 0, image: "https://images.unsplash.com/photo-1585386959984-a4155224a1ad?w=300", ratings: 4.6, reviews: 89 },
                { id: 3, name: "Rose De Makkah", brand: "Al Haramain", fragrance: "Rose", price: 1500, stock: 3, image: "https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?w=300", ratings: 4.9, reviews: 56 },
                { id: 4, name: "Amber Night", brand: "Rasasi", fragrance: "Amber", price: 2200, stock: 20, image: "https://images.unsplash.com/photo-1595428774223-ef52624120d2?w=300", ratings: 4.7, reviews: 203 },
                { id: 5, name: "Sandalwood Classic", brand: "Ajmal", fragrance: "Sandalwood", price: 990, stock: 8, image: "https://images.unsplash.com/photo-1594035910387-fea47794261f?w=300", ratings: 4.5, reviews: 67 }
            ];
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
        showToast('Using offline product data');
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

    // Sorting
    if (sortBy === 'price_asc') filtered.sort((a, b) => a.price - b.price);
    else if (sortBy === 'price_desc') filtered.sort((a, b) => b.price - a.price);
    else if (sortBy === 'rating') filtered.sort((a, b) => b.ratings - a.ratings);

    const productCountSpan = document.getElementById('productCount');
    if (productCountSpan) {
        productCountSpan.innerHTML = `Showing ${filtered.length} of ${products.length} products`;
    }

    // Pagination
    const start = 0;
    const end = productsPerPage * currentPage;
    const paginated = filtered.slice(start, end);
    const hasMore = end < filtered.length;

    const loadMoreBtn = document.getElementById('loadMoreBtn');
    if (loadMoreBtn) {
        loadMoreBtn.style.display = hasMore ? 'block' : 'none';
    }

    const grid = document.getElementById('productGrid');
    if (!grid) return;

    if (paginated.length === 0) {
        grid.innerHTML = '<div style="text-align: center; padding: 3rem;">No products found 😢</div>';
        return;
    }

    const isLoggedIn = getCurrentUser() !== null;

    grid.innerHTML = paginated.map(p => `
        <div class="product-card">
            ${p.stock === 0 ? '<div class="product-badge sold-out">Sold Out</div>' : ''}
            <div class="product-actions">
                <button onclick="event.stopPropagation(); addToWishlist(${p.id})"><i class="far fa-heart"></i></button>
                <button onclick="event.stopPropagation(); addToCompare(${p.id})"><i class="fas fa-chart-line"></i></button>
            </div>
            <img class="product-img" src="${p.image}" onclick="viewProduct(${p.id})" onerror="this.src='https://via.placeholder.com/300x220?text=Attar'">
            <div class="product-info" onclick="viewProduct(${p.id})">
                <div class="product-title">${escapeHtml(p.name)}</div>
                <div style="font-size: 0.8rem; color: #666;">${escapeHtml(p.brand)} | ${escapeHtml(p.fragrance)}</div>
                <div class="product-price">৳${p.price.toLocaleString()}</div>
                <div class="stock-status ${p.stock > 10 ? 'in-stock' : (p.stock > 0 ? 'low-stock' : 'out-of-stock')}">
                    ${p.stock > 10 ? '✓ In Stock' : (p.stock > 0 ? `⚠ Only ${p.stock} left` : '✗ Out of Stock')}
                </div>
                <div style="font-size: 0.8rem;">⭐ ${p.ratings} (${p.reviews} reviews)</div>
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
    `).join('');
}

function loadMoreProducts() {
    currentPage++;
    renderProducts();
}

function addToCart(productId) {
    // CHECK LOGIN REQUIREMENT
    if (!requireLogin('add items to cart')) {
        return;
    }

    const product = products.find(p => p.id === productId);
    if (!product) return;

    if (product.stock === 0) {
        showStockAlert(productId);
        return;
    }

    // Load current cart from localStorage
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
        currentCart.push({ id: productId, quantity: 1 });
    }

    // Save back to localStorage
    localStorage.setItem('attar_cart', JSON.stringify(currentCart));
    
    // Update the local cart variable
    cart = currentCart;
    
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

function viewProduct(productId) {
    localStorage.setItem('viewProductId', productId);
    window.location.href = 'products.html';
}

function showStockAlert(productId) {
    currentStockProduct = products.find(p => p.id === productId);
    const modal = document.getElementById('stockModal');
    const productNameSpan = document.getElementById('stockProductName');
    if (productNameSpan) productNameSpan.innerText = currentStockProduct.name;
    if (modal) modal.classList.add('open');
}

function closeStockModal() {
    const modal = document.getElementById('stockModal');
    if (modal) modal.classList.remove('open');
    const emailInput = document.getElementById('stockEmail');
    if (emailInput) emailInput.value = '';
}

function saveStockAlert() {
    const email = document.getElementById('stockEmail').value;
    if (!email || !email.includes('@')) {
        showToast('Please enter a valid email address');
        return;
    }

    let waitlist = JSON.parse(localStorage.getItem('stock_waitlist')) || [];
    waitlist.push({
        productId: currentStockProduct.id,
        productName: currentStockProduct.name,
        email: email,
        date: new Date().toISOString(),
        notified: false
    });
    localStorage.setItem('stock_waitlist', JSON.stringify(waitlist));

    showToast(`We'll notify you when ${currentStockProduct.name} is back in stock!`);
    closeStockModal();
}

// Event listeners
document.addEventListener('DOMContentLoaded', function () {
    loadShopData();

    const applyFiltersBtn = document.getElementById('applyFilters');
    if (applyFiltersBtn) applyFiltersBtn.addEventListener('click', () => {
        currentPage = 1;
        renderProducts();
    });

    const resetFiltersBtn = document.getElementById('resetFilters');
    if (resetFiltersBtn) resetFiltersBtn.addEventListener('click', () => {
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

        currentPage = 1;
        renderProducts();
    });

    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', () => {
            currentPage = 1;
            renderProducts();
        });
    }

    const priceSlider = document.getElementById('priceSlider');
    if (priceSlider) {
        priceSlider.addEventListener('input', (e) => {
            const priceValue = document.getElementById('priceValue');
            if (priceValue) priceValue.innerText = '৳' + e.target.value;
            renderProducts();
        });
    }
});

// Make functions global
window.addToCart = addToCart;
window.addToWishlist = addToWishlist;
window.addToCompare = addToCompare;
window.viewProduct = viewProduct;
window.showStockAlert = showStockAlert;
window.closeStockModal = closeStockModal;
window.saveStockAlert = saveStockAlert;
window.loadMoreProducts = loadMoreProducts;
