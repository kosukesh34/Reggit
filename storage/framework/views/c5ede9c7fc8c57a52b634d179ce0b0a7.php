<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>モバイルオーダー</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(asset('css/style.css')); ?>">
</head>
<body>
    <div class="app-container">
        
        <header class="header">
            <div class="header-content">
                <h1 class="logo">モバイルオーダー</h1>
                <button class="cart-btn" id="cartBtn">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </button>
            </div>
        </header>

        
        <main class="main-content">
            
            <div class="tab-content active" id="productsTab">
                
                <div class="category-tabs">
                    <button class="tab-btn active" data-category="all">すべて</button>
                    <button class="tab-btn" data-category="food">フード</button>
                    <button class="tab-btn" data-category="drink">ドリンク</button>
                    <button class="tab-btn" data-category="side">サイドメニュー</button>
                </div>

                
                <div class="products-section">
                    <div class="products-grid" id="productsGrid">
                    </div>
                </div>
            </div>

            
            <div class="tab-content" id="memberTab">
                <div class="member-section" id="memberSection">
                </div>
            </div>
        </main>

        
        <div class="cart-sidebar" id="cartSidebar">
            <div class="cart-header">
                <h2><i class="fas fa-shopping-bag"></i> 注文内容</h2>
                <button class="close-btn" id="closeCartBtn"><i class="fas fa-times"></i></button>
            </div>
            <div class="cart-items" id="cartItems">
                <div class="empty-cart">カートは空です</div>
            </div>
            <div class="cart-footer">
                <div class="cart-total">
                    <span>合計: </span>
                    <span class="total-price" id="totalPrice">¥0</span>
                </div>
                <button class="order-btn" id="orderBtn" disabled><i class="fas fa-check-circle"></i> 注文する</button>
            </div>
        </div>

        
        <div class="overlay" id="overlay"></div>
        
        <footer class="main-tabs">
            <button class="main-tab-btn active" data-tab="products">
                <i class="fas fa-utensils"></i>
                <span>商品</span>
            </button>
            <button class="main-tab-btn" data-tab="member">
                <i class="fas fa-id-card"></i>
                <span>会員証</span>
            </button>
        </footer>
    </div>

    <meta name="stripe-key" content="<?php echo e(env('STRIPE_KEY', '')); ?>">
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="<?php echo e(asset('js/constants.js')); ?>"></script>
    <script src="<?php echo e(asset('js/utils/apiClient.js')); ?>"></script>
    <script src="<?php echo e(asset('js/utils/domHelper.js')); ?>"></script>
    <script src="<?php echo e(asset('js/managers/CartManager.js')); ?>"></script>
    <script src="<?php echo e(asset('js/managers/ProductManager.js')); ?>"></script>
    <script src="<?php echo e(asset('js/stripe.js')); ?>"></script>
    <script src="<?php echo e(asset('js/app.refactored.js')); ?>"></script>
</body>
</html>

<?php /**PATH /var/www/html/resources/views/index.blade.php ENDPATH**/ ?>