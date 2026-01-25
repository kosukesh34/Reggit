<?php $__env->startSection('title', '注文管理'); ?>
<?php $__env->startSection('page-title', '注文管理'); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> ステータスフィルター</h3>
    </div>
    <div class="status-filters">
        <a href="<?php echo e(route('admin.orders', ['status' => 'all'])); ?>" 
           class="status-filter <?php echo e(!request('status') || request('status') === 'all' ? 'active' : ''); ?>">
            <i class="fas fa-list"></i> すべて
            <span class="badge"><?php echo e($statusCounts['all']); ?></span>
        </a>
        <a href="<?php echo e(route('admin.orders', ['status' => 'pending'])); ?>" 
           class="status-filter <?php echo e(request('status') === 'pending' ? 'active' : ''); ?>">
            <i class="fas fa-clock"></i> 未処理
            <span class="badge badge-warning"><?php echo e($statusCounts['pending']); ?></span>
        </a>
        <a href="<?php echo e(route('admin.orders', ['status' => 'confirmed'])); ?>" 
           class="status-filter <?php echo e(request('status') === 'confirmed' ? 'active' : ''); ?>">
            <i class="fas fa-check-circle"></i> 確認済み
            <span class="badge badge-info"><?php echo e($statusCounts['confirmed']); ?></span>
        </a>
        <a href="<?php echo e(route('admin.orders', ['status' => 'preparing'])); ?>" 
           class="status-filter <?php echo e(request('status') === 'preparing' ? 'active' : ''); ?>">
            <i class="fas fa-utensils"></i> 準備中
            <span class="badge badge-info"><?php echo e($statusCounts['preparing']); ?></span>
        </a>
        <a href="<?php echo e(route('admin.orders', ['status' => 'ready'])); ?>" 
           class="status-filter <?php echo e(request('status') === 'ready' ? 'active' : ''); ?>">
            <i class="fas fa-check"></i> 準備完了
            <span class="badge badge-info"><?php echo e($statusCounts['ready']); ?></span>
        </a>
        <a href="<?php echo e(route('admin.orders', ['status' => 'completed'])); ?>" 
           class="status-filter <?php echo e(request('status') === 'completed' ? 'active' : ''); ?>">
            <i class="fas fa-check-double"></i> 完了
            <span class="badge badge-success"><?php echo e($statusCounts['completed']); ?></span>
        </a>
        <a href="<?php echo e(route('admin.orders', ['status' => 'cancelled'])); ?>" 
           class="status-filter <?php echo e(request('status') === 'cancelled' ? 'active' : ''); ?>">
            <i class="fas fa-times-circle"></i> キャンセル
            <span class="badge badge-danger"><?php echo e($statusCounts['cancelled']); ?></span>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> 注文一覧</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>注文番号</th>
                <th>ユーザー</th>
                <th>金額</th>
                <th>支払い方法</th>
                <th>ステータス</th>
                <th>日時</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><a href="<?php echo e(route('admin.orders.detail', $order->id)); ?>"><?php echo e($order->order_number); ?></a></td>
                <td><?php echo e($order->user->name ?? 'N/A'); ?></td>
                <td>¥<?php echo e(number_format($order->total_amount)); ?></td>
                <td><?php echo e($order->payment_method ?? 'N/A'); ?></td>
                <td>
                    <?php
                        $statusLabels = [
                            'pending' => '未処理',
                            'confirmed' => '確認済み',
                            'preparing' => '準備中',
                            'ready' => '準備完了',
                            'completed' => '完了',
                            'cancelled' => 'キャンセル',
                        ];
                        $statusColors = [
                            'pending' => 'warning',
                            'confirmed' => 'info',
                            'preparing' => 'info',
                            'ready' => 'info',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                        ];
                    ?>
                    <span class="badge badge-<?php echo e($statusColors[$order->status] ?? 'info'); ?>">
                        <?php echo e($statusLabels[$order->status] ?? $order->status); ?>

                    </span>
                </td>
                <td><?php echo e($order->created_at->format('Y/m/d H:i')); ?></td>
                <td>
                    <div class="action-buttons">
                    <a href="<?php echo e(route('admin.orders.detail', $order->id)); ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> 詳細
                    </a>
                        <?php if($order->status !== 'completed' && $order->status !== 'cancelled'): ?>
                        <form action="<?php echo e(route('admin.orders.complete', $order->id)); ?>" method="POST" style="display: inline;" onsubmit="return confirm('この注文を完了にしますか？')">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-check-double"></i> 完了
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="7" class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>注文がありません</p>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="pagination">
        <?php echo e($orders->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('admin.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/orders.blade.php ENDPATH**/ ?>