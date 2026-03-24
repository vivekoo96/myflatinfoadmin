


<?php $__env->startSection('title'); ?>
    Facility List
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
            <h1>Facility List</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Facilities</li>
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
                <?php if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "Facility"): ?>
                <button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Add New Facility</button>
                <?php endif; ?>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Icon</th>
                    <th>Name</th>
                    <th>Color</th>
                    <th>Max Booking</th>
                    <th>Per User Max Booking(Monthly)</th>
                    <th>GST</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  <?php
                    $facilities = $building->facilities->filter(function($f){ return is_null($f->deleted_at); });
                  ?>
                  <?php $__empty_1 = true; $__currentLoopData = $facilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php $i++; ?>
                  <tr>
                    <td><?php echo e($i); ?></td>
                    <td><img src="<?php echo e($facility->icon); ?>" style="height:40px;"></td>
                    <td><?php echo e($facility->name); ?></td>
                    <td>
                      <div style="width:36px;height:22px;border:1px solid #ddd;background: <?php echo e($facility->color); ?>;display:inline-block;vertical-align:middle;margin-right:6px;"></div>
                      <span class="text-muted"><?php echo e($facility->color); ?></span>
                    </td>
                    <td><?php echo e($facility->max_booking); ?></td>
                    <td><?php echo e($facility->per_user_max_booking); ?></td>
                    <td><?php echo e($facility->gst); ?></td>
                    <td><?php echo e($facility->status); ?></td>
                    <td>
                      <?php if($facility->status !== 'Closed'): ?>
                        <a href="<?php echo e(route('facility.show',$facility->id)); ?>"   class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                      <?php endif; ?>
                    <?php if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "Facility"): ?>
                          <?php if($facility->status !== 'Closed'): ?>
                              <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="<?php echo e($facility->id); ?>" data-name="<?php echo e($facility->name); ?>"
                                  data-max_booking="<?php echo e($facility->max_booking); ?>" data-per_user_max_booking="<?php echo e($facility->per_user_max_booking); ?>" data-gst="<?php echo e($facility->gst); ?>" data-status="<?php echo e($facility->status); ?>"
                                  data-icon="<?php echo e($facility->icon); ?>" data-color="<?php echo e($facility->color); ?>" data-booking_type="<?php echo e($facility->booking_type); ?>" data-closing_reason="<?php echo e($facility->closing_reason); ?>"><i class="fa fa-edit"></i></button>
                          <?php endif; ?>

                              
                              <?php if(is_null($facility->deleted_at) && $facility->status === 'Closed'): ?>
                                <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($facility->id); ?>" data-action="delete"><i class="fa fa-trash"></i></button>
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
        <h5 class="modal-title" id="exampleModalLabel">Add New Facility</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(route('facility.store')); ?>" method="post" class="add-form" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="modal-body">
          <div class="error"></div>
          <div class="form-group">
            <label for="name" class="col-form-label">Name:</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="Name" minlength="3" required>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Icon:</label>
            <input type="file" name="icon" id="icon" class="form-control" placeholder="Icon" accept="image/*">
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Color:</label>
            <div class="d-flex">
                <input type="color" name="color" id="color" class="form-control w-25">
                <input type="text" id="colorHex" class="form-control ms-2" placeholder="#000000" readonly>
            </div>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Max Booking Type:</label>
            <select name="booking_type" id="booking_type" class="form-control" required>
                <option value="Single">Single</option>
                <option value="Multiple">Multiple</option>
            </select>
          </div>
          <div class="form-group max_booking">
            <label for="name" class="col-form-label">Max Booking:</label>
            <input type="number" name="max_booking" id="max_booking" class="form-control" placeholder="Max Booking" min="1" max="100" required>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Per User Max Booking (Monthly):</label>
            <input type="number" name="per_user_max_booking" id="per_user_max_booking" class="form-control" placeholder="Max Booking" min="1" required>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">GST:</label>
            <input type="text" name="gst" id="gst" class="form-control" placeholder="GST"  required>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Status:</label>
            <select name="status" id="status" class="form-control" required>
                <option value="Inactive">Inactive</option>
                <option value="Active">Active</option>
                <option value="Closed">Closed</option>
            </select>
          </div>
          <div class="form-group closing_reason">
            <label for="name" class="col-form-label">Closing Reason:(This will cancel all bookings with 100% refund)</label>
            <textarea name="closing_reason" id="closing_reason" class="form-control" placeholder="Closing reason.."></textarea>
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
    var max_booking = 1;
    $('#deleteModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      id = button.data('id');
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
      var url = "<?php echo e(route('facility.destroy','')); ?>";
      $.ajax({
        url : url + '/' + id,
        type: "DELETE",
        data : {'_token':token,'action':action},
        success: function(data)
        {
          window.location.reload();
        }
      });
    });

    $('#addModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      $('#edit-id').val(button.data('id'));
      $('#name').val(button.data('name'));
      $('#color').val(button.data('color') || '#000000');
      $('#colorHex').val(button.data('color') || '#000000');
      $('#booking_type').val(button.data('booking_type'));
      $('#booking_type').val(button.data('booking_type'));
      $('#max_booking').val(button.data('max_booking'));
      $('#per_user_max_booking').val(button.data('per_user_max_booking'));
      $('#gst').val(button.data('gst'));
      $('#status').val(button.data('status'));
      $('#closing_reason').val(button.data('closing_reason'));
      $('.modal-title').text('Add New Facility');
      $('#icon').attr('required',true);
      $('.closing_reason').hide();
      $('#closing_reason').attr('required',false);
      $('.max_booking').hide();
      var booking_type = button.data('booking_type');
      max_booking = button.data('max_booking');
      if(edit_id){
          $('#icon').attr('required',false);
          $('.modal-title').text('Update Facility');
          var status = button.data('status');
          if(status == 'Closed'){
                $('.closing_reason').show();
                $('#closing_reason').attr('required',true);
            }
      }
      if(booking_type == 'Multiple'){
          $('.max_booking').show();
          $('#max_booking').val(max_booking);
      }else{
          max_booking
          $('.max_booking').hide();
          $('#max_booking').val(1);
      }

    });
    // keep color hex input in sync with color picker and show swatch
    function updateColorDisplay(val){
        $('#colorHex').val(val);
        try{
            $('#colorHex').css('background', val);
            // set text color for contrast
            var r = parseInt(val.substr(1,2),16);
            var g = parseInt(val.substr(3,2),16);
            var b = parseInt(val.substr(5,2),16);
            var yiq = ((r*299)+(g*587)+(b*114))/1000;
            $('#colorHex').css('color', (yiq >= 128) ? '#000' : '#fff');
        }catch(e){
            // ignore
        }
    }

    $('#color').on('input change', function(){
      var v = $(this).val();
      updateColorDisplay(v);
    });
    
    $('#status').on('change', function () {
        let status = $(this).val();
        if (status === 'Closed') {
            $('.closing_reason').show();
            $('#closing_reason').attr('required',true);
        } else {
            $('.closing_reason').hide();
            $('#closing_reason').attr('required',false);
        }
    });
    
    $('#booking_type').on('change', function () {
        let booking_type = $(this).val();
        if (booking_type === 'Multiple') {
            $('.max_booking').show();
            $('#max_booking').val(max_booking);
        } else {
            $('.max_booking').hide();
            $('#max_booking').val(1);
        }
    });

    // Prevent multiple form submissions
    $('.add-form').on('submit', function(e) {
        var $form = $(this);
        var $submitBtn = $form.find('#save-button');
        
        // Check if form is already being submitted
        if ($form.data('submitted') === true) {
            e.preventDefault();
            return false;
        }
        
        // Mark form as submitted and disable button
        $form.data('submitted', true);
        $submitBtn.prop('disabled', true);
        $submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        
        // Re-enable form after 3 seconds (in case of errors)
        setTimeout(function() {
            $form.data('submitted', false);
            $submitBtn.prop('disabled', false);
            $submitBtn.html('Save');
        }, 3000);
    });

    // Reset form submission state when modal is opened
    $('#addModal').on('shown.bs.modal', function() {
        $('.add-form').data('submitted', false);
        $('#save-button').prop('disabled', false);
        $('#save-button').html('Save');
        $('.error').html(''); // Clear any previous errors
    });

  });
</script>
<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/facility/index.blade.php ENDPATH**/ ?>