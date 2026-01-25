<?php $__env->startSection('title', '商品管理'); ?>
<?php $__env->startSection('page-title', '商品管理'); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> 商品一覧</h3>
        <a href="<?php echo e(route('admin.products.create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新規追加
        </a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>画像</th>
                <th>商品名</th>
                <th>価格</th>
                <th>カテゴリ</th>
                <th>在庫</th>
                <th>ステータス</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($product->id); ?></td>
                <td>
                    <img src="<?php echo e($product->image_url); ?>" alt="<?php echo e($product->name); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                </td>
                <td><?php echo e($product->name); ?></td>
                <td>¥<?php echo e(number_format($product->price)); ?></td>
                <td><?php echo e($product->category); ?></td>
                <td><?php echo e($product->stock); ?></td>
                <td>
                    <span class="badge badge-<?php echo e($product->is_available ? 'success' : 'danger'); ?>">
                        <?php echo e($product->is_available ? '販売中' : '販売停止'); ?>

                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="<?php echo e(route('admin.products.edit', $product->id)); ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> 編集
                        </a>
                    <form action="<?php echo e(route('admin.products.delete', $product->id)); ?>" method="POST" style="display: inline;" class="delete-form" data-item-name="<?php echo e($product->name); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> 削除
                        </button>
                    </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="8" class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>商品がありません</p>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="pagination">
        <?php echo e($products->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('admin.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/products.blade.php ENDPATH**/ ?>