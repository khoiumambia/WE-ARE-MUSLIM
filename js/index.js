// Index page specific JavaScript
let products = [];
let wishlist = [];
let cart = [];
let blogPosts = [];
let homepageData = {};
let carouselSlides = [];
let features = [];

// Load all data from APIs
async function loadIndexData() {
    try {
        const featuredGrid = document.getElementById('featuredProducts');
        const bestSellersGrid = document.getElementById('bestSellers');
        const blogGrid = document.getElementById('blogPosts');
        const featuresGrid = document.getElementById('featuresGrid');
        const heroCarousel = document.getElementById('heroCarousel');
        
        if (featuredGrid) featuredGrid.innerHTML = '<div class="loading">Loading products...</div>';
        if (bestSellersGrid) bestSellersGrid.innerHTML = '<div class="loading">Loading products...</div>';
        if (blogGrid) blogGrid.innerHTML = '<div class="loading">Loading articles...</div>';
        if (heroCarousel) heroCarousel.classList.add('loading');
        
        await Promise.all([
            loadProducts(),
            loadBlogPosts(),
            loadHomepageContent()
        ]);
        
        products.forEach((p, index) => { p.isBestSeller = index < 4; });
        
        const currentUser = getCurrentUser();
        if (currentUser) {
            wishlist = JSON.parse(localStorage.getItem('attar_wishlist')) || [];
            cart = JSON.parse(localStorage.getItem('attar_cart')) || [];
        }
        
        displayFeatured();
        displayBestSellers();
        displayBlogPosts();
        displayFeatures();
        updateCarousel();
        updateTextContent();
        updateSaleBanner();
        startCountdown();
        updateCartCount();
        updateWishlistCount();
        
    } catch (error) {
        console.error('Error loading index data:', error);
        displayFeatured();
        displayBestSellers();
        displayBlogPosts();
        startCountdown();
        updateCartCount();
        updateWishlistCount();
    }
}

async function loadProducts() {
    try {
        const response = await fetch('api/get-products.php');
        const data = await response.json();
        if (data.success) {
            products = data.products;
            console.log(`Loaded ${products.length} products from database`);
        } else {
            products = [];
        }
    } catch (error) {
        console.error('Error loading products:', error);
        products = [];
    }
}

async function loadBlogPosts() {
    try {
        const response = await fetch('api/get-blogs.php');
        const data = await response.json();
        if (data.success) {
            blogPosts = data.blogs.filter(post => post.status === 'published');
            console.log(`Loaded ${blogPosts.length} blog posts`);
        } else {
            blogPosts = [];
        }
    } catch (error) {
        console.error('Error loading blog posts:', error);
        blogPosts = [];
    }
}

async function loadHomepageContent() {
    try {
        const response = await fetch('api/get-homepage.php');
        const data = await response.json();
        if (data.success) {
            homepageData = data;
            carouselSlides = data.carousel || [];
            features = data.features || [];
            console.log(`Loaded ${carouselSlides.length} carousel slides, ${features.length} features`);
        }
    } catch (error) {
        console.error('Error loading homepage content:', error);
    }
}

function displayFeatured() {
    const featured = products.slice(0, 4);
    const grid = document.getElementById('featuredProducts');
    if (grid && featured.length > 0) {
        grid.innerHTML = featured.map(p => renderProductCard(p)).join('');
    } else if (grid) {
        grid.innerHTML = '<div class="loading">No products available</div>';
    }
}

function displayBestSellers() {
    const bestSellers = products.filter(p => p.isBestSeller).slice(0, 4);
    const grid = document.getElementById('bestSellers');
    if (grid && bestSellers.length > 0) {
        grid.innerHTML = bestSellers.map(p => renderProductCard(p)).join('');
    } else if (grid) {
        grid.innerHTML = '<div class="loading">No products available</div>';
    }
}

function renderProductCard(p) {
    const isLoggedIn = getCurrentUser() !== null;
    let imageUrl = p.image;
    if (!imageUrl || imageUrl === '') {
        imageUrl = 'https://via.placeholder.com/300x250?text=' + encodeURIComponent(p.name);
    }
    
    return `
        <div class="product-card" onclick="viewProduct(${p.id})">
            ${p.isBestSeller ? '<div class="product-badge">🔥 Best Seller</div>' : ''}
            <div class="product-actions">
                <button onclick="event.stopPropagation(); addToWishlist(${p.id})"><i class="far fa-heart"></i></button>
                <button onclick="event.stopPropagation(); addToCompare(${p.id})"><i class="fas fa-chart-line"></i></button>
            </div>
            <img class="product-img" src="${imageUrl}" loading="lazy" onerror="this.src='https://via.placeholder.com/300x250?text=Attar'">
            <div class="product-info">
                <div class="product-title">${escapeHtml(p.name)}</div>
                <div style="font-size: 0.8rem; color: #666;">${escapeHtml(p.brand)} | ${escapeHtml(p.fragrance)}</div>
                <div class="product-price">৳${p.price.toLocaleString()}</div>
                <div style="font-size: 0.8rem;">⭐ ${p.ratings || 0} (${p.reviews || 0} reviews)</div>
                <button class="btn btn-primary" style="width:100%; margin-top: 10px;" onclick="event.stopPropagation(); addToCart(${p.id})">
                    <i class="fas fa-shopping-cart"></i> ${isLoggedIn ? 'Add to Cart' : 'Login to Add to Cart'}
                </button>
            </div>
        </div>
    `;
}

