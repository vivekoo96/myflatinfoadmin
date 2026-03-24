            <select name="gate_id" class="form-control" id="gate_id" required>
                <option value="">--Select--</option>
                <?php $__empty_1 = true; $__currentLoopData = $gates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $gate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <option value="<?php echo e($gate->id); ?>" <?php echo e($gate->id == $gate_id ? 'selected' : ''); ?>><?php echo e($gate->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <?php endif; ?>
            </select><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/partials/gates.blade.php ENDPATH**/ ?>