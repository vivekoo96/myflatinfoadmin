

<?php $__env->startSection('title'); ?>
    Classified Details
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Classified Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Classified Details</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-3">

            <!-- Profile Image -->
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center">
                </div>
                <h3 class="profile-username text-center"><?php echo e($classified->category); ?></h3>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Status</b> <a class="float-right"><?php echo e($classified->status); ?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Target Buildings</b>
                    <div class="float-right">
                      <?php if($targetBuildings->count() > 0): ?>
                        <?php $__currentLoopData = $targetBuildings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $building): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                          <span class="badge badge-info mb-1"><?php echo e($building->name); ?></span><br>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                      <?php else: ?>
                        <span class="badge badge-secondary">No buildings</span>
                      <?php endif; ?>
                    </div>
                  </li>
                </ul>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->

          </div>
          <!-- /.col -->
          <div class="col-md-9">
              <div class="card">
                  <div class="card-header">
                      <span class="badge badge-default"><?php echo e($classified->category); ?></span>
                      <span class="badge badge-primary"><?php echo e($classified->status); ?></span>
                  </div>
                  <div class="card-body">
                      <p><b><?php echo e($classified->title); ?></b></p>
                      <p><?php echo e($classified->desc); ?></p>
                      <div class="row">
                          <?php $__empty_1 = true; $__currentLoopData = $classified->photos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                          <div class="col-md-4 d-flex align-items-center justify-content-center" style="border:1px solid #ddd;">
                          <img src="<?php echo e($photo->photo); ?>" class="img-fluid w-100">
                          </div>
                          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                          <?php endif; ?>
                      </div>
                  </div>
              </div>
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
    
<?php $__env->startSection('script'); ?>


<script>
  $(document).ready(function(){

  });
</script>
<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>




<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/classified/show.blade.php ENDPATH**/ ?>