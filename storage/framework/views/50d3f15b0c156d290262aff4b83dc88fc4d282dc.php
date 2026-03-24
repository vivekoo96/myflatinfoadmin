
            <select name="flat_id" class="form-control" id="flat_id">
                <option value="">All</option>
                <?php $__empty_1 = true; $__currentLoopData = $flats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $flat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <option value="<?php echo e($flat->id); ?>" <?php echo e($flat->id == $flat_id ? 'selected' : ''); ?>><?php echo e($flat->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <?php endif; ?>
            </select><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/partials/flats.blade.php ENDPATH**/ ?>