function displayBlogPosts() {
    const container = document.getElementById('blogPosts');
    if (!container) return;
    
    const latestPosts = blogPosts.slice(0, 3);
    
    if (latestPosts.length === 0) {
        const blogSection = document.querySelector('.section:has(#blogPosts)');
        if (blogSection) {
            blogSection.style.display = 'none';
        }
        return;
    }
    
    const blogSection = document.querySelector('.section:has(#blogPosts)');
    if (blogSection) {
        blogSection.style.display = 'block';
    }
    
    container.innerHTML = latestPosts.map(post => `
        <div class="product-card" onclick="viewBlogPost(${post.id})">
            <img class="product-img" src="${post.image || 'https://via.placeholder.com/300x200?text=Blog'}" loading="lazy" style="height: 200px;" onerror="this.src='https://via.placeholder.com/300x200?text=Blog'">
            <div class="product-info">
                <div class="product-title">${escapeHtml(post.title)}</div>
                <p style="font-size: 0.8rem; color: #666;">${escapeHtml(post.excerpt?.substring(0, 100) || '')}...</p>
                <small>📅 ${formatDate(post.created_at)} | 📖 ${post.read_time || 5} min read</small>
                <button class="btn btn-primary" style="width:100%; margin-top: 10px;">Read More →</button>
            </div>
        </div>
    `).join('');
}

function displayFeatures() {
    const container = document.getElementById('featuresGrid');
    if (!container) return;
    
    // If no features from database, show default clean features
    if (!features || features.length === 0) {
        container.innerHTML = `
            <div class="feature-item" onclick="window.location.href='shop.html'">
                <i class="fas fa-gem"></i>
                <h3>Premium Quality</h3>
                <p>100% natural attars, alcohol-free</p>
            </div>
            <div class="feature-item" onclick="window.location.href='shop.html'">
                <i class="fas fa-truck"></i>
                <h3>Free Shipping</h3>
                <p>Free delivery on orders over ৳10000</p>
            </div>
            <div class="feature-item" onclick="window.location.href='shop.html'">
                <i class="fas fa-gift"></i>
                <h3>Gift Ready</h3>
                <p>Beautiful gift packaging available</p>
            </div>
            <div class="feature-item" onclick="window.location.href='shop.html'">
                <i class="fas fa-shield-alt"></i>
                <h3>100% Authentic</h3>
                <p>Premium quality guaranteed</p>
            </div>
            <div class="feature-item" onclick="window.location.href='order-tracking.html'">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Order Tracking</h3>
                <p>Real-time order updates</p>
            </div>
            <div class="feature-item" onclick="window.location.href='contact.html'">
                <i class="fas fa-headset"></i>
                <h3>24/7 Support</h3>
                <p>Customer care always ready</p>
            </div>
        `;
        return;
    }
    
    // If features exist in database, use them
    container.innerHTML = features.map(f => `
        <div class="feature-item" onclick="window.location.href='${f.link || '#'}'">
            <i class="${f.icon}"></i>
            <h3>${escapeHtml(f.title)}</h3>
            <p>${escapeHtml(f.description || '')}</p>
        </div>
    `).join('');
}

