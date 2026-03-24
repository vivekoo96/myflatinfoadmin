<?php $__env->startSection('title'); ?>
    Noticeboard List
<?php $__env->stopSection(); ?>

<style>
.desc-short, .desc-full {
    font-weight: normal !important;
    font-style: normal !important;
}

.desc-toggle {
    font-weight: normal !important;
    text-decoration: none !important;
}

.desc-toggle:hover {
    text-decoration: underline !important;
}

/* Ensure table cells don't force bold text */
.table td {
    font-weight: normal !important;
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
                <?php if(empty($building) || !$building->id): ?>
                <div class="alert alert-warning">
                  Current building context is not set, so noticeboard items cannot be displayed. Please ensure your profile is assigned to a building.
                </div>
                <?php endif; ?>
                <?php if(session()->has('success')): ?>
                <div class="alert alert-success">
                    <?php echo e(session()->get('success')); ?>

                </div>
                <?php endif; ?>
            </div>
          <div class="col-sm-6">
            <h1>Noticeboard List</h1>
          </div>
          <div class="col-sm-6">
            
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Noticeboards</li>
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
                    <button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Add New Noticeboard</button>
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
                    <th>Title</th>
                    <th>Description</th>
                    <th>From Time</th>
                    <th>To Time</th>
                      <?php if(Auth::User()->role == 'BA'): ?>
                    <th>Action</th>
                    <?php endif; ?>
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  <?php $__empty_1 = true; $__currentLoopData = $building->noticeboards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php $i++; ?>
                  <tr>
                    <td><?php echo e($i); ?></td>
                    <td><a href="<?php echo e(route('buildings.show',$item->building_id)); ?>" target="_blank"><?php echo e($item->building->name); ?></a></td>
                    
                    <td>
                      <?php echo e($item->title); ?>

                      <?php if(\Carbon\Carbon::parse($item->to_time)->isPast()): ?>
                        <span class="badge badge-secondary ml-1">Inactive</span>
                      <?php elseif(\Carbon\Carbon::parse($item->from_time)->isFuture()): ?>
                        <span class="badge badge-info ml-1">Future</span>
                      <?php elseif(\Carbon\Carbon::parse($item->from_time)->isToday()): ?>
                        <span class="badge badge-success ml-1">Today</span>
                      <?php else: ?>
                        <span class="badge badge-warning ml-1">Active</span>
                      <?php endif; ?>
                    </td>
                    <td>
                    <?php if(strlen($item->desc) > 300): ?>
                        <span class="desc-short"><?php echo e(Str::limit($item->desc, 300)); ?></span>
                        <span class="desc-full d-none"><?php echo e($item->desc); ?></span>
                        <a href="#" class="text-primary desc-toggle" data-id="<?php echo e($item->id); ?>">Show more</a>
                    <?php else: ?>
                        <?php echo e($item->desc); ?>

                    <?php endif; ?>
                    </td>
                    <td><?php echo e(\Carbon\Carbon::parse($item->from_time)->format('M j, Y g:i A')); ?></td>
                    <td><?php echo e(\Carbon\Carbon::parse($item->to_time)->format('M j, Y g:i A')); ?></td>
                    <?php if(Auth::User()->role == 'BA'): ?>
                    <td>
                      <!--<a href="<?php echo e(route('noticeboard.show',$item->id)); ?>" target="_blank"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>-->
                      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="<?php echo e($item->id); ?>" data-title="<?php echo e($item->title); ?>" data-desc="<?php echo e($item->desc); ?>"  
                      data-from_time="<?php echo e($item->from_time); ?>" data-to_time="<?php echo e($item->to_time); ?>" data-block_ids="<?php echo e($item->blocks->pluck('id')->implode(',')); ?>" data-building_id="<?php echo e($item->building_id); ?>"><i class="fa fa-edit"></i></button>
                      <?php if($item->deleted_at): ?>
                      <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($item->id); ?>" data-action="restore"><i class="fa fa-undo"></i></button>
                      <?php else: ?>
                      <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($item->id); ?>" data-action="delete"><i class="fa fa-trash"></i></button>
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
        <h5 class="modal-title" id="exampleModalLabel">Add Event</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(route('noticeboard.store')); ?>" method="post" class="add-form" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
          <div class="modal-body">
          <div class="error">
            <?php if($errors->any()): ?>
              <div class="alert alert-danger">
                <ul class="mb-0">
                  <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
              </div>
            <?php endif; ?>
          </div>
          
        
            <?php $userBuildings = Auth::user() ? (method_exists(Auth::user(), 'allBuildings') ? Auth::user()->allBuildings() : Auth::user()->buildings()) : collect(); ?>
            
          
          <input type="hidden" name="building_id" id="building_id" value="<?php echo e($building->id); ?>">
          <div class="form-group">
            <label for="name" class="col-form-label">Title:</label>
            <input type="text" name="title" id="title" class="form-control" value="<?php echo e(old('title')); ?>" maxlength="50" placeholder="Enter title" required>
          </div>

          <div class="form-group">
            <label for="code" class="col-form-label">Description:</label>
            <textarea name="desc" class="form-control" id="desc" placeholder="Enter description" required rows="4"><?php echo e(old('desc')); ?></textarea>
           
          </div>
          <input type="hidden" name="block_ids[]" value="all" id="block_ids">
          
          
          <div class="form-group">
            <label for="code" class="col-form-label">From Time:</label>
            <input type="datetime-local" name="from_time" class="form-control" id="from_time" placeholder="From Time" value="<?php echo e(old('from_time')); ?>" required>
          
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">To Time:</label>
            <input type="datetime-local" name="to_time" class="form-control" id="to_time" placeholder="To Time" value="<?php echo e(old('to_time')); ?>" required>
            
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
    
    // Fix noticeboard description textarea - completely remove all validation and enable emojis/new lines
    setTimeout(function() {
        var $desc = $('#desc');
        
        // Remove ALL event handlers that might interfere
        $desc.off('input').off('keydown').off('keypress').off('keyup').off('paste').off('cut');
        
        // Remove all restrictive attributes
        $desc.removeAttr('maxlength').removeAttr('pattern').removeAttr('data-validation');
        
        // Mark this textarea as completely free from validation
        $desc.attr('data-no-validation', 'true').attr('data-allow-emojis', 'true');
        
        // Add completely unrestricted event handling
        $desc.on('keydown', function(e) {
            // Allow ALL keys including Enter for new lines
            return true;
        });
        
        $desc.on('input', function(e) {
            // Allow ALL input including emojis and special characters
            return true;
        });
        
        $desc.on('paste', function(e) {
            // Allow all pasting including emojis
            return true;
        });
        
        // Override any global validation that might try to interfere
        $desc[0].addEventListener('input', function(e) {
            e.stopImmediatePropagation();
        }, true);
        
        $desc[0].addEventListener('keydown', function(e) {
            e.stopImmediatePropagation();
        }, true);
        
        console.log('Noticeboard description textarea completely unrestricted - emojis and new lines enabled');
    }, 200);
    
    // Handle description show more/less functionality
    $(document).on('click', '.desc-toggle', function(e) {
        e.preventDefault();
        var $toggle = $(this);
        var $short = $toggle.siblings('.desc-short');
        var $full = $toggle.siblings('.desc-full');
        
        if ($full.hasClass('d-none')) {
            // Show full content
            $short.addClass('d-none');
            $full.removeClass('d-none');
            $toggle.text('Show less');
        } else {
            // Show short content
            $short.removeClass('d-none');
            $full.addClass('d-none');
            $toggle.text('Show more');
        }
    });
    
    // Block selection removed from UI. Keep hidden input defaulting to 'all'.
    
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
      var url = "<?php echo e(route('noticeboard.destroy','')); ?>";
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

      // Clear fields first unless we have old input from validation errors
      var hasOldInput = <?php echo e($errors->any() ? 'true' : 'false'); ?>;
      var edit_id = button.data('id');
      
      if (!hasOldInput) {
        $('#edit-id').val('');
        $('#title').val('');
        $('#desc').val('');
        // Keep block_ids defaulting to 'all' (hidden input). Do NOT clear to null —
        // clearing produced an empty string which caused a DB insert with '' block_id.
        $('#block_ids').val('all');
        // Only clear datetime fields if NOT editing; for creation, let user fill them in
        if (!edit_id) {
          $('#from_time').val('');
          $('#to_time').val('');
        }
        // Set default building selection for creation
        $('#building_id').val('<?php echo e(request('building_id', $building->id ?? '')); ?>');
      }
      $('.modal-title').text('Add New Noticeboard');

      // Remove all min constraints - allow any date/time selection
      // Validation will happen on submit only
      $('#from_time').removeAttr('min');
      $('#to_time').removeAttr('min');

      // If editing, populate fields
      if(edit_id) {
        $('#edit-id').val(button.data('id'));
        $('#title').val(button.data('title'));
        $('#desc').val(button.data('desc'));

        // We no longer expose block selection in the UI; always default to 'all'
        $('#block_ids').val('all');

        // Populate datetime values - convert from server format to datetime-local format
        var fromVal = button.data('from_time');
        var toVal = button.data('to_time');
        
        if (fromVal) {
          // Server sends: 2025-12-09 14:30:00 or 2025-12-09T14:30:00
          // datetime-local expects: 2025-12-09T14:30
          var cleanFrom = String(fromVal).replace(' ', 'T').substring(0, 16);
          $('#from_time').val(cleanFrom);
          originalFromDate = cleanFrom; // Store original for validation
        }
        if (toVal) {
          var cleanTo = String(toVal).replace(' ', 'T').substring(0, 16);
          $('#to_time').val(cleanTo);
          originalToDate = cleanTo; // Store original for validation
        }
        // populate building_id if present (for SA or BA with selection)
        var buildingId = button.data('building_id');
        if (buildingId) { $('#building_id').val(buildingId); }
        $('.modal-title').text('Update Noticeboard');
      } else {
        // Reset original values when creating new
        originalFromDate = null;
        originalToDate = null;
        isUserInteracting = false;
      }
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "<?php echo e(url('update-building-status')); ?>",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });

        // Validate date and time fields (prevent past selections and ensure to_time > from_time)
        function isPast(dt) {
          var now = new Date();
          // Check if the selected date/time is in the past (including today's past times)
          return dt < now;
        }

        function isPastOrTodayPastTime(dt) {
          var now = new Date();
          var selectedDate = new Date(dt);
          
          console.log('Validation Check:', {
            selectedDateTime: selectedDate,
            currentDateTime: now,
            isSelectedPast: selectedDate < now,
            selectedString: selectedDate.toString(),
            currentString: now.toString()
          });
          
          // Get today's date at midnight for comparison
          var todayMidnight = new Date();
          todayMidnight.setHours(0, 0, 0, 0);
          
          // Get tomorrow's date at midnight for comparison
          var tomorrowMidnight = new Date(todayMidnight);
          tomorrowMidnight.setDate(tomorrowMidnight.getDate() + 1);
          
          // Check if it's a past date (before today)
          if (selectedDate < todayMidnight) {
            return true; // Past date - not allowed
          }
          
          // If it's today, only allow future times
          if (selectedDate >= todayMidnight && selectedDate < tomorrowMidnight) {
            return selectedDate <= now; // Past or current time on today's date
          }
          
          // Future dates are always allowed
          return false; // Future date - allowed
        }

        // Store original date values when editing starts
        var originalFromDate = null;
        var originalToDate = null;
        var isUserInteracting = false;

        $('#from_time, #to_time').on('change', function() {
          isUserInteracting = true; // Mark that user is interacting
          
          // Only validate on submit, not on change
          // Allow any date/time selection in the calendar
          console.log('Date changed, will validate on submit');
        });

        function validateNewDates() {
          // No validation on change - only on submit
          console.log('Date selection allowed, will validate on submit');
        }

        // Ensure dates are validated on submit (covers programmatic or create values)
        $('.add-form').on('submit', function(e) {
          var isEdit = $('#edit-id').val();
          var fromVal = $('#from_time').val();
          var toVal = $('#to_time').val();
          var now = new Date();

          // Log what's being submitted for debugging
          console.log('Form submission - From Time:', fromVal);
          console.log('Form submission - To Time:', toVal);

          // Ensure both from_time and to_time have values before submitting
          if (!fromVal) {
            e.preventDefault();
            alert('From Time is required.');
            return false;
          }

          if (!toVal) {
            e.preventDefault();
            alert('To Time is required.');
            return false;
          }

          // For creation, validate normally
          if (!isEdit) {
            if (fromVal) {
              var f = new Date(fromVal);
              if (f < now) {
                e.preventDefault();
                alert('From date and time cannot be in the past.');
                return false;
              }
            }

            if (toVal) {
              var t = new Date(toVal);
              if (t < now) {
                e.preventDefault();
                alert('To date and time cannot be in the past.');
                return false;
              }
            }
          }

          // For editing, prevent past dates and times only if changed
          if (isEdit) {
            if (fromVal && fromVal !== originalFromDate) {
              var f = new Date(fromVal);
              if (isPastOrTodayPastTime(f)) {
                e.preventDefault();
                alert('Cannot select a past date and time. Please select a future date and time.');
                return false;
              }
            }

            if (toVal && toVal !== originalToDate) {
              var t = new Date(toVal);
              if (isPastOrTodayPastTime(t)) {
                e.preventDefault();
                alert('Cannot select a past date and time. Please select a future date and time.');
                return false;
              }
            }
          }

          // Always validate that to_time is after from_time
          if (fromVal && toVal) {
            var fromDate = new Date(fromVal);
            var toDate = new Date(toVal);
            if (toDate <= fromDate) {
              e.preventDefault();
              alert('To date and time must be after from date and time.');
              return false;
            }
          }

          return true;
        });

  });
