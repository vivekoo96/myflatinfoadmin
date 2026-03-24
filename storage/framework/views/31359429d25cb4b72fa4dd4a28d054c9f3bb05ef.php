<?php $__env->startSection('title'); ?>
    Issue List
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
            <h1>Issue</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Issue</li>
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
                 <?php if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "Issue Tracker"): ?>
                <button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Add New Issue</button>
                <?php endif; ?>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Flat</th>
                    <th>Block</th>
                     <th>Raised by</th>
                    <th>Department</th>
                    <th>Image</th>
                    <th>Desc</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  <?php $__empty_1 = true; $__currentLoopData = $building->issues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $issue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php $i++; ?>
                  <tr>
                    <td><?php echo e($i); ?></td>
                    <td><?php echo e($issue->flat ? $issue->flat->name : 'N/A'); ?></td>
                    <td><?php echo e($issue->block ? $issue->block->name : 'N/A'); ?></td>
                    <td><?php echo e($issue->created_by_rolename); ?></td>
                    <td><?php echo e($issue->department->name); ?></td>
                    <td>
                        <?php if(!empty($issue->photos) && isset($issue->photos[0])): ?>
                          
                            <a href="<?php echo e($issue->photos[0]->photo); ?>"  style="text-decoration: underline; color: #007bff; font-size: 12px;">View Image</a>
                          <?php else: ?>
                          <span>No Image</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e($issue->desc); ?></td>
                    <td><?php echo e($issue->periority); ?></td>
                    <td><?php echo e(($issue->status === 'Solved' || $issue->status === 'Completed') ? 'Completed' : $issue->status); ?></td>
                    <td>
                      <a href="<?php echo e(route('issue.show',$issue->id)); ?>"   class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                      <?php if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "Issue Tracker"): ?>
                      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="<?php echo e($issue->id); ?>" data-desc="<?php echo e($issue->desc); ?>" data-status="<?php echo e($issue->status); ?>" data-building_id="<?php echo e($issue->building_id); ?>" 
                        data-block_id="<?php echo e($issue->block_id); ?>" data-flat_id="<?php echo e($issue->flat_id); ?>" data-periority="<?php echo e($issue->periority); ?>" data-role_id="<?php echo e($issue->role_id); ?>"><i class="fa fa-edit"></i></button>
                      <?php if($issue->deleted_at): ?>
                      <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($issue->id); ?>" data-action="restore"><i class="fa fa-undo"></i></button>
                      <?php else: ?>
                      <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($issue->id); ?>" data-action="delete"><i class="fa fa-trash"></i></button>
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
        <h5 class="modal-title" id="exampleModalLabel">Add Owner</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(route('issue.store')); ?>" method="post" class="add-form" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="modal-body">
          <div class="error"></div>
          
          <div class="form-group">
            <label for="role" class="col-form-label">Department:</label>
            <select name="role_id" class="form-control" id="role_id" required>
              <?php $roles = Auth::User()->building->roles; ?>
              <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <option value="<?php echo e($role->id); ?>"><?php echo e($role->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <?php endif; ?>
            </select>
          </div>
          
          <!-- Hidden Block and Flat inputs -->
          <input type="hidden" name="block_id" id="block_id" value="">
          <input type="hidden" name="flat_id" id="flat_id" value="">
          
          <div class="form-group">
            <label for="role" class="col-form-label">Priority:</label>
            <select name="periority" class="form-control" id="periority" required>
              <option value="High">High</option>
              <option value="Medium">Medium</option>
              <option value="Low">Low</option>
            </select>
          </div>
          <div class="form-group">
            <label for="role" class="col-form-label">Description:</label>
            <textarea name="desc" id="desc" class="form-control" required></textarea>
          </div>
          <div class="form-group">
            <label for="role" class="col-form-label">Status:</label>
            <select name="status" class="form-control" id="status" required>
              <option value="On Hold">On Hold</option>
              <option value="Pending" selected>Pending</option>
              <option value="Ongoing">Ongoing</option>
              <option value="Completed">Completed</option>
              <option value="Rejected">Rejected</option>
            </select>
          </div>
          <div class="form-group">
            <label for="photos" class="col-form-label">Photos:</label>
            <input type="file" accept="image/*" name="photos[]" class="form-control" id="photos" multiple>
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
      var url = "<?php echo e(route('issue.destroy','')); ?>";
      $.ajax({
        url : url + '/' + id,
        type: "DELETE",
        data : {'_token':token,'_method':'DELETE','action':action},
        success: function(data)
        {
          window.location.reload();
        },
        error: function(xhr, status, error) {
          console.log('Delete error:', error);
          console.log('Response:', xhr.responseText);
          alert('Error deleting issue. Please try again.');
        }
      });
    });

    $('#addModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      
      $('.modal-title').text('Add New Issue');
    //   $('#photos').attr('required',true);
      
      if(edit_id){
          // Editing existing issue
          $('.modal-title').text('Update Issue');
          $('#photos').attr('required',false);
          $('#edit-id').val(edit_id);
          $('#building_id').val(button.data('building_id'));
          $('#block_id').val(button.data('block_id'));
          $('#flat_id').val(button.data('flat_id'));
          $('#role_id').val(button.data('role_id'));
          $('#periority').val(button.data('periority'));
          $('#desc').val(button.data('desc'));
          $('#status').val(button.data('status'));
          
          // For editing: show ALL status options
          $('#status option').prop('disabled', false).show();
          $('#status option[value="On Hold"]').prop('disabled', false).show();
          $('#status option[value="Pending"]').prop('disabled', false).show();
          $('#status option[value="Ongoing"]').prop('disabled', false).show();
          $('#status option[value="Completed"]').prop('disabled', false).show();
          $('#status option[value="Rejected"]').prop('disabled', false).show();
          
          // Store current status for later use
          $(this).data('current-status', button.data('status'));
          
            // For editing issues, disable all fields except status
            var currentStatus = button.data('status');

            // Disable all fields except status for editing
            $('#role_id').prop('disabled', true);
            $('#periority').prop('disabled', true);
            $('#desc').prop('readonly', true).css('background-color', '#f8f9fa');
            $('#photos').prop('disabled', true);

            // If issue is NOT Pending then disable editing entirely
            //   if (currentStatus == 'Solved') {
              // Disable status field and save button so user cannot edit
              $('#status').prop('disabled', true);
              $('#save-button').prop('disabled', true);
              $('.error').html('<div class="alert alert-info">Editing disabled: only issues with status <strong>Pending</strong> can be edited.</div>');
            // } else {
              // Keep status field enabled for editing
            //   $('#status').prop('disabled', false);
            //   $('#save-button').prop('disabled', false);
            //   $('.error').html('');
            // }
          
          // Add hidden inputs for all disabled fields when editing
          // Add hidden inputs to ensure disabled select values are submitted
          if ($('#hidden-role-id').length === 0) {
              $('<input>').attr({type: 'hidden', id: 'hidden-role-id', name: 'role_id'}).appendTo('.add-form');
          }
          if ($('#hidden-periority').length === 0) {
              $('<input>').attr({type: 'hidden', id: 'hidden-periority', name: 'periority'}).appendTo('.add-form');
          }
          
          // Set hidden input values for disabled fields
          $('#hidden-role-id').val(button.data('role_id'));
          $('#hidden-periority').val(button.data('periority'));
      } else {
          // Adding new issue - clear all fields and enable them
          $('#edit-id').val('');
          $('#role_id').val('');
          $('#periority').val('High');
          $('#desc').val('');
           $('#status').val('Pending');
          $('#photos').val('');
          
          // Enable all visible fields for new issue
          $('#role_id').prop('disabled', false);
          $('#periority').prop('disabled', false);
          $('#desc').prop('readonly', false).css('background-color', '');
          $('#photos').prop('disabled', false);
          $('#status').prop('disabled', false);
            $('#status option').prop('disabled', false).show();
          $('#status option:not([value="Pending"])').prop('disabled', true).hide();
          // Ensure Save button and error area are reset for new issues
          $('#save-button').prop('disabled', false);
          $('.error').html('');
          
          // Remove hidden inputs for new issues and clear status data
          $('#hidden-role-id, #hidden-periority, #hidden-status, #hidden-status-role').remove();
          $(this).removeData('current-status');
      }

      var block_id = button.data('block_id');
      var flat_id = button.data('flat_id');
      var edit_id = button.data('id');
      
      // Set hidden block and flat values
      $('#block_id').val(block_id);
      $('#flat_id').val(flat_id);
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
    $(document).on('change','#block_id',function(){
        // Block is now hidden, no need for AJAX
    });

    // Prevent multiple form submissions and check for solved issues
    $('.add-form').on('submit', function(e) {
        var $form = $(this);
        var $submitBtn = $form.find('#save-button');
        
        // Check if form is already being submitted
        if ($form.data('submitted') === true) {
            e.preventDefault();
            return false;
        }
        
        // Additional check: prevent submission if save button indicates completed/ solved issue
        if ($submitBtn.text().includes('Issue Solved - Cannot Edit') || $submitBtn.text().includes('Issue Completed - Cannot Edit')) {
          e.preventDefault();
          alert('Cannot update a completed issue. Completed issues are read-only.');
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

  // CRITICAL: Save button disable logic - placed at bottom to prevent override by other scripts
  $(document).ready(function() {
    // This runs after all other scripts to ensure it's not overridden
    $('#addModal').on('shown.bs.modal', function() {
        var currentStatus = $(this).data('current-status');
        
        // Apply save button logic after modal is fully shown
        setTimeout(function() {
            if (currentStatus === 'Solved') {
              $('#save-button').prop('disabled', true).text('Issue Completed - Cannot Edit');
            } else {
              $('#save-button').prop('disabled', false).text('Save');
            }
        }, 100); // Small delay to ensure other scripts have finished
    });
  });
</script>
<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/issue/index.blade.php ENDPATH**/ ?>