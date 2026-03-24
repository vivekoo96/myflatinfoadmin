<?php $__env->startSection('title'); ?>
    Department List
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
            <h1>Issue Departments</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Issue Departments</li>
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
                <button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">
                    <?php echo e(request()->is('issue-department*') ? 'Add New Department' : 'Add New Role'); ?>

                </button>
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
                    <th>Name</th>
                    <!--<th>Slug</th>-->
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  <?php $__empty_1 = true; $__currentLoopData = $building->roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php $i++; ?>
                  <tr>
                    <td><?php echo e($i); ?></td>
                    <td><?php echo e($role->building->name); ?></td>
                    <td><?php echo e($role->name); ?></td>
                    <!--<td><?php echo e($role->slug); ?></td>-->
                    <td>
                      <a href="<?php echo e(url('issue-department',$role->slug)); ?>" class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                     <?php if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "Issue Tracker"): ?>
                      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="<?php echo e($role->id); ?>" data-name="<?php echo e($role->name); ?>" data-slug="<?php echo e($role->slug); ?>" 
                       data-building_id="<?php echo e($role->building_id); ?>"><i class="fa fa-edit"></i></button>
                      <?php if($role->deleted_at): ?>
                      <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($role->id); ?>" data-action="restore"><i class="fa fa-undo"></i></button>
                      <?php else: ?>
                      <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($role->id); ?>" data-action="delete"><i class="fa fa-trash"></i></button>
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
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">
    <?php echo e(request()->is('issue-department*') ? 'Add New Department' : 'Add Role'); ?>

</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(route('role.store')); ?>" method="post" class="add-form">
        <?php echo csrf_field(); ?>
        <div class="modal-body">
          <div class="error"></div>
          <!--<div class="form-group">-->
          <!--  <label for="name" class="col-form-label">Building:</label>-->
          <!--  <select name="building_id" id="building_id" class="form-control" required>-->
          <!--      <option value="<?php echo e($building->id); ?>"><?php echo e($building->name); ?></option>-->
          <!--  </select>-->
          <!--</div>-->
          <div class="form-group">
            <label for="name" class="col-form-label">Name:</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="Name" required>
          </div>
          <div class="form-group" style="display: none;">
            <label for="name" class="col-form-label">Slug:</label>
            <input type="hidden" name="slug" id="slug" class="form-control" placeholder="Slug">
          </div>
          <!--<div class="form-group">-->
          <!--  <label for="name" class="col-form-label">Permissions:</label>-->
          <!--  <div class="row">-->
          <!--      <?php $__empty_1 = true; $__currentLoopData = $building->permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>-->
          <!--      <div class="col-md-3">-->
          <!--          <input type="checkbox" name="permissions[]" value="<?php echo e($permission->id); ?>" class="permission-checkbox"> <?php echo e($permission->slug); ?>-->
          <!--      </div>-->
          <!--      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>-->
          <!--      <?php endif; ?>-->
          <!--  </div>-->
          <!--</div>-->
          <input type="hidden" name="id" id="edit-id">
          <input type="hidden" name="type" id="type" value="issue">
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
    
    // Auto-generate slug from name
    $('#name').on('input', function() {
        var name = $(this).val();
        var slug = name.toLowerCase()
                      .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
                      .replace(/\s+/g, '-')         // Replace spaces with hyphens
                      .replace(/-+/g, '-')          // Replace multiple hyphens with single
                      .trim('-');                   // Remove leading/trailing hyphens
        $('#slug').val(slug);
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
      var url = "<?php echo e(route('role.destroy','')); ?>";
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
      
      // Clear fields first
      $('#name').val('');
      $('#slug').val('');
      var isIssueDepartment = window.location.pathname.indexOf('issue-department') !== -1;
      $('.modal-title').text(isIssueDepartment ? 'Add New Department' : 'Add New Role');
      // Uncheck all permissions by default
      $('.permission-checkbox').prop('checked', false);
      
      // If editing, populate fields
      if(edit_id){
          $('.modal-title').text(isIssueDepartment ? 'Update Department' : 'Update Role');
          $('#name').val(button.data('name'));
          $('#slug').val(button.data('slug'));
          
          // Fetch the role's existing permissions using AJAX
        // $.ajax({
        //     url: "<?php echo e(url('get-role-permissions')); ?>/" + edit_id, // Create a route to fetch permissions
        //     type: "GET",
        //     success: function(response) {
        //         if (response.permissions) {
        //             // Uncheck all first
        //             $('input[name="permissions[]"]').prop('checked', false);

        //             // Loop through and check the permissions
        //             response.permissions.forEach(function(permission_id) {
        //                 $('input[name="permissions[]"][value="' + permission_id + '"]').prop('checked', true);
        //             });
        //         }
        //     }
        // });
      }
      
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "<?php echo e(url('update-role-status')); ?>",
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



<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/role/index.blade.php ENDPATH**/ ?>