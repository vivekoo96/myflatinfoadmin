 
<style>
.readonly-select {
    background-color: #f4f4f4 !important;
    cursor: not-allowed;
}
</style>
<?php
    use Illuminate\Support\Facades\Auth;

    $building_users = collect($building_users);
    $isBA = Auth::user()->role === 'BA';
?>

<select 
    name="treasurer_id" 
    id="treasurer_id"
    class="form-control <?php echo e(!$isBA ? 'readonly-select' : ''); ?>"
    <?php echo e(!$isBA ? 'disabled' : ''); ?>

>
    <?php
        $uniqueUsers = $building_users->unique('user_id');
        $selectedTreasurer = $building->treasurer_id ?? Auth::id();
    ?>

   <?php $__currentLoopData = $uniqueUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $building_user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        $user = $building_user->user ?? $building_user;

        $displayName = $user->name
            ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
    ?>

    <option
        value="<?php echo e($building_user->user_id ?? $building_user->id); ?>"
        <?php echo e($selectedTreasurer == ($building_user->user_id ?? $building_user->id) ? 'selected' : ''); ?>

    >
        <?php echo e($displayName); ?>

    </option>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>


<?php if(!$isBA): ?>
    <input type="hidden" name="treasurer_id" value="<?php echo e($selectedTreasurer); ?>">
<?php endif; ?>

<?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/partials/department_users.blade.php ENDPATH**/ ?>