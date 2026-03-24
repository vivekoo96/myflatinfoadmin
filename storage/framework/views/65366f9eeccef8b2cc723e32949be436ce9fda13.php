<?php $__env->startSection('title'); ?>
    Block List
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
            <h1>Block List</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Block</li>
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
                <?php if(Auth::User()->role == 'BA'): ?>
                <button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Add New Block</button>
                <?php endif; ?>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Building</th>
                    <th>Block Name</th>
                    <th>Flats</th>
                    <th>Gates</th>
                    <th>Guards</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  <?php $__empty_1 = true; $__currentLoopData = $building->blocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $block): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php $i++; ?>
                  <tr>
                    <td><?php echo e($i); ?></td>
                    <td><?php echo e($block->building->name); ?></td>
                    <td><?php echo e($block->name); ?></td>
                    <td><?php echo e($block->flats->count()); ?></td>
                    <td><?php echo e($block->gates->count()); ?></td>
                    <td><?php echo e($block->guards->count()); ?></td>
                    <td><?php echo e($block->status); ?></td>
                    <td>
                      <a href="<?php echo e(route('block.show',$block->id)); ?>"   class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                      <?php if(Auth::User()->role == 'BA'): ?>
                      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="<?php echo e($block->id); ?>" data-name="<?php echo e($block->name); ?>" data-status="<?php echo e($block->status); ?>">
                          <i class="fa fa-edit"></i></button>
                      <?php if($block->deleted_at): ?>
                      <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($block->id); ?>" data-action="restore"><i class="fa fa-undo"></i></button>
                      <?php else: ?>
                      <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($block->id); ?>" data-action="delete"><i class="fa fa-trash"></i></button>
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
        <h5 class="modal-title" id="exampleModalLabel">Add New Block</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(route('block.store')); ?>" method="post" class="add-form" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="modal-body">
          <div class="error"></div>
          <div class="form-group">
      <label for="name" class="col-form-label">Block Name:</label>
      <input type="text" name="name" id="name" class="form-control" placeholder="Block Name" minlength="1" maxlength="20" required aria-describedby="blockNameHelp">
      <small id="blockNameHelp" class="form-text text-muted">Block name must be between 1 and 20 characters.</small>
      <div class="invalid-feedback">
        Please enter a block name between 1 and 20 characters.
      </div>
          </div>

          
          <div class="form-group">
            <label for="name" class="col-form-label">Status:</label>
            <select name="status" id="status" class="form-control" required>
                <option value="Inactive">Inactive</option>
                <option value="Active">Active</option>
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
    
    // Fix DataTables search functionality completely
    setTimeout(function() {
        // Check if DataTables is initialized
        if (!$.fn.dataTable.isDataTable('#example1')) {
            console.log('DataTables not initialized, initializing now...');
            $('#example1').DataTable({
                "responsive": false, 
                "scrollX": true,  
                "ordering": true, 
                "lengthChange": false, 
                "autoWidth": false,
                "paging": true,
                "info": false,
                "searching": true,
                "pageLength": 10,
                "language": {
                    "search": "Search:"
                }
            });
        }
        
        // Completely disable input validation for all DataTables search inputs
        $('.dataTables_filter input').each(function() {
            var $input = $(this);
            // Remove all event handlers
            $input.off();
            // Remove all attributes that might interfere
            $input.removeAttr('maxlength').removeAttr('pattern');
            // Add back only the DataTables search functionality with case-insensitive support
            $input.on('keyup', function(e) {
                var searchTerm = $(this).val();
                var table = $('#example1').DataTable();
                
                // Enable case-insensitive search
                table.search(searchTerm, false, false, true).draw();
                
                console.log('Searching for:', searchTerm);
            });
        });
        
        console.log('DataTables search fix applied');
        
        // Fallback: Add custom search if DataTables search still doesn't work
        setTimeout(function() {
            // Add custom search input if needed
            if ($('.dataTables_filter input').length === 0) {
                $('.card-body').prepend('<div class="mb-3"><input type="text" id="customSearch" class="form-control" placeholder="Search blocks..."></div>');
                
                $('#customSearch').on('keyup', function() {
                    var searchTerm = $(this).val().toLowerCase();
                    console.log('Custom search for:', searchTerm);
                    $('#example1 tbody tr').each(function() {
                        var rowText = $(this).text().toLowerCase();
                        var shouldShow = rowText.includes(searchTerm);
                        $(this).toggle(shouldShow);
                        console.log('Row:', rowText, 'Match:', shouldShow);
                    });
                });
            }
        }, 500);
    }, 300);
    
    // Prevent multiple form submissions
    $('.add-form').on('submit', function(e) {
        var submitButton = $(this).find('#save-button');
        
        // Disable the submit button to prevent multiple clicks
        submitButton.prop('disabled', true);
        submitButton.html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        
        // Re-enable button after 3 seconds in case of any issues
        setTimeout(function() {
            submitButton.prop('disabled', false);
            submitButton.html('Save');
        }, 3000);
    });
    
    $('#deleteModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      id = button.data('id');
      $('.modal-title').text('Are you sure ?')
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
      var url = "<?php echo e(route('block.destroy','')); ?>";
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
      $('#edit-id').val(edit_id);
      
      // Reset submit button state when modal opens
      $('#save-button').prop('disabled', false);
      $('#save-button').html('Save');
      
      if(edit_id){
          // Editing existing block
          $('#name').val(button.data('name'));
          $('#building_id').val(button.data('building_id'));
          $('#status').val(button.data('status'));
          $('.modal-title').text('Update Block');
      } else {
          // Adding new block - clear form fields
          $('#name').val('');
          $('#building_id').val('');
          $('#status').val('Active');
          $('.modal-title').text('Add New Block');
      }
      
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "<?php echo e(url('update-block-status')); ?>",
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


<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/block/index.blade.php ENDPATH**/ ?>