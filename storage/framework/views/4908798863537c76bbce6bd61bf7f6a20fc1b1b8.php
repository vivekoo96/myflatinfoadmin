<?php $__env->startSection('title'); ?>
    Classified List
<?php $__env->stopSection(); ?>


  <style>
  .readonly-select {
    background-color: #f4f4f4 !important;
    pointer-events: none;   /* makes select unclickable */
    cursor: not-allowed;
  }
</style>


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
                        <?php $i = 0; ?>
          <div class="col-sm-6">
            <h1>Classified List</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Classifieds</li>
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
                    <?php
                      try {
                        $__now = \Carbon\Carbon::now();
                        $__within_span = intval($building->within_for_month ?? 1);
                        $__all_span = intval($building->all_for_month ?? 1);
                        $__within_span = max(1, $__within_span);
                        $__all_span = max(1, $__all_span);
                        $__start_within = $__now->copy()->subMonths($__within_span - 1)->startOfMonth();
                        
                        $__start_all = $__now->copy()->subMonths($__all_span - 1)->startOfMonth();
                        $__end = $__now->copy()->endOfMonth();

                        // SIMPLE COUNT LOGIC - PER-USER APPROVED CLASSIFIEDS
                        if (!isset($within_used_user)) {
                          $within_used_user = \App\Models\Classified::where('building_id', $building->id)
                            ->where('user_id', Auth::id())
                            ->where('category', 'Within Building')
                            ->where('status', 'Approved')
                            ->where('is_approved_on_creation', true)
                            ->whereBetween('created_at', [$__start_within, $__end])
                            ->count();
                        }

                        if (!isset($all_used_user)) {
                          $all_used_user = \App\Models\Classified::where('building_id', $building->id)
                            ->where('user_id', Auth::id())
                            ->where('category', 'All Buildings')
                            ->where('status', 'Approved')
                            ->where('is_approved_on_creation', true)
                            ->whereBetween('created_at', [$__start_all, $__end])
                            ->count();
                        }

                        // CALCULATE PER-USER REMAINING QUOTA (each user has individual limit)
                        if (!isset($within_remaining)) {
                          if (isset($building->classified_limit_within_building) && $building->classified_limit_within_building !== null) {
                            $limit = intval($building->classified_limit_within_building);
                            $within_remaining = max(0, $limit - $within_used_user);
                          } else {
                            $within_remaining = null; // unlimited
                          }
                        }

                        if (!isset($all_remaining)) {
                          if (isset($building->classified_limit_all_building) && $building->classified_limit_all_building !== null) {
                            $limit = intval($building->classified_limit_all_building);
                            $all_remaining = max(0, $limit - $all_used_user);
                          } else {
                            $all_remaining = null; // unlimited
                          }
                        }

                        $within_exhausted = ($within_remaining === 0);
                        $all_exhausted = ($all_remaining === 0);
                      } catch (\Exception $e) {
                        // If anything goes wrong while computing counts, default to not exhausting quotas
                        // so the Add button remains visible and users are not blocked by view errors.
                        $within_remaining = $within_remaining ?? null;
                        $all_remaining = $all_remaining ?? null;
                        $within_used_user = $within_used_user ?? 0;
                        $all_used_user = $all_used_user ?? 0;
                        $within_exhausted = false;
                        $all_exhausted = false;
                      }
                    ?>
              <?php if(Auth::User()->role == 'BA'): ?>
              <div class="card-header">
                 <?php if(Auth::User()->building && (Auth::User()->building->hasPermission('Classified for withinbuilding') || Auth::User()->building->hasPermission('Classified for all buildings'))): ?>
                <button id="add-classified-btn" class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal" <?php echo e(($within_exhausted && $all_exhausted) ? 'disabled' : ''); ?>>Add New Classified</button>
                <?php endif; ?>
                <?php if(Auth::User()->building && Auth::User()->building->hasPermission('Classified for withinbuilding')): ?>
                <div class="float-right px-3">
                  <small class="text-muted">Within: <?php if($within_remaining === null): ?> Unlimited <?php else: ?> <?php echo e($within_remaining); ?> left <?php endif; ?> <?php if(isset($within_used_user)): ?> <span class="text-primary"> <?php endif; ?></small>
                  <?php if(Auth::User()->building->hasPermission('Classified for all buildings')): ?>
                  &nbsp;|&nbsp;
                  <small class="text-muted">All: <?php if($all_remaining === null): ?> Unlimited <?php else: ?> <?php echo e($all_remaining); ?> left <?php endif; ?> <?php if(isset($all_used_user)): ?> <span class="text-primary"> <?php endif; ?></small>
                  <?php endif; ?>
                </div>
                <?php elseif(Auth::User()->building && Auth::User()->building->hasPermission('Classified for all buildings')): ?>
                <div class="float-right px-3">
                  <small class="text-muted">All: <?php if($all_remaining === null): ?> Unlimited <?php else: ?> <?php echo e($all_remaining); ?> left <?php endif; ?> <?php if(isset($all_used_user)): ?> <span class="text-primary"> <?php endif; ?></small>
                </div>
                <?php endif; ?>
              </div>
              <?php endif; ?>
             
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped table-sm">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Posted By</th>
                    <th>Building</th>
                    <!--<th>Block</th>-->
                    <th>Category</th>
                    <th>Title</th>
                    <!--<th>Image</th>-->
                    <th>Desc</th>
                    <th>Status</th>
                    <?php if(Auth::User()->role == 'BA'): ?>
                    <th>Action</th>
                    <?php endif; ?>
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  <?php $__empty_1 = true; $__currentLoopData = $building->classifieds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php $i++; ?>
                  <tr>
                    <td><?php echo e($i); ?></td>
                    <td>
                      <?php if($item->user): ?>
                        <a href="<?php echo e(url('customer',$item->user_id)); ?>"><?php echo e($item->user->name); ?></a>
                      <?php else: ?>
                        <span class="text-muted">User Deleted</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if($item->building_id == 0): ?>
                        <span class="badge badge-info">Super Admin</span>
                      <?php elseif($item->building): ?>
                        <a href="<?php echo e(route('buildings.show',$item->building_id)); ?>"><?php echo e($item->building->name); ?></a>
                      <?php else: ?>
                        <span class="text-muted">Building Deleted</span>
                      <?php endif; ?>
                    </td>
                   
                    <td><?php echo e($item->category); ?></td>
                    <td><?php echo e($item->title); ?></td>
                    <!--<td>-->
                   
                    <!--   <?php if($item->photos->first() && $item->photos->first()->photo): ?>-->
                      
                    <!--    <a href="<?php echo e($item->photos->first()->photo); ?>" target="_blank" style="text-decoration: underline;">-->
                    <!--        View Image-->
                    <!--    </a>-->
                    <!--<?php else: ?>-->
                    <!--    <span class="text-muted">No Image</span>-->
                    <!--<?php endif; ?>-->
                     
                    <!--</td>-->
                    <td><?php echo e($item->desc); ?></td>
                    <td><?php echo e($item->status); ?></td>
                     <?php if(Auth::User()->role == 'BA'): ?>
                    <td>
                      <a href="<?php echo e(route('classified.show',$item->id)); ?>"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                      <?php if($item->building_id != 0): ?>
                      <?php if(Auth::user()->building_id == $item->building_id): ?>
                      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="<?php echo e($item->id); ?>" data-title="<?php echo e($item->title); ?>" data-desc="<?php echo e($item->desc); ?>"  
                       data-status="<?php echo e($item->status); ?>" data-building_id="<?php echo e($item->building_id); ?>" data-reason="<?php echo e($item->reason); ?>" 
                      data-block_id="<?php echo e($item->block_id); ?>" data-category="<?php echo e($item->category); ?>" data-user_id="<?php echo e($item->user_id); ?>"><i class="fa fa-edit"></i></button>
                      <?php endif; ?>
                      <?php if($item->deleted_at): ?>
                        <?php if(Auth::user()->building_id == $item->building_id): ?>
                        <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($item->id); ?>" data-action="delete"><i class="fa fa-trash"></i></button>
                    
                        <?php endif; ?>
                      <?php endif; ?>
                      <?php endif; ?>
                    </td>
                    <?php endif; ?>

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
        <h5 class="modal-title" id="exampleModalLabel">Add Classified</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(route('classified.store')); ?>" method="post" class="add-form" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="modal-body">
          <div class="error"></div>
          <!--<input type="hidden" name="id" id="edit-id" value="">-->
           <input type="hidden" name="building_id" id="building_id" value="<?php echo e($building->id); ?>">
         
          
          <div class="form-group">
            <label for="name" class="col-form-label">Category:</label>
            <select name="category" id="category" class="form-control" required>
              <option value="">-- Select Category --</option>
              <option value="Within Building"
                    data-remaining="<?php echo e($within_remaining === null ? -1 : $within_remaining); ?>"
                    data-permission="<?php echo e(Auth::User()->building->hasPermission('Classified for withinbuilding') ? 'true' : 'false'); ?>"
                >Within Building</option>

                <option value="All Buildings"
                    data-remaining="<?php echo e($all_remaining === null ? -1 : $all_remaining); ?>"
                    data-permission="<?php echo e(Auth::User()->building->hasPermission('Classified for all buildings') ? 'true' : 'false'); ?>"
                >All Buildings</option>
            </select>
            <small id="limit-help" class="form-text text-muted"></small>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Title:</label>
            <input type="text" name="title" id="title" class="form-control" min="3" max="30" placeholder="Title" minlength="4" required>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Desc:</label>
            <textarea name="desc" id="desc" class="form-control" required></textarea>
          </div>
          <div class="form-group">
            <label for="image" class="col-form-label">Images (Optional):</label>
            <input type="file" name="photos[]" id="image" class="form-control" multiple accept="image/*">
          </div>
           <div class="form-group">
            <label for="name" class="col-form-label">Status:</label>
            <select name="status" id="status" class="form-control" required>
                <?php if(Auth::User()->role == 'BA'): ?>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                    <option value="Send For Editing">Send For Editing</option>
                 
                <?php endif; ?>
            </select>
          </div>
          <!-- CRITICAL: This hidden field MUST be populated when editing existing classifieds -->
          <!-- When BA approves a user's post, this user_id ensures quota is credited to original poster, not BA -->
          <input type="hidden" name="user_id" id="user_id" value="">
          
          <!-- Hidden Reason field -->
        <div class="form-group" id="reasonBox" style="display: none;">
          <label for="reason" class="col-form-label">Reason:</label>
          <textarea name="reason" id="reason" class="form-control" rows="3"></textarea>
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
    const LOGGED_IN_USER_ID = <?php echo e(Auth::id()); ?>;
