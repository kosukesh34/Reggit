<?php $__env->startSection('title', 'ダッシュボード'); ?>
<?php $__env->startSection('page-title', 'ダッシュボード'); ?>

<?php $__env->startSection('content'); ?>
<div class="stats-grid">
    <div class="stat-card stat-card-primary">
        <div class="stat-icon">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">総商品数</div>
            <div class="stat-value"><?php echo e($stats['total_products']); ?></div>
        </div>
    </div>
    <div class="stat-card stat-card-info">
        <div class="stat-icon">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">総注文数</div>
            <div class="stat-value"><?php echo e($stats['total_orders']); ?></div>
        </div>
    </div>
    <div class="stat-card stat-card-success">
        <div class="stat-icon">
            <i class="fas fa-user"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">総ユーザー数</div>
            <div class="stat-value"><?php echo e($stats['total_users']); ?></div>
        </div>
    </div>
    <div class="stat-card stat-card-warning">
        <div class="stat-icon">
            <i class="fas fa-id-card"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">総会員数</div>
            <div class="stat-value"><?php echo e($stats['total_members']); ?></div>
        </div>
    </div>
    <div class="stat-card stat-card-danger">
        <div class="stat-icon">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">本日の注文</div>
            <div class="stat-value"><?php echo e($stats['today_orders']); ?></div>
        </div>
    </div>
    <div class="stat-card stat-card-success">
        <div class="stat-icon">
            <i class="fas fa-yen-sign"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">本日の売上</div>
            <div class="stat-value">¥<?php echo e(number_format($stats['today_revenue'])); ?></div>
        </div>
    </div>
    <div class="stat-card stat-card-warning">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">未処理注文</div>
            <div class="stat-value"><?php echo e($stats['pending_orders']); ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> 最近の注文</h3>
        <a href="<?php echo e(route('admin.orders')); ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> すべて見る
        </a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>注文番号</th>
                <th>ユーザー</th>
                <th>金額</th>
                <th>ステータス</th>
                <th>日時</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $recent_orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><a href="<?php echo e(route('admin.orders.detail', $order->id)); ?>"><?php echo e($order->order_number); ?></a></td>
                <td><?php echo e($order->user->name ?? 'N/A'); ?></td>
                <td>¥<?php echo e(number_format($order->total_amount)); ?></td>
                <td>
                    <span class="badge badge-<?php echo e($order->status === 'completed' ? 'success' : ($order->status === 'pending' ? 'warning' : 'info')); ?>">
                        <?php echo e($order->status); ?>

                    </span>
                </td>
                <td><?php echo e($order->created_at->format('Y/m/d H:i')); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="5" class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>注文がありません</p>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('admin.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>