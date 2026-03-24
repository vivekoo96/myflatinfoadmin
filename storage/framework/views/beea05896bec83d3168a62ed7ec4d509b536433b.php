

<?php $__env->startSection('title'); ?>
    Visitor Details
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Visitor Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Visitor Details</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-3">
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center">
                 <?php if(!empty($visitor->head_photo)): ?>
                <img class="profile-user-img img-fluid img-circle"
                     src="<?php echo e($visitor->head_photo); ?>"
                     alt="Issue picture">
            <?php else: ?>
                <span>No Image</span>
            <?php endif; ?>

                </div>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Members</b> <a class="float-right"><?php echo e($visitor->total_members); ?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Status</b> <a class="float-right"><?php echo e($visitor->status); ?></a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          
          <div class="col-md-9">
            <div class="card">
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                  <li class="nav-item"><a class="nav-link active" href="#basic" data-toggle="tab">Basic Info</a></li>
                  <li class="nav-item"><a class="nav-link" href="#inouts" data-toggle="tab">Inouts</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="active tab-pane" id="basic">
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr style="display:none;">
                              <td></td>
                              <td></td>
                              <td></td>
                              <td></td>
                          </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>Head Name</th>
                          <td><?php echo e($visitor->head_name); ?></td>
                          <td>Head Phone</th>
                          <td><?php echo e($visitor->head_phone); ?></td>
                        </tr>

                        <tr>
                          <td>Staying From</th>
                          <td><?php echo e($visitor->stay_from); ?></td>
                          <td>Staying To</th>
                          <td><?php echo e($visitor->stay_to); ?></td>
                        </tr>
                        <tr>
                          <td>Type</th>
                          <td><?php echo e($visitor->type); ?></td>
                          <td>Status</th>
                          <td><?php echo e($visitor->status); ?></td>
                        </tr>
                        <tr>
                          <td>Purpose</th>
                          <td colspan="3"><?php echo e($visitor->visiting_purpose); ?></td>
                        </tr>
                        <tr>
                          <td>Created at</th>
                          <td><?php echo e($visitor->created_at); ?></td>
                          <td>Updated at</th>
                          <td><?php echo e($visitor->updated_at); ?></th>
                        </tr>
                        
                        <tr>
                        <td>Vehicles</td>
                        <td colspan="3">
                            <?php if($visitor->vehicles->isNotEmpty()): ?>
                                <ul class="mb-0 pl-3">
                                   
                                    <?php $__currentLoopData = $visitor->vehicles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vehicle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    
                                       <span class="d-block mb-1">
                                        <i class="fas fa-car text-secondary mr-2"></i>
                                        <?php echo e($vehicle->vehicle_no); ?>

                                    </span>

                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            <?php else: ?>
                                <span>No Vehicle</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                        
                      </tbody>
                    </table>
                    </div>
                  </div>
                  <!-- /.tab-pane -->
                  
                  <div class="tab-pane" id="inouts">
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>S No</th>
                              <th>Type</th>
                              <th>Time</th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php $i = 0; ?>
                        <?php $__empty_1 = true; $__currentLoopData = $visitor->inouts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inout): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php $i++; ?>
                        <tr>
                          <td><?php echo e($i); ?></th>
                          <td><?php echo e($inout->type); ?></td>
                          <td><?php echo e($inout->created_at); ?></th>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                    </div>
                  </div>
                  <!-- /.tab-pane -->
                  
                </div>
                <!-- /.tab-content -->
              </div><!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
      </div>
    </section>

<?php $__env->startSection('script'); ?>
<script>
  $(document).ready(function(){
      var token = "<?php echo e(csrf_token()); ?>";

  });
</script>
<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/visitor/show.blade.php ENDPATH**/ ?>