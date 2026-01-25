<?php $__env->startSection('title', '注文詳細'); ?>
<?php $__env->startSection('page-title', '注文詳細: ' . $order->order_number); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-info-circle"></i> 注文情報</h3>
    </div>
    <div style="padding: 24px;">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">注文番号</div>
                <div class="info-value"><?php echo e($order->order_number); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">ユーザー</div>
                <div class="info-value"><?php echo e($order->user->name ?? 'N/A'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">合計金額</div>
                <div class="info-value">¥<?php echo e(number_format($order->total_amount)); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">支払い方法</div>
                <div class="info-value"><?php echo e($order->payment_method ?? 'N/A'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">ステータス</div>
                <div class="info-value">
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
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">注文日時</div>
                <div class="info-value"><?php echo e($order->created_at->format('Y/m/d H:i:s')); ?></div>
            </div>
        </div>

        <div class="order-status-actions">
            <?php if($order->status !== 'completed' && $order->status !== 'cancelled'): ?>
            <form action="<?php echo e(route('admin.orders.complete', $order->id)); ?>" method="POST" class="complete-form">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-success btn-large" onclick="return confirm('この注文を完了にしますか？')">
                    <i class="fas fa-check-double"></i> 完了にする
                </button>
            </form>
            <?php endif; ?>
            
            <div class="quick-actions">
                <h4><i class="fas fa-bolt"></i> クイックアクション</h4>
                <div class="quick-action-buttons">
                    <?php if($order->status === 'pending'): ?>
                    <form action="<?php echo e(route('admin.orders.status', $order->id)); ?>" method="POST" style="display: inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-check-circle"></i> 確認済み
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if($order->status === 'confirmed'): ?>
                    <form action="<?php echo e(route('admin.orders.status', $order->id)); ?>" method="POST" style="display: inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="status" value="preparing">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-utensils"></i> 準備中
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if($order->status === 'preparing'): ?>
                    <form action="<?php echo e(route('admin.orders.status', $order->id)); ?>" method="POST" style="display: inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="status" value="ready">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-check"></i> 準備完了
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if($order->status === 'ready'): ?>
                    <form action="<?php echo e(route('admin.orders.complete', $order->id)); ?>" method="POST" style="display: inline;">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-success" onclick="return confirm('この注文を完了にしますか？')">
                            <i class="fas fa-check-double"></i> 完了
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="status-selector">
                <h4><i class="fas fa-cog"></i> ステータス変更</h4>
                <form action="<?php echo e(route('admin.orders.status', $order->id)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="form-group">
                        <select name="status" class="form-select">
                            <option value="pending" <?php echo e($order->status === 'pending' ? 'selected' : ''); ?>>未処理 (pending)</option>
                            <option value="confirmed" <?php echo e($order->status === 'confirmed' ? 'selected' : ''); ?>>確認済み (confirmed)</option>
                            <option value="preparing" <?php echo e($order->status === 'preparing' ? 'selected' : ''); ?>>準備中 (preparing)</option>
                            <option value="ready" <?php echo e($order->status === 'ready' ? 'selected' : ''); ?>>準備完了 (ready)</option>
                            <option value="completed" <?php echo e($order->status === 'completed' ? 'selected' : ''); ?>>完了 (completed)</option>
                            <option value="cancelled" <?php echo e($order->status === 'cancelled' ? 'selected' : ''); ?>>キャンセル (cancelled)</option>
                        </select>
                        <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                            <i class="fas fa-save"></i> 更新
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> 注文明細</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>商品名</th>
                <th>数量</th>
                <th>単価</th>
                <th>小計</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($item->product->name ?? 'N/A'); ?></td>
                <td><?php echo e($item->quantity); ?></td>
                <td>¥<?php echo e(number_format($item->price)); ?></td>
                <td>¥<?php echo e(number_format($item->price * $item->quantity)); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right; font-weight: 700;">合計:</td>
                <td style="font-weight: 700;">¥<?php echo e(number_format($order->total_amount)); ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="form-actions">
    <a href="<?php echo e(route('admin.orders')); ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> 戻る
    </a>
</div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('admin.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/order-detail.blade.php ENDPATH**/ ?>