function updateCarousel() {
    const container = document.getElementById('heroCarousel');
    if (!container) return;
    
    if (!carouselSlides.length) {
        container.innerHTML = `
            <div class="carousel-slide active" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1594035910387-fea47794261f?w=1200'); background-size: cover; background-position: center;">
                <div class="carousel-content">
                    <h1>Premium Attars & Fragrances</h1>
                    <p>Discover the finest collection of traditional and modern attars</p>
                    <a href="shop.html" class="btn btn-primary">Shop Now</a>
                </div>
            </div>
            <button class="carousel-btn prev" onclick="window.changeSlide(-1)">❮</button>
            <button class="carousel-btn next" onclick="window.changeSlide(1)">❯</button>
        `;
        container.classList.remove('loading');
        return;
    }
    
    container.classList.add('loading');
    const existingSlides = container.querySelectorAll('.carousel-slide');
    existingSlides.forEach(slide => slide.remove());
    
    carouselSlides.forEach((slide, index) => {
        const slideDiv = document.createElement('div');
        slideDiv.className = 'carousel-slide';
        if (index === 0) slideDiv.classList.add('active');
        slideDiv.setAttribute('data-bg', slide.image);
        slideDiv.innerHTML = `
            <div class="carousel-content">
                <h1>${escapeHtml(slide.title)}</h1>
                <p>${escapeHtml(slide.subtitle || '')}</p>
                ${slide.button_text ? `<a href="${slide.button_link || 'shop.html'}" class="btn btn-primary">${slide.button_text}</a>` : ''}
            </div>
        `;
        container.appendChild(slideDiv);
    });
    
    if (!container.querySelector('.carousel-btn')) {
        const prevBtn = document.createElement('button');
        prevBtn.className = 'carousel-btn prev';
        prevBtn.innerHTML = '❮';
        prevBtn.onclick = () => window.changeSlide(-1);
        
        const nextBtn = document.createElement('button');
        nextBtn.className = 'carousel-btn next';
        nextBtn.innerHTML = '❯';
        nextBtn.onclick = () => window.changeSlide(1);
        
        container.appendChild(prevBtn);
        container.appendChild(nextBtn);
    }
    
    const loadImage = (slide, isPriority = false) => {
        const bgUrl = slide.getAttribute('data-bg');
        if (bgUrl && !slide.style.backgroundImage) {
            const img = new Image();
            img.onload = () => {
                slide.style.backgroundImage = `linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('${bgUrl}')`;
                if (isPriority) container.classList.remove('loading');
            };
            img.onerror = () => { if (isPriority) container.classList.remove('loading'); };
            img.src = bgUrl;
        }
    };
    
    const activeSlide = document.querySelector('.carousel-slide.active');
    if (activeSlide) loadImage(activeSlide, true);
    setTimeout(() => {
        document.querySelectorAll('.carousel-slide').forEach(slide => {
            if (!slide.style.backgroundImage) loadImage(slide, false);
        });
    }, 1000);
    
    window.currentSlide = 0;
    window.carouselSlidesList = document.querySelectorAll('.carousel-slide');
}

function updateTextContent() {
    if (!homepageData.sections) return;
    const sections = homepageData.sections;
    
    const heroTitle = sections.find(s => s.section === 'hero_title');
    const heroSubtitle = sections.find(s => s.section === 'hero_subtitle');
    const heroButton = sections.find(s => s.section === 'hero_button');
    const featuredTitle = sections.find(s => s.section === 'featured_title');
    const featuredSubtitle = sections.find(s => s.section === 'featured_subtitle');
    const featuredButton = sections.find(s => s.section === 'featured_button_text');
    const bestsellerTitle = sections.find(s => s.section === 'bestseller_title');
    const bestsellerSubtitle = sections.find(s => s.section === 'bestseller_subtitle');
    const bestsellerButton = sections.find(s => s.section === 'bestseller_button_text');
    const newsletterTitle = sections.find(s => s.section === 'newsletter_title');
    const newsletterText = sections.find(s => s.section === 'newsletter_content');
    const footerTitle = sections.find(s => s.section === 'footer_text');
    const footerSubtitle = sections.find(s => s.section === 'footer_subtitle');
    const copyright = sections.find(s => s.section === 'footer_copyright');
    
    const heroTitleEl = document.getElementById('heroTitle');
    const heroSubtitleEl = document.getElementById('heroSubtitle');
    const heroButtonEl = document.getElementById('heroButton');
    if (heroTitleEl && heroTitle) heroTitleEl.innerText = heroTitle.title;
    if (heroSubtitleEl && heroSubtitle) heroSubtitleEl.innerText = heroSubtitle.subtitle;
    if (heroButtonEl && heroButton) heroButtonEl.innerText = heroButton.title;
    
    const featuredTitleEl = document.getElementById('featuredSectionTitle');
    const featuredSubtitleEl = document.getElementById('featuredSubtitle');
    const featuredButtonEl = document.getElementById('featuredButton');
    if (featuredTitleEl && featuredTitle) featuredTitleEl.innerHTML = featuredTitle.title;
    if (featuredSubtitleEl && featuredSubtitle) featuredSubtitleEl.innerText = featuredSubtitle.subtitle;
    if (featuredButtonEl && featuredButton) featuredButtonEl.innerText = featuredButton.title;
    
    const bestsellerTitleEl = document.getElementById('bestsellerSectionTitle');
    const bestsellerSubtitleEl = document.getElementById('bestsellerSubtitle');
    const bestsellerButtonEl = document.getElementById('bestsellerButton');
    if (bestsellerTitleEl && bestsellerTitle) bestsellerTitleEl.innerHTML = bestsellerTitle.title;
    if (bestsellerSubtitleEl && bestsellerSubtitle) bestsellerSubtitleEl.innerText = bestsellerSubtitle.subtitle;
    if (bestsellerButtonEl && bestsellerButton) bestsellerButtonEl.innerText = bestsellerButton.title;
    
    const newsletterTitleEl = document.getElementById('newsletterTitle');
    const newsletterTextEl = document.getElementById('newsletterText');
    if (newsletterTitleEl && newsletterTitle) newsletterTitleEl.innerHTML = newsletterTitle.title;
    if (newsletterTextEl && newsletterText) newsletterTextEl.innerHTML = newsletterText.content;
    
    const footerTitleEl = document.getElementById('footerTitle');
    const footerTextEl = document.getElementById('footerText');
    const copyrightEl = document.getElementById('copyright');
    if (footerTitleEl && footerTitle) footerTitleEl.innerHTML = footerTitle.title;
    if (footerTextEl && footerSubtitle) footerTextEl.innerHTML = footerSubtitle.subtitle;
    if (copyrightEl && copyright) copyrightEl.innerHTML = copyright.title;
}

