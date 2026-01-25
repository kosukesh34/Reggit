<?php $__env->startSection('title', '会員管理'); ?>
<?php $__env->startSection('page-title', '会員管理'); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>会員番号</th>
                <th>ユーザー名</th>
                <th>ポイント</th>
                <th>ステータス</th>
                <th>登録日</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $members; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($member->member_number); ?></td>
                <td><?php echo e($member->user->name ?? 'N/A'); ?></td>
                <td><?php echo e(number_format($member->points)); ?>pt</td>
                <td>
                    <span class="badge badge-<?php echo e($member->status === 'active' ? 'success' : 'danger'); ?>">
                        <?php echo e($member->status === 'active' ? '有効' : '無効'); ?>

                    </span>
                </td>
                <td><?php echo e($member->created_at->format('Y/m/d')); ?></td>
                <td>
                    <a href="<?php echo e(route('admin.members.detail', $member->id)); ?>" class="btn btn-sm btn-primary" data-tooltip="会員の詳細を表示">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="6" class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <p>会員がありません</p>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="pagination">
        <?php echo e($members->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('admin.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/members.blade.php ENDPATH**/ ?>