</script>
<?php if($errors->any()): ?>
<script>
  $(document).ready(function(){
    // Show modal if validation errors occurred
    $('#addModal').modal('show');
    // If old id present, set edit id
    var oldId = '<?php echo e(old('id')); ?>';
    if(oldId) {
      $('#edit-id').val(oldId);
    }
    var oldBlockIds = <?php echo json_encode(old('block_ids', []), 512) ?>;
    if(oldBlockIds && oldBlockIds.length > 0) {
      $('#block_ids').val(oldBlockIds).trigger('change');
    }
    // Restore old datetime values - they come from the form submission
    // Format should be: YYYY-MM-DDTHH:MM or YYYY-MM-DDTHH:MM:SS
    var oldFrom = '<?php echo e(old('from_time')); ?>';
    var oldTo = '<?php echo e(old('to_time')); ?>';
    if(oldFrom) {
      // Ensure format is datetime-local compatible (YYYY-MM-DDTHH:MM)
      var cleanFrom = String(oldFrom).replace(' ', 'T').substring(0, 16);
      $('#from_time').val(cleanFrom);
    }
    if(oldTo) {
      var cleanTo = String(oldTo).replace(' ', 'T').substring(0, 16);
      $('#to_time').val(cleanTo);
    }
  });
</script>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/noticeboard/index.blade.php ENDPATH**/ ?>