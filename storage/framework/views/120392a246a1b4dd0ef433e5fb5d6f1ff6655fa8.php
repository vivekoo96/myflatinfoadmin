<?php $__env->startSection('title'); ?>
    Visitor Management
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Visitor Management</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Visitors</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<!-- Success/Error Messages -->
<?php if(session('success')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
  <strong>Success!</strong> <?php echo e(session('success')); ?>

  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
</div>
<?php endif; ?>

<?php if(session('error')): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
  <strong>Error!</strong> <?php echo e(session('error')); ?>

  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
</div>
<?php endif; ?>

<?php if(session('info')): ?>
<div class="alert alert-info alert-dismissible fade show" role="alert">
  <strong>Info!</strong> <?php echo e(session('info')); ?>

  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
</div>
<?php endif; ?>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title">All Visitors</h5>
            <!--<div class="card-tools">-->
            <!--  <?php if(Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Security' || Auth::User()->role == 'BA'): ?>-->
            <!--  <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#visitorModal" data-type="regular">-->
            <!--    <i class="fa fa-plus"></i> Add Visitor-->
            <!--  </button>-->
            <!--  <?php endif; ?>-->
            <!--  <?php if(Auth::User()->role == 'BA'): ?>-->
            <!--  <button class="btn btn-sm btn-primary ml-2" data-toggle="modal" data-target="#visitorModal" data-type="invitation">-->
            <!--    <i class="fa fa-envelope"></i> Invite Guest-->
            <!--  </button>-->
            <!--  <?php endif; ?>-->
            <!--</div>-->
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>S.No</th>
                    <th>Type</th>
                    <th>Flat</th>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Mobile</th>
                    <!--<th>Vehicle</th>-->
                    <th>From</th>
                    <th>To</th>
                    <th>Guests</th>
                    <th>Purpose</th>
                    <th>Passcode</th>
                    <!--<th>Email</th>-->
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $__empty_1 = true; $__currentLoopData = $visitors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $visitor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php if(!$visitor->deleted_at): ?>
                  <tr>
                    <td><?php echo e($loop->iteration); ?></td>
                    <td>
                      <span class="badge badge-<?php echo e($visitor->type == 'Planned' ? 'info' : 'warning'); ?>">
                        <?php echo e($visitor->type); ?>

                      </span>
                    </td>
                    <td><?php echo e($visitor->flat ? $visitor->flat->name : 'N/A'); ?></td>
                    <td>
                     
                           <?php if($visitor->head_photo): ?>
                        <a href="<?php echo e($visitor->head_photo); ?>" target="_blank" style="text-decoration: underline; display: inline-block;" title="View Full Image">
                          View Image
                        </a>
                      <?php else: ?>
                        <span class="text-muted">No Image</span>
                      <?php endif; ?>
                   
                    </td>
                    <td><?php echo e($visitor->head_name); ?></td>
                    <td><?php echo e($visitor->head_phone); ?></td>
                    
                    <td><?php echo e($visitor->stay_from ? date('d-m-Y H:i', strtotime($visitor->stay_from)) : ''); ?></td>
                    <td><?php echo e($visitor->stay_to ? date('d-m-Y H:i', strtotime($visitor->stay_to)) : ''); ?></td>
                    <td><?php echo e($visitor->total_members); ?></td>
                    <td><?php echo e($visitor->visiting_purpose); ?></td>
                    <td>
                      <?php if($visitor->code): ?>
                        <code class="bg-light p-1"><?php echo e($visitor->code); ?></code>
                      <?php else: ?>
                        <span class="text-muted">N/A</span>
                      <?php endif; ?>
                    </td>
                   
                    <td>
                      <span class="badge badge-<?php echo e($visitor->status == 'AllowIn' ? 'success' : ($visitor->status == 'Invited' ? 'info' : 'secondary')); ?>">
                        <?php echo e($visitor->status); ?>

                      </span>
                    </td>
                    <td>
                      <a href="<?php echo e(route('visitor.show', $visitor->id)); ?>" class="btn btn-sm btn-warning" title="View">
                        <i class="fa fa-eye"></i>
                      </a>
                      <!--  <a href="<?php echo e(url('visitor/user-history/' . $visitor->head_phone)); ?>" target="_blank" class="btn btn-sm btn-success" title="View User History">-->
                      <!--  <i class="fa fa-history"></i>-->
                      <!--</a>-->
                     
                      <?php if(Auth::User()->hasRole('security')): ?>
                      <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" 
                              data-id="<?php echo e($visitor->id); ?>" data-action="delete" title="Delete">
                        <i class="fa fa-trash"></i>
                      </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endif; ?>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="15" class="text-center">No visitors found.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Unified Visitor Modal -->
<div class="modal fade" id="visitorModal" tabindex="-1" role="dialog" aria-labelledby="visitorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="visitorModalLabel">Add Visitor</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="visitorForm" method="POST" action="<?php echo e(route('visitor.store')); ?>" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" id="visitor_id">
        <input type="hidden" name="form_type" id="form_type" value="regular">
        <div class="modal-body">
          <div class="row">
            <!-- Left Column -->
            <div class="col-md-6">
              <div class="form-group">
                <label for="visitor_name">Name <span class="text-danger">*</span></label>
                <input type="text" name="head_name" id="visitor_name" class="form-control" required minlength="2" maxlength="100">
              </div>
              
              <div class="form-group">
                <label for="visitor_mobile">Mobile Number <span class="text-danger">*</span></label>
                <input type="text" name="head_phone" id="visitor_mobile" class="form-control" required 
                       pattern="^[6-9]\d{9}$" maxlength="10" placeholder="Enter 10-digit mobile number">
                <small class="form-text text-muted">Enter a valid 10-digit mobile number starting with 6, 7, 8, or 9</small>
              </div>
              
              <div class="form-group">
                <label for="visitor_vehicle_number">Vehicle Number</label>
                <input type="text" name="vehicle_number" id="visitor_vehicle_number" class="form-control" 
                       maxlength="20" placeholder="e.g., MH01AB1234">
              </div>
              
              <div class="form-group">
                <label for="visitor_vehicle_type">Vehicle Type</label>
                <select name="vehicle_type" id="visitor_vehicle_type" class="form-control">
                  <option value="">Select Vehicle Type</option>
                  <option value="Car">Car</option>
                  <option value="Bike">Bike</option>
                  <option value="Auto">Auto</option>
                  <option value="Taxi">Taxi</option>
                  <option value="Bus">Bus</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              
              <div class="form-group">
                <label for="visitor_photo">Photo</label>
                <input type="file" name="head_photo" id="visitor_photo" class="form-control-file" 
                       accept="image/jpeg,image/jpg,image/png">
                <small class="form-text text-muted">Upload JPG, JPEG, or PNG image (max 2MB)</small>
              </div>
            </div>
            
            <!-- Right Column -->
            <div class="col-md-6">
              <!-- Regular Visitor Fields -->
              <div id="regular_fields">
                <div class="form-group">
                  <label for="block_id">Block <span class="text-danger">*</span></label>
                  <select name="block_id" id="block_id" class="form-control">
                    <option value="">Select Block</option>
                    <?php $__empty_1 = true; $__currentLoopData = $building->blocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $block): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                      <option value="<?php echo e($block->id); ?>"><?php echo e($block->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php endif; ?>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="flat_id">Flat <span class="text-danger">*</span></label>
                  <select name="flat_id" id="flat_id" class="form-control">
                    <option value="">Select Flat</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="visitor_purpose">Purpose of Visit <span class="text-danger">*</span></label>
                  <textarea name="visiting_purpose" id="visitor_purpose" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                  <label for="visitor_status">Status</label>
                  <select name="status" id="visitor_status" class="form-control">
                    <option value="AllowIn">Allow In</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                </div>
              </div>
              
              <!-- Invitation Fields -->
              <div id="invitation_fields" style="display:none;">
                <div class="form-group">
                  <label for="block_id_invitation">Block <span class="text-danger">*</span></label>
                  <select name="block_id" id="block_id_invitation" class="form-control" required>
                    <option value="">Select Block</option>
                    <?php $__empty_1 = true; $__currentLoopData = $building->blocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $block): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                      <option value="<?php echo e($block->id); ?>"><?php echo e($block->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php endif; ?>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="flat_id_invitation">Flat <span class="text-danger">*</span></label>
                  <select name="flat_id" id="flat_id_invitation" class="form-control" required>
                    <option value="">Select Flat</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="invitation_email">Guest Email</label>
                  <input type="email" name="invitation_email" id="invitation_email" class="form-control" maxlength="100">
                </div>
              </div>
              
              <!-- Common Fields -->
              <div class="form-group">
                <label for="visitor_type">Type</label>
                <select name="type" id="visitor_type" class="form-control">
                  <option value="Planned">Planned</option>
                  <option value="Unplanned">Unplanned</option>
                </select>
              </div>
              
              <div class="form-group">
                <label for="visitor_date">Visit Date <span class="text-danger">*</span></label>
                <input type="date" name="visit_date" id="visitor_date" class="form-control" required min="<?php echo e(date('Y-m-d')); ?>">
              </div>
              
              <div class="form-group">
                <label for="visitor_time">Visit Time <span class="text-danger">*</span></label>
                <input type="time" name="visit_time" id="visitor_time" class="form-control" required>
              </div>
              
              <div class="form-group">
                <label for="visitor_guests">Number of Guests <span class="text-danger">*</span></label>
                <input type="number" name="total_members" id="visitor_guests" class="form-control" 
                       required min="1" max="20" value="1">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="submitBtn">Save Visitor</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="delete-text">Are you sure you want to delete this visitor?</p>
        <input type="hidden" id="delete_id">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirm-delete">Delete</button>
      </div>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script>
$(document).ready(function(){
  var token = "<?php echo e(csrf_token()); ?>";
  
  // Initialize DataTable
  // $('#visitorsTable').DataTable({
  //   "responsive": true,
  //   "lengthChange": false,
  //   "autoWidth": false,
  //   "order": [[ 0, "desc" ]]
  // });
  
  // Function to load flats based on block selection
  function loadFlats(blockId, selectedFlatId = null, targetFlatSelect = '#flat_id') {
    if (!blockId) {
      $(targetFlatSelect).html('<option value="">Select Flat</option>');
      return;
    }
    
    $.ajax({
      url: '/get-flats/' + blockId,
      type: 'GET',
      data: {
        _token: token
      },
      success: function(response) {
        var options = '<option value="">Select Flat</option>';
        $.each(response.flats, function(index, flat) {
          options += '<option value="' + flat.id + '"' + 
                    (selectedFlatId == flat.id ? ' selected' : '') + 
                    '>' + flat.name + '</option>';
        });
        $(targetFlatSelect).html(options);
      },
      error: function(xhr, status, error) {
        console.error('Error loading flats:', error);
      }
    });
  }

  // Handle block selection change for regular visitors
  $('#block_id').on('change', function() {
    loadFlats($(this).val(), null, '#flat_id');
  });

  // Handle block selection change for invitations
  $('#block_id_invitation').on('change', function() {
    loadFlats($(this).val(), null, '#flat_id_invitation');
  });

  // Modal handling
  $('#visitorModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var type = button.data('type');
    var visitor = button.data('visitor');
    var modal = $(this);
    
    // Reset form
    modal.find('form')[0].reset();
    modal.find('#visitor_id').val('');
    modal.find('#visitor_photo').attr('required', false);
    
    // Show regular fields by default
    modal.find('#regular_fields').show();
    modal.find('#invitation_fields').hide();
    
    // Determine if this is edit mode
    var isEdit = visitor ? true : false;
    
    console.log('Modal opening - Type:', type, 'IsEdit:', isEdit); // Debug log
    
    if (type === 'invitation') {
      // Invitation mode
      modal.find('.modal-title').text(isEdit ? 'Edit Invitation' : 'Invite Guest');
      modal.find('#form_type').val('invitation');
      modal.find('#visitorForm').attr('action', '<?php echo e(route("admin.visitor.saveInvitation")); ?>');
      modal.find('#regular_fields').hide();
      modal.find('#invitation_fields').show();
      modal.find('#block_id, #flat_id, #visitor_purpose').removeAttr('required');
      modal.find('#block_id_invitation, #flat_id_invitation').attr('required', true);
    } else {
      // Regular visitor mode
      modal.find('.modal-title').text(isEdit ? 'Edit Visitor' : 'Add Visitor');
      modal.find('#form_type').val('regular');
      modal.find('#visitorForm').attr('action', '<?php echo e(route("visitor.store")); ?>');
      modal.find('#regular_fields').show();
      modal.find('#invitation_fields').hide();
      modal.find('#block_id, #flat_id, #visitor_purpose').attr('required', true);
      modal.find('#block_id_invitation, #flat_id_invitation').removeAttr('required');
    }
    
    // Fill form data in edit mode
    if (visitor) {
      console.log('Editing visitor:', visitor); // Debug log
      
      modal.find('#visitor_id').val(visitor.id);
      modal.find('#visitor_name').val(visitor.head_name || '');
      modal.find('#visitor_mobile').val(visitor.head_phone || '');
      modal.find('#visitor_vehicle_number').val(visitor.vehicle_number || '');
      modal.find('#visitor_vehicle_type').val(visitor.vehicle_type || '');
      modal.find('#visitor_type').val(visitor.type || 'Planned');
      modal.find('#visitor_guests').val(visitor.total_members || 1);
      modal.find('#invitation_email').val(visitor.invitation_email || '');
      
      // Handle date and time
      if (visitor.stay_from) {
        var dateTime = visitor.stay_from.split(' ');
        if (dateTime.length >= 2) {
          modal.find('#visitor_date').val(dateTime[0]);
          modal.find('#visitor_time').val(dateTime[1].substring(0,5));
        } else {
          // If datetime is in ISO format
          modal.find('#visitor_date').val(visitor.stay_from.substring(0,10));
          modal.find('#visitor_time').val(visitor.stay_from.substring(11,16));
        }
      }
      
      // Handle regular visitor specific fields
      if (type === 'regular') {
        modal.find('#visitor_purpose').val(visitor.visiting_purpose || '');
        modal.find('#visitor_status').val(visitor.status || 'AllowIn');
        
        if (visitor.block_id) {
          modal.find('#block_id').val(visitor.block_id);
          // Load flats for selected block
          loadFlats(visitor.block_id, visitor.flat_id);
        }
      } else {
        // For invitation type, handle block and flat for invitation fields
        if (visitor && visitor.block_id) {
          modal.find('#block_id_invitation').val(visitor.block_id);
          loadFlats(visitor.block_id, visitor.flat_id, '#flat_id_invitation');
        }
      }
    }
    
    // Set minimum date to today for new entries
    if (!isEdit) {
      modal.find('#visitor_date').attr('min', new Date().toISOString().split('T')[0]);
    }
  });
  
  // Block change handler
  $(document).on('change', '#block_id', function() {
    var blockId = $(this).val();
    loadFlats(blockId, '');
  });
  
  // Note: loadFlats is defined earlier (uses GET /get-flats/{blockId} and returns JSON).
  // The duplicate POST-based implementation was removed to avoid conflicts.
  
  // Form validation
  $('#visitorForm').on('submit', function(e) {
    console.log('Form submission started'); // Debug log
    
    // Disable hidden fields to prevent them from being submitted
    var formType = $('#form_type').val();
    if (formType === 'regular') {
      $('#block_id_invitation, #flat_id_invitation').prop('disabled', true);
      $('#block_id, #flat_id').prop('disabled', false);
    } else {
      $('#block_id, #flat_id').prop('disabled', true);
      $('#block_id_invitation, #flat_id_invitation').prop('disabled', false);
    }
    
    var mobile = $('#visitor_mobile').val();
    var mobilePattern = /^[6-9]\d{9}$/;
    
    if (mobile && !mobilePattern.test(mobile)) {
      e.preventDefault();
      alert('Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9');
      return false;
    }
    
    var dateValue = $('#visitor_date').val();
    if (dateValue) {
      var date = new Date(dateValue);
      var today = new Date();
      today.setHours(0,0,0,0);
      
      if (date < today) {
        e.preventDefault();
        alert('Visit date cannot be in the past');
        return false;
      }
    }
    
    // Additional validation for required fields based on form type
    var formType = $('#form_type').val();
    console.log('Validating form type:', formType); // Debug log
    
    if (formType === 'regular' && $('#regular_fields').is(':visible')) {
      var blockId = $('#block_id').val();
      var flatId = $('#flat_id').val();
      var purpose = $('#visitor_purpose').val();
      
      console.log('Regular form validation - Block:', blockId, 'Flat:', flatId, 'Purpose:', purpose); // Debug log
      
      if (!blockId) {
        e.preventDefault();
        alert('Please select a block for regular visitor');
        return false;
      }
      if (!flatId) {
        e.preventDefault();
        alert('Please select a flat for regular visitor');
        return false;
      }
      if (!purpose || !purpose.trim()) {
        e.preventDefault();
        alert('Please enter the purpose of visit');
        return false;
      }
    } else if (formType === 'invitation' && $('#invitation_fields').is(':visible')) {
      var blockIdInvitation = $('#block_id_invitation').val();
      var flatIdInvitation = $('#flat_id_invitation').val();
      
      console.log('Invitation form validation - Block:', blockIdInvitation, 'Flat:', flatIdInvitation); // Debug log
      
      if (!blockIdInvitation) {
        e.preventDefault();
        alert('Please select a block for invitation');
        return false;
      }
      if (!flatIdInvitation) {
        e.preventDefault();
        alert('Please select a flat for invitation');
        return false;
      }
    }
    
    console.log('Form validation passed, submitting...'); // Debug log
    console.log('Final form values:');
    console.log('Form type:', $('#form_type').val());
    console.log('Block ID (regular):', $('#block_id').val(), 'disabled:', $('#block_id').prop('disabled'));
    console.log('Flat ID (regular):', $('#flat_id').val(), 'disabled:', $('#flat_id').prop('disabled'));
    console.log('Block ID (invitation):', $('#block_id_invitation').val(), 'disabled:', $('#block_id_invitation').prop('disabled'));
    console.log('Flat ID (invitation):', $('#flat_id_invitation').val(), 'disabled:', $('#flat_id_invitation').prop('disabled'));
  });
  
  // Delete modal handling
  $('#deleteModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    $('#delete_id').val(id);
  });
  
  // Confirm delete
  $('#confirm-delete').on('click', function() {
    var id = $('#delete_id').val();
    $.ajax({
      url: "<?php echo e(url('visitor')); ?>/" + id,
      type: "POST",
      data: {
        '_token': token,
        '_method': 'DELETE',
        'id': id,
        'action': 'delete'
      },
      success: function(data) {
        if (data.msg === 'success') {
          $('#deleteModal').modal('hide');
          location.reload();
        } else {
          alert('Delete failed');
        }
      },
      error: function() {
        alert('Error occurred while deleting');
      }
    });
  });
  
  // Auto-dismiss alerts
  setTimeout(function() {
    $('.alert').fadeOut('slow');
  }, 5000);
  
  // Debug: Add click handler to submit button
  $('#submitBtn').on('click', function(e) {
    console.log('Submit button clicked');
    console.log('Form action:', $('#visitorForm').attr('action'));
    console.log('Form method:', $('#visitorForm').attr('method'));
    console.log('Form type:', $('#form_type').val());
    console.log('Regular fields visible:', $('#regular_fields').is(':visible'));
    console.log('Invitation fields visible:', $('#invitation_fields').is(':visible'));
    console.log('Block value:', $('#block_id').val());
    console.log('Flat value:', $('#flat_id').val());
  });
  
  // Debug: Check if form exists
  console.log('Form found:', $('#visitorForm').length > 0);
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/visitor/index.blade.php ENDPATH**/ ?>