</script>

<script>
 
  $(document).ready(function(){

    var id = '';
    var action = '';
    var token = "<?php echo e(csrf_token()); ?>";

    /* ================= DELETE / RESTORE ================= */

    $('#deleteModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      id = button.data('id');
      action = button.data('action');

      $('#delete-button').removeClass('btn-success btn-danger');

      if(action === 'delete'){
          $('#delete-button').addClass('btn-danger').text('Confirm Delete');
          $('.text').text('You are going to permanently delete this item.');
      } else {
          $('#delete-button').addClass('btn-success').text('Confirm Restore');
          $('.text').text('You are going to restore this item.');
      }
    });

    $(document).on('click','#delete-button',function(){
      var url = "<?php echo e(route('classified.destroy','')); ?>/" + id;

      $.ajax({
        url : url,
        type: "DELETE",
        data : {_token: token, action: action},
        success: function(){
          window.location.reload();
        }
      });
    });

    /* ================= ADD / EDIT MODAL ================= */

    $('#addModal').on('show.bs.modal', function (event) {

      var button = $(event.relatedTarget);
      var edit_id = button.data('id') || '';
      var classifiedUserId = parseInt(button.data('user_id')) || null;
      var isOwner = (classifiedUserId === LOGGED_IN_USER_ID);
            var currentStatus = button.data('status') || '';
        $('#status option').show().prop('disabled', false);
        
            <?php if(Auth::user()->role == 'BA'): ?>
                if (edit_id && currentStatus === 'Approved') {
                    // remove unwanted options
                    $('#status option[value="Rejected"]').remove();
                    $('#status option[value="Send For Editing"]').remove();
        
                    // ensure Approved stays selected
                    $('#status').val('Approved');
                }
            <?php endif; ?>

  $('#status option').prop('disabled', false).hide();

    if (!edit_id) {
        // ================= ADD MODE =================
        $('#status option[value="Approved"]').show();
        $('#status').val('Approved');

        // disable others completely
        $('#status option[value!="Approved"]').prop('disabled', true);

    } else {
        // ================= EDIT MODE =================
        $('#status option').show();

        // set existing status
        $('#status').val(button.data('status'));
    }
      // reset
      $('.add-form')[0].reset();
      $('#edit-id').val(edit_id);
      $('#user_id').val(classifiedUserId || '');
      $('#limit-help').text('').removeClass('text-danger');
      $('#save-button').prop('disabled', false);

      $('.modal-title').text(edit_id ? 'Update Classified' : 'Add New Classified');

      /* ---------------- STATUS FIX ---------------- */
      if (edit_id) {
        $('#status').val(button.data('status'));
      } else {
        $('#status').val('Pending'); // ✅ ADD DEFAULT
      }

      $('#title').val(button.data('title') || '');
      $('#desc').val(button.data('desc') || '');
      $('#reason').val(button.data('reason') || '');

      toggleReason($('#status').val());

      /* ---------------- CATEGORY ---------------- */
      var cat = button.data('category');
      if (cat) {
        $('#category').val(cat).data('original', cat);
      }

      /* ================= OWNER CHECK ================= */

      if (edit_id && !isOwner) {

        // ✅ READONLY (NOT DISABLED)
        $('#title, #desc').prop('readonly', true);
        $('#image').prop('disabled', true); // file input can't be readonly

        $('#category')
          .addClass('readonly-select')
          .data('locked', true);

        $('#limit-help')
          .text('You are not the owner. Only status / reason can be changed.')
          .addClass('text-danger');

      } else {

        // ✅ ENABLE FOR OWNER / CREATE
        $('#title, #desc').prop('readonly', false);
        $('#image').prop('disabled', false);

        $('#category')
          .removeClass('readonly-select')
          .data('locked', false);
      }

      /* ---------------- CREATE CATEGORY AVAILABILITY ---------------- */

      if (!edit_id) {
        var enabledOptions = [];

        $('#category option').each(function(){
          var val = $(this).val();
          if (!val) return;

          var rem = Number($(this).data('remaining'));
          var perm = String($(this).data('permission')) === 'true';

          if (perm && (isNaN(rem) || rem === -1 || rem > 0)) {
            $(this).prop('disabled', false);
            enabledOptions.push(val);
          } else {
            $(this).prop('disabled', true);
          }
        });

        if (enabledOptions.length === 1) {
          $('#category').val(enabledOptions[0]).trigger('change');
        } else if (enabledOptions.length === 0) {
          $('#limit-help').text('No categories available to post.');
          $('#save-button').prop('disabled', true);
        }
      }
    });

    /* ================= BLOCK CATEGORY CHANGE (LOCK) ================= */

    $(document).on('change', '#category', function (e) {
      if ($(this).data('locked') === true) {
        e.preventDefault();
        $(this).val($(this).data('original'));
        return false;
      } else {
        $(this).data('original', $(this).val());
      }
    });

    /* ================= STATUS → REASON ================= */

    $(document).on('change', '#status', function () {
      toggleReason($(this).val());
    });

    function toggleReason(status) {
      if (status === 'Rejected' || status === 'Send For Editing') {
        $('#reasonBox').show();
        $('#reason').attr('required', true);
      } else {
        $('#reasonBox').hide();
        $('#reason').removeAttr('required');
      }
    }

    /* ================= CATEGORY LIMIT TEXT ================= */

    $(document).on('change', '#category', function () {
      if ($(this).data('locked') === true) return;

      var opt = $(this).find('option:selected');
      var rem = parseInt(opt.data('remaining'));
      var perm = String(opt.data('permission'));

      if (perm === 'false') {
        $('#limit-help').text('You do not have permission.');
        $('#save-button').prop('disabled', true);
      } else if (isNaN(rem) || rem === -1) {
        $('#limit-help').text('Unlimited posts allowed.');
        $('#save-button').prop('disabled', false);
      } else if (rem === 0) {
        $('#limit-help').text('Monthly limit reached.');
        $('#save-button').prop('disabled', true);
      } else {
        $('#limit-help').text(rem + ' post(s) left this month.');
        $('#save-button').prop('disabled', false);
      }
    });

  });
</script>



<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/classified/index.blade.php ENDPATH**/ ?>