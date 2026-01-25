<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>管理画面 - <?php echo $__env->yieldContent('title', 'Mobile Order'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(asset('css/admin/admin.css')); ?>">
    <script src="<?php echo e(asset('js/shared/utils/confirmDialog.js')); ?>"></script>
    <script src="<?php echo e(asset('js/admin/admin.js')); ?>"></script>
</head>
<body>
    <div class="admin-container">
        
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-cog"></i> 管理画面</h1>
            </div>
            <nav class="sidebar-nav">
                <a href="<?php echo e(route('admin.dashboard')); ?>" class="nav-item <?php echo e(request()->routeIs('admin.dashboard') ? 'active' : ''); ?>" data-tooltip="ダッシュボード">
                    <i class="fas fa-chart-line"></i>
                    <span>ダッシュボード</span>
                </a>
                <a href="<?php echo e(route('admin.products')); ?>" class="nav-item <?php echo e(request()->routeIs('admin.products*') ? 'active' : ''); ?>">
                    <i class="fas fa-hamburger"></i>
                    <span>商品管理</span>
                </a>
                <a href="<?php echo e(route('admin.orders')); ?>" class="nav-item <?php echo e(request()->routeIs('admin.orders*') ? 'active' : ''); ?>">
                    <i class="fas fa-shopping-bag"></i>
                    <span>注文管理</span>
                </a>
                <a href="<?php echo e(route('admin.members')); ?>" class="nav-item <?php echo e(request()->routeIs('admin.members*') ? 'active' : ''); ?>">
                    <i class="fas fa-users"></i>
                    <span>会員管理</span>
                </a>
                <a href="/" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>フロントに戻る</span>
                </a>
            </nav>
        </aside>

        
        <main class="main-content">
            <header class="content-header">
                <h2><?php echo $__env->yieldContent('page-title', 'ダッシュボード'); ?></h2>
            </header>
            
            <?php if(session('success')): ?>
                <div class="alert alert-success"><?php echo e(session('success')); ?></div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-error"><?php echo e(session('error')); ?></div>
            <?php endif; ?>

            <div class="content-body">
                <?php echo $__env->yieldContent('content'); ?>
            </div>
        </main>
    </div>
</body>
</html>


<?php /**PATH /var/www/html/resources/views/admin/layout.blade.php ENDPATH**/ ?>