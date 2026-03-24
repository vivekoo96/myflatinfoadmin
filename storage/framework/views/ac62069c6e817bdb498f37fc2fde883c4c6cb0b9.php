<?php $__env->startSection('title'); ?>
    Vehicle List
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-md-12">
                <?php if(session()->has('error')): ?>
                <div class="alert alert-danger">
                    <?php echo e(session()->get('error')); ?>

                </div>
                <?php endif; ?>
                <?php if(session()->has('success')): ?>
                <div class="alert alert-success">
                    <?php echo e(session()->get('success')); ?>

                </div>
                <?php endif; ?>
            </div>
          <div class="col-sm-6">
            <h1>Vehicle Inouts</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Vehicle Inouts</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">

            <div class="card">
              <div class="card-header">
                <!--<button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Add New Issue</button>-->
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>S No</th>
                              <th>Flat</th>
                              <th>Vehicle No</th>
                              <th>Vehicle Type</th>
                              <th>Vehicle Ownership</th>
                              <th>Type</th>
                              <th>Time</th>
                              <th>Actions</th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php $i = 0; ?>
                        <?php $__empty_1 = true; $__currentLoopData = $building->vehicle_inouts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inout): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php $i++; ?>
                        <tr>
                          <td><?php echo e($i); ?></td>
                          <td><?php echo e(optional($inout->flat)->name); ?></td>
                          <td><?php echo e(optional($inout->vehicle)->vehicle_no); ?></td>
                          <td><?php echo e(optional($inout->vehicle)->vehicle_type); ?></td>
                          <td><?php echo e(optional($inout->vehicle)->ownership); ?></td>
                          <td><?php echo e($inout->type); ?></td>
                          <td><?php echo e($inout->created_at); ?></td>
                          <td>
                            <?php if(Auth::User()->role == 'BA' || Auth::User()->hasRole('security') || Auth::User()->hasRole('president') || Auth::User()->hasPermission('custom.vehiclesinouts')): ?>
                            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" 
                              data-id="<?php echo e($inout->id); ?>" 
                              data-flat_id="<?php echo e($inout->flat_id); ?>" 
                              data-vehicle_id="<?php echo e($inout->vehicle_id); ?>" 
                              data-type="<?php echo e($inout->type); ?>" 
                              data-created_at="<?php echo e($inout->created_at); ?>">
                              <i class="fa fa-edit"></i>
                            </button>
                            <?php if($inout->deleted_at): ?>
                            <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($inout->id); ?>" data-action="restore"><i class="fa fa-undo"></i></button>
                            <?php else: ?>
                            <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($inout->id); ?>" data-action="delete"><i class="fa fa-trash"></i></button>
                            <?php endif; ?>
                            <?php endif; ?>
                          </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                </div>
                
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->

<!-- Add Modal -->

<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit Vehicle Inout</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(url('vehicle-inouts/update')); ?>" method="post" class="add-form">
        <?php echo csrf_field(); ?>
        <div class="modal-body">
          <div class="error"></div>
          <div class="form-group">
            <label for="flat_id" class="col-form-label">Flat:</label>
            <select name="flat_id" class="form-control" id="flat_id" required>
              <option value="">Select Flat</option>
              <?php $__currentLoopData = $building->flats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $flat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($flat->id); ?>"><?php echo e($flat->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
          <div class="form-group">
            <label for="vehicle_id" class="col-form-label">Vehicle:</label>
            <select name="vehicle_id" class="form-control" id="vehicle_id" required>
              <option value="">Select Vehicle</option>
              <?php $__currentLoopData = $building->vehicles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vehicle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($vehicle->id); ?>"><?php echo e($vehicle->vehicle_no); ?> - <?php echo e($vehicle->vehicle_type); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
          <div class="form-group">
            <label for="type" class="col-form-label">Type:</label>
            <select name="type" class="form-control" id="type" required>
              <option value="In">In</option>
              <option value="Out">Out</option>
            </select>
          </div>
          <input type="hidden" name="id" id="edit-id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="save-button">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Are you sure ?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="text"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-danger" data-dismiss="modal" id="delete-button">Confirm Delete</button>
      </div>
    </div>
  </div>
</div>


<?php $__env->startSection('script'); ?>


<script>
  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "<?php echo e(csrf_token()); ?>";
    
    $('.hide-password').hide();
            
    $(document).on('click','.show-password',function(){
        $('.password').attr('type','text');
        $('.show-password').hide();
        $('.hide-password').show();
    });
    $(document).on('click','.hide-password',function(){
        $('.password').attr('type','password');
        $('.hide-password').hide();
        $('.show-password').show();
    });

    $('#deleteModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      id = button.data('id');
      $('.modal-title').text('Are you sure ?');
      $('#delete-id').val(id);
      action= button.data('action');
      $('#delete-button').removeClass('btn-success');
      $('#delete-button').removeClass('btn-danger');
      if(action == 'delete'){
          $('#delete-button').addClass('btn-danger');
          $('#delete-button').text('Confirm Delete');
          $('.text').text('You are going to permanently delete this item..');
      }else{
          $('#delete-button').addClass('btn-success');
          $('#delete-button').text('Confirm Restore');
          $('.text').text('You are going to restore this item..');
      }
    });

    $(document).on('click','#delete-button',function(){
      var url = "<?php echo e(url('vehicle-inouts/destroy')); ?>";
      $.ajax({
        url : url,
        type: "POST",
        data : {'_token':token,'id':id,'action':action},
        success: function(data)
        {
          window.location.reload();
        }
      });
    });

    $('#addModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      $('#edit-id').val(edit_id);
      $('#flat_id').val(button.data('flat_id'));
      $('#vehicle_id').val(button.data('vehicle_id'));
      $('#type').val(button.data('type'));
      $('.modal-title').text('Add New Vehicle Inout');
      if(edit_id){
          $('.modal-title').text('Update Vehicle Inout');
      }
    });
    
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



<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/vehicle/inouts.blade.php ENDPATH**/ ?>