# 🚀 We Are Muslim

---

## 📌 Overview

**We Are Muslim** is a full-stack e-commerce web application for selling premium attars (alcohol-free perfume oils). It includes a complete storefront, order and coupon system, product reviews, a blog module, and an admin-manageable homepage — all built on PHP and MySQL.

---

## 👥 Group Details

* **Group Number:** 1
* **Course Name:** Database Management System
* **Instructor:** Fahmidur Rahman Sakib

### 🧑‍🤝‍🧑 Team Members

| Name              | ID          | Contribution        |
| ----------------- | ----------- | -------------------- |
| Mohammad Hanif     | 242-115-347 | Frontend             |
| Manzurul Ambia     | 242-115-327 | Database             |
| Sami Jamali        | 242-115-327 | Backend              |
| Joy Baktchi        | 242-115-325 | Debugger              |

---

## 🎯 Objective

Traditional attar buyers often rely on physical shops or unstructured online listings with no reliable ordering, tracking, or review system. **We Are Muslim** solves this by providing a dedicated, database-driven online store where customers can browse attars, read reviews, apply coupons, track orders, and read fragrance-related blog content — all in one place.

---

## ✨ Features

* ✅ Product catalog with brand, fragrance, price, stock, ratings, and reviews
* ✅ User accounts with roles (`admin` / `user`) and a loyalty tier system (Bronze → Platinum)
* ✅ Shopping cart & order placement with itemized order tracking (`orders`, `order_items`)
* ✅ Coupon system supporting percentage and fixed discounts, with usage limits and per-user tracking
* ✅ Product reviews and ratings submitted by customers
* ✅ Return & exchange request handling with admin approval workflow
* ✅ Blog module with admin-authored posts and user-submitted blog submissions (pending/approved/rejected)
* ✅ Fully CMS-driven homepage — hero section, featured/bestseller sections, newsletter, and footer text are all editable from the database (`homepage_content`)
* ✅ Homepage image carousel and feature-highlight sections managed via the database
* ✅ Admin dashboard capability for managing products, orders, blogs, and homepage content

---

## 🖼️ Project Preview

### 🔹 UI Screenshots

<img src="https://raw.githubusercontent.com/khoiumambia/WE-ARE-MUSLIM/main/uploads/Screenshot_2026-05-25_181646.png" width="700">

### 🔹 ER Diagram
<img src="https://raw.githubusercontent.com/khoiumambia/WE-ARE-MUSLIM/main/uploads/ChatGPT Image Aug 12, 2026, 02_13_27 PM.png" width="700">


---

## 🏗️ Tech Stack

**Frontend:**
Built with plain **HTML, CSS, and JavaScript**, giving full control over the storefront layout, product carousel, and interactive UI elements (cart, filters, reviews) without relying on a JS framework.

**Backend:**
Built with **PHP**, handling server-side logic such as user authentication, order processing, coupon validation, review submission, and blog/content management. Runs on a local **XAMPP** server (Apache + PHP + MySQL) at **localhost**.

**Database:**
**MySQL** database named `muslim`, consisting of 13 tables covering users, products, orders, order items, blogs, blog submissions, reviews, coupons, coupon usage, return requests, carousel slides, features, and homepage content. Foreign keys link orders to users and products, order items to orders/products, reviews to products/users, coupon usage to coupons/orders, ensuring referential integrity with `ON DELETE CASCADE` / `ON DELETE SET NULL` rules where appropriate.

---

## ⚙️ Installation & Setup

```bash
# Clone the repository
git clone https://github.com/khoiumambia/WE-ARE-MUSLIM.git

# Move the project into your XAMPP htdocs folder
# (or copy it directly there)

# Start Apache and MySQL from the XAMPP Control Panel

# Import the database
# Open phpMyAdmin -> Create/Select the "muslim" database -> Import setup.sql

# Run the project in your browser
http://localhost/WE-ARE-MUSLIM/
```

---

## 🗂️ Project Structure

```
WE-ARE-MUSLIM/
│
├── api/                        # API endpoint scripts
├── css/                        # Stylesheets
├── js/                         # Client-side JavaScript
├── uploads/                    # Product, blog, and carousel images
│
├── config.php                  # Database connection config
├── setup.sql                   # Database schema & seed data
│
├── index.html                  # Homepage
├── shop.html                   # Product listing page
├── products.html                # Product detail page
├── cart.html                   # Shopping cart
├── payment.html                # Checkout / payment
├── wishlist.html                # Wishlist page
├── compare.html                 # Product comparison
├── order-tracking.html          # Order tracking page
├── login.html / register.php    # Auth pages
├── forgot-password.php
├── dashboard.html               # User dashboard
├── about.html / contact.html
├── blog.html / blog-post.html / blog-submit.html
│
├── admin-dashboard.html         # Admin panel
├── admin-products.html
├── admin-orders.html
├── admin-users.html
├── admin-blog.html
├── admin-coupons.html
├── admin-returns.html
├── admin-homepage.html
├── admin-settings.html
│
├── login.php                    # Auth logic
├── quick-login.php
├── register.php
├── create-order.php             # Order processing
├── get-orders.php / get-order.php / update-order.php
├── get-products.php / get-product.php / add-product.php / update-product.php / delete-product.php
├── get-users.php / get-user.php / update-user.php / delete-user.php / update-user-tier.php / get-user-tier.php
├── get-blogs.php / get-blog.php / add-blog.php / update-blog.php / delete-blog.php
├── submit-blog.php / approve-blog.php / get-blog-submissions.php
├── submit-review.php / get-reviews.php / generate-reviews.php
├── coupons.php
├── return-request.php
├── update-carousel.php / update-feature.php / update-homepage.php / get-homepage.php
├── upload-image.php / compress-image.php / fix-images.php / check-uploads.php
├── update-stock.php
├── generate-hash.php / reset-database.php
├── index.js / navigation.js / shop.js / utils.js / style.css
│
└── README.md
```

---

## 🎥 Demo Video

👉 [Watch Project Demo](#) *(Add your video link here)*

---
