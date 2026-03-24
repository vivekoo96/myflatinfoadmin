

<?php $__env->startSection('title'); ?>
    Setting
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<section class="content">
    <div class="container-fluid">
        <div class="row mt-5">
            <div class="col-md-12 mt-2">
                <center>
                    <div>
                        <!--<img src="<?php echo e($setting->logo); ?>" style="width:60%">-->
                    </div>
                </center>
            </div>
            <div class="col-md-6 offset-md-3">
                <center>
                    <?php if(session()->has('error')): ?>
                        <div class="alert alert-danger">
                            <?php echo e(session()->get('error')); ?>

                        </div>
                    <?php endif; ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="box">
                                <h3>Permission Denied !</h3>
                                <p>You dont have permission to access this page</p>
                            </div>
                            <br>
                            <p><a href="<?php echo e(url('/dashboard')); ?>">Back To Home</a></p>
                        </div>
                    </div>
                    </a>
                </center>
            </div>
        </div>
    </div>
</section>
<?php $__env->startSection('script'); ?>


<script>
  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "<?php echo e(csrf_token()); ?>";
    
    

  });
</script>
<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>




<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/permission_denied.blade.php ENDPATH**/ ?>