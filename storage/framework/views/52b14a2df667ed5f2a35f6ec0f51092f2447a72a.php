<?php $__env->startSection('title'); ?>
    User Details
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
             <h1><?php echo e(($role_name && $role_name !== 'User') ? 'Other User Details' : 'User Details'); ?></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">User Details</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row justify-content-center">
          <div class="col-md-8">
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center mb-3">
                  <img class="profile-user-img img-fluid img-circle" src="<?php echo e($customer->photo); ?>" alt="User profile picture" style="width:100px;height:100px;object-fit:cover;">
                </div>
                <h3 class="profile-username text-center mb-3"><?php echo e($customer->name); ?></h3>
                <div class="table-responsive">
                  <table class="table table-bordered table-striped mb-0">
                    <tbody>
                      <tr>
                        <td><b>Role</b></td>
                        <td colspan="3">
                          <?php echo e($role_name); ?>

                        </td>
                        
                        
                      </tr>
                      <tr>
                        <td><b>Email</b></td>
                        <td><?php echo e($customer->email); ?></td>
                        <td><b>Phone</b></td>
                        <td><?php echo e($customer->phone); ?></td>
                      </tr>
                      <tr>
                        <td><b>Gender</b></td>
                        <td><?php echo e($customer->gender); ?></td>
                        <td><b>Departments</b></td>
                        <td colspan="3">
                          <?php
                            $filteredDepartments = $customer->departments->filter(function($department) {
                              return $department->role && $department->role->name !== 'User' && $department->building_id == Auth::user()->building_id;
                            });
                          ?>
                          <?php $__empty_1 = true; $__currentLoopData = $filteredDepartments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php echo e($department->role->name); ?><?php if(!$loop->last): ?>, <?php endif; ?>
                          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            No departments assigned
                          <?php endif; ?>
                        </td>
                      </tr>
                      
                    
                      <tr>
                        <td><b>Created at</b></td>
                        <td><?php echo e($customer->created_at); ?></td>
                        <td><b>Updated at</b></td>
                        <td><?php echo e($customer->updated_at); ?></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
    
    


<?php $__env->startSection('script'); ?>
<script>
    $(document).ready(function(){
        var id = '';
        var action = '';
        var token = "<?php echo e(csrf_token()); ?>";
        
        $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "<?php echo e(url('update-user-status')); ?>",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/user/show.blade.php ENDPATH**/ ?>