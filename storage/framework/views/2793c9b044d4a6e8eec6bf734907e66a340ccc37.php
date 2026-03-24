<?php $__env->startSection('title'); ?>
    Building Policy
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<link rel="stylesheet" href="<?php echo e(asset('public/admin/plugins/summernote/summernote-bs4.min.css')); ?>">

<!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Building Policy</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Building Policy</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <?php if(session()->has('success')): ?>
                        <div class="alert alert-success">
                            <?php echo e(session()->get('success')); ?>

                        </div>
                    <?php endif; ?>
                       <?php if(Auth::User()->role == 'BA' ): ?>
                    <div class="card-body">
                            <form action="<?php echo e(url('update-building-policy')); ?>" method="post">
                                <?php echo csrf_field(); ?>
                                <div class="form-group">
                                    <label>Building Policy</label>
                                    <textarea name="building_policy" id="summernote" class="form-control"><?php echo $building_policy; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <input type="submit" class="btn bg-gradient-primary btn-flat" value="Save Changes">
                                </div>
                            </form>
                        </div>
                     <?php else: ?>
                          <div style="background-color: white; padding: 20px; border-radius: 4px; border: 1px solid #dee2e6;">
                                <?php echo $building_policy; ?>

                              </div>
                     <?php endif; ?>
                   
                </div>
            </div>
        </div>
    </section>


<?php $__env->startSection('script'); ?>
<script src="<?php echo e(asset('public/admin/plugins/summernote/summernote-bs4.min.js')); ?>"></script>

<script>
  $(function () {
    // Summernote
    $('#summernote').summernote()

  })
</script>
<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/settings/building_policy.blade.php ENDPATH**/ ?>