function updateSaleBanner() {
    if (!homepageData.sections) return;
    const saleEnabled = homepageData.sections.find(s => s.section === 'sale_enabled');
    const saleTitle = homepageData.sections.find(s => s.section === 'sale_title');
    const saleDiscount = homepageData.sections.find(s => s.section === 'sale_discount');
    const saleEndDate = homepageData.sections.find(s => s.section === 'sale_end_date');
    
    const flashSaleDiv = document.querySelector('.flash-sale');
    if (!flashSaleDiv) return;
    
    if (saleEnabled && saleEnabled.content === '1') {
        flashSaleDiv.style.display = 'block';
        if (saleTitle) {
            flashSaleDiv.innerHTML = `<i class="fas fa-bolt"></i> ${saleTitle.title} <span id="saleDiscount">${saleDiscount?.title || '25% OFF'}</span> on all Oudh Collections!<div class="countdown" id="countdown"></div>`;
        }
        if (saleEndDate && saleEndDate.content) {
            window.SALE_END_DATE = new Date(saleEndDate.content);
        } else {
            window.SALE_END_DATE = new Date();
            window.SALE_END_DATE.setDate(window.SALE_END_DATE.getDate() + 3);
        }
        startCountdown();
    } else {
        flashSaleDiv.style.display = 'none';
    }
}

window.SALE_END_DATE = new Date();
window.SALE_END_DATE.setDate(window.SALE_END_DATE.getDate() + 3);

function startCountdown() {
    const saleEnd = window.SALE_END_DATE;
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
            countdownDiv.innerHTML = `<span class="countdown-item">${days}d</span><span class="countdown-item">${hours}h</span><span class="countdown-item">${minutes}m</span><span class="countdown-item">${seconds}s</span>`;
        }
    }
    updateCountdown();
    setInterval(updateCountdown, 1000);
}

function viewBlogPost(id) {
    localStorage.setItem('viewPostId', id);
    window.location.href = 'blog-post.html';
}

// FIXED: addToCart with tier discount only (no points)
async function addToCart(productId) {
    if (!requireLogin('add items to cart')) return;
    
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
            showToast('Not enough stock available!');
            return;
        }
    } else {
        cart.push({ id: productId, quantity: 1 });
    }
    
    localStorage.setItem('attar_cart', JSON.stringify(cart));
    updateNavCartCount();
    showToast(`${product.name} added to cart!`);
}

function addToWishlist(productId) {
    if (!requireLogin('add items to wishlist')) return;
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
    if (!requireLogin('compare products')) return;
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

function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('attar_cart')) || [];
    const count = cart.reduce((sum, i) => sum + i.quantity, 0);
    const cartCountSpan = document.getElementById('cartCount');
    if (cartCountSpan) cartCountSpan.innerText = count;
}

function updateWishlistCount() {
    const wishlist = JSON.parse(localStorage.getItem('attar_wishlist')) || [];
    const wishlistSpan = document.getElementById('wishlistCount');
    if (wishlistSpan) wishlistSpan.innerText = wishlist.length;
}

window.currentSlide = 0;
window.carouselSlidesList = [];

window.changeSlide = function(direction) {
    const slides = document.querySelectorAll('.carousel-slide');
    if (!slides.length) return;
    slides[window.currentSlide].classList.remove('active');
    window.currentSlide = (window.currentSlide + direction + slides.length) % slides.length;
    slides[window.currentSlide].classList.add('active');
};

function initCarouselInterval() {
    setInterval(() => { window.changeSlide(1); }, 5000);
}

AOS.init({ duration: 800, once: true });

document.addEventListener('DOMContentLoaded', function () {
    loadIndexData();
    initCarouselInterval();
});

window.addToCart = addToCart;
window.addToWishlist = addToWishlist;
window.addToCompare = addToCompare;
window.viewProduct = viewProduct;
window.viewBlogPost = viewBlogPost;