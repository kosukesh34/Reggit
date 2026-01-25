<?php $__env->startSection('title', isset($product) ? '商品編集' : '商品追加'); ?>
<?php $__env->startSection('page-title', isset($product) ? '商品編集' : '商品追加'); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <form action="<?php echo e(isset($product) ? route('admin.products.update', $product->id) : route('admin.products.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <?php if(isset($product)): ?>
            <?php echo method_field('PUT'); ?>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label">商品名</label>
            <input type="text" name="name" class="form-control" value="<?php echo e(old('name', $product->name ?? '')); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">説明</label>
            <textarea name="description" class="form-control" rows="3"><?php echo e(old('description', $product->description ?? '')); ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">価格</label>
            <input type="number" name="price" class="form-control" value="<?php echo e(old('price', $product->price ?? '')); ?>" min="0" step="1" required>
        </div>

        <div class="form-group">
            <label class="form-label"><i class="fas fa-tags"></i> カテゴリ</label>
            <select name="category" class="form-select" required>
                <option value="food" <?php echo e(old('category', $product->category ?? '') === 'food' ? 'selected' : ''); ?>>フード</option>
                <option value="drink" <?php echo e(old('category', $product->category ?? '') === 'drink' ? 'selected' : ''); ?>>ドリンク</option>
                <option value="dessert" <?php echo e(old('category', $product->category ?? '') === 'dessert' ? 'selected' : ''); ?>>デザート</option>
                <option value="side" <?php echo e(old('category', $product->category ?? '') === 'side' ? 'selected' : ''); ?>>サイドメニュー</option>
                <option value="other" <?php echo e(old('category', $product->category ?? '') === 'other' ? 'selected' : ''); ?>>その他</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label"><i class="fas fa-image"></i> 画像URL</label>
            <input type="url" name="image_url" class="form-control" value="<?php echo e(old('image_url', $product->image_url ?? '')); ?>" placeholder="https://...">
            <small class="form-help"><i class="fas fa-info-circle"></i> 画像を追加後、php artisan products:download-images を実行してください</small>
        </div>

        <div class="form-group">
            <label class="form-label">在庫数</label>
            <input type="number" name="stock" class="form-control" value="<?php echo e(old('stock', $product->stock ?? 0)); ?>" min="0" required>
        </div>

        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="is_available" id="is_available" value="1" <?php echo e(old('is_available', $product->is_available ?? true) ? 'checked' : ''); ?>>
                <label for="is_available">販売中</label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> 保存
            </button>
            <a href="<?php echo e(route('admin.products')); ?>" class="btn btn-secondary">
                <i class="fas fa-times"></i> キャンセル
            </a>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('admin.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/product-form.blade.php ENDPATH**/ ?>