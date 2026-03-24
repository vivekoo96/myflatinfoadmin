    <div class="model-id">
        <div class="form-group">
            <label for="name" class="col-form-label">Events:</label>
            <select name="model_id" class="form-control" id="model_id" required>
                <?php $__empty_1 = true; $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <option value="<?php echo e($event->id); ?>" <?php echo e($event->id == $model_id ? 'selected' : ''); ?>><?php echo e($event->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <?php endif; ?>
            </select>
        </div>
    </div><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/partials/events.blade.php ENDPATH**/ ?>