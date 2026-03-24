<?php $__env->startSection('title'); ?>
    Flats Users 
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
            <h1>Users</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">User</li>
            </ol>
           
            <?php if(Auth::User()->role == 'BA'): ?>
                <button class="btn btn-sm btn-success right ml-2" data-toggle="modal" data-target="#addModal" <?php echo e($active_count >= $login_limit ? 'disabled' : ''); ?>>Add New User</button>
            <?php endif; ?>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">User List <span id="user-count-display" class="ml-2"><?php echo e($total_count); ?> (<?php echo e($active_count); ?> active)/<?php echo e($login_limit); ?></span></h3>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table id="example1" class="table table-bordered table-striped">
                    <thead>
                      <tr>
                        <th>S No</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Role</th>
                        <th>Flats</th>
                        <th>Status</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $i = 0; ?>
                      <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php $i++; ?>
                        <?php
                          // Prefer the user's assigned building (department) for this row if present;
                          // otherwise fall back to the current building in session or the Auth user's building.
                          $rowBuildingId = null;
                          if (isset($user) && method_exists($user, 'department') && $user->department && isset($user->department->building_id)) {
                            $rowBuildingId = $user->department->building_id;
                          }
                          if (!$rowBuildingId) {
                            $rowBuildingId = session('current_building_id') ?? (Auth::user() ? Auth::user()->building_id : null);
                          }

                          $flatsCount = 0;
                          if ($rowBuildingId) {
                            $flatsCount = \App\Models\Flat::where('building_id', $rowBuildingId)
                              ->where(function($q) use ($user) {
                                $q->where('owner_id', $user->id)->orWhere('tanent_id', $user->id);
                              })->count();
                          } else {
                            // fallback: count across all buildings if no building context is available
                            $flatsCount = \App\Models\Flat::where('owner_id', $user->id)->orWhere('tanent_id', $user->id)->count();
                          }
                        ?>
                        <tr>
                          <td><?php echo e($i); ?></td>
                          <td><img src="<?php echo e($user->photo); ?>" style="width:40px"></td>
                          <td><?php echo e($user->first_name); ?> <?php echo e($user->last_name); ?></td>
                          <td><?php echo e($user->phone); ?></td>
                          <td><?php echo e($user->email); ?></td>
                        <td><?php echo e($user->gender); ?></td>
                          <td><span class="badge badge-info"><?php echo e($user->role_name); ?></span></td>
                          <td>
                            <?php if($flatsCount > 0): ?>
                              <span class="badge badge-primary"><?php echo e($flatsCount); ?></span>
                            <?php else: ?>
                              <span class="text-muted">0</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if($user->status === 'Active'): ?>
                              <span class="badge badge-success">Active</span>
                            <?php else: ?>
                              <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <a href="<?php echo e(url('user/'.$user->id.'/'.$user->building_user_id)); ?>"   class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                        
                        
                            <?php if(Auth::User()->role == 'BA'): ?>
                              <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="<?php echo e($user->id); ?>" data-building_user_id="<?php echo e($user->building_user_id); ?>" data-first_name="<?php echo e($user->first_name); ?>" data-last_name="<?php echo e($user->last_name); ?>"
                              data-email="<?php echo e($user->email); ?>" data-phone="<?php echo e($user->phone); ?>" data-gender="<?php echo e($user->gender); ?>" data-city_id="<?php echo e($user->city_id); ?>" data-address="<?php echo e($user->address); ?>" data-status="<?php echo e($user->status); ?>" data-role_name="<?php echo e($user->role_name); ?>"><i class="fa fa-edit"></i></button>
                              
                              <?php if($user->deleted_at): ?>
                                <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($user->id); ?>" data-building_user_id="<?php echo e($user->building_user_id); ?>" data-action="restore" data-role_name="<?php echo e($user->role_name); ?>"><i class="fa fa-undo"></i></button>
                              <?php else: ?>
                                <?php if($flatsCount > 0): ?>
                                  <button class="btn btn-sm btn-secondary" disabled title="User has <?php echo e($flatsCount); ?> flat(s) — cannot delete"><i class="fa fa-trash"></i></button>
                                <?php else: ?>
                                  <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($user->id); ?>" data-building_user_id="<?php echo e($user->building_user_id); ?>" data-action="delete" data-role_name="<?php echo e($user->role_name); ?>"><i class="fa fa-trash"></i></button>
                                <?php endif; ?>
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
            </div>
          </div>
        </div>
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
        <h5 class="modal-title" id="exampleModalLabel">Add New User</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(url('store-user')); ?>" method="post" class="add-form">
        <?php echo csrf_field(); ?>
        <div class="modal-body">
          <div class="error"></div>
           <div class="form-group">
            <label for="email" class="col-form-label">Email:</label>
            <div class="input-group">
              <input type="email" name="email" class="form-control" id="email" maxlength="40" placeholder="Email" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-info" id="check-user-btn">Check User</button>
              </div>
            </div>
            <div class="invalid-feedback"></div>
            <div id="user-check-result" class="mt-2"></div>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">First Name:</label>
            <input type="text" name="first_name" id="first_name" class="form-control" placeholder="First Name" minlength="3"
                          onkeypress="return event.charCode >= 65 && event.charCode <= 90 || event.charCode >= 97 && event.charCode <= 122 || event.charCode == 32" required>
            <div class="invalid-feedback"></div>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Last Name:</label>
            <input type="text" name="last_name" id="last_name" class="form-control" placeholder="Last Name" minlength="3"
                          onkeypress="return event.charCode >= 65 && event.charCode <= 90 || event.charCode >= 97 && event.charCode <= 122 || event.charCode == 32" required>
            <div class="invalid-feedback"></div>
          </div>
         
          <div class="form-group">
            <label for="phone" class="col-form-label">Phone:</label>
            <input type="text" name="phone" class="form-control" id="phone" value="<?php echo e(old('phone')); ?>" placeholder="Phone" minlength="10" maxlength="10" 
                                      onkeypress="return event.charCode >= 48 && event.charCode <= 57" required />
            <div class="invalid-feedback"></div>
            <div id="phone-exists-feedback" class="text-danger small mt-1"></div>
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Gender:</label>
            <select name="gender" class="form-control" id="gender" required>
                <option value="">--Select Gender--</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Others">Others</option>
            </select>
            <div class="invalid-feedback"></div>
          </div>
          <div class="form-group">
            <label for="status" class="col-form-label">Status:</label>
            <select name="status" class="form-control" id="status" required>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
          </div>

          

          
          <!-- <div class="form-group">
            <label for="role" class="col-form-label">City:</label>
            <select name="city_id" id="city_id" class="form-control" required>
              <?php $__empty_1 = true; $__currentLoopData = $cities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <option value="<?php echo e($city->id); ?>"><?php echo e($city->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <?php endif; ?>
            </select>
          </div> -->
          <!-- <div class="form-group">
            <label for="role" class="col-form-label">Address:</label>
            <textarea name="address" id="address" class="form-control" required></textarea>
          </div> -->
          <!-- <div class="form-group">
            <label for="role" class="col-form-label">Role:</label>
            <select name="role" class="form-control" id="role" required>
              <option value="user">User</option>
            </select>
          </div> -->
          <input type="hidden" name="role" id="role" value="user">
            <div class="form-group">
                <label>Password <span class="password-update-label"></span></label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control password" id="re_pass" placeholder="Password" minlength="8" maxlength="14" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="show-password password-icon"><i class="fa fa-eye-slash"></i></span>
                            <span class="hide-password password-icon" style="display:none;"><i class="fa fa-eye"></i></span>
                        </div>
                    </div>
                </div>
            
            </div>
          <input type="hidden" name="id" id="edit-id">
          <input type="hidden" name="existing_user_id" id="existing-user-id">
          <input type="hidden" name="created_type" id="created_type" value="direct">
          <input type="hidden" name="building_user_id" id="building_user_id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="save-button" disabled>Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Enhanced Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">User Deletion Confirmation</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="user-info-section">
          <h6><strong>User Information:</strong></h6>
          <div id="user-details" class="mb-3"></div>
          
          <h6><strong>Buildings Associated:</strong></h6>
          <div id="user-buildings-list" class="mb-3">
            <div class="text-center">
              <i class="fa fa-spinner fa-spin"></i> Loading building information...
            </div>
          </div>
          
          <!--<div id="role-selection-section" style="display: none;">-->
          <!--  <h6><strong>Select Role to Remove:</strong></h6>-->
          <!--  <div class="alert alert-info">-->
          <!--    <div class="form-group">-->
          <!--      <label for="role_select">Choose which role to remove for this user:</label>-->
          <!--      <select class="form-control" id="role_select" name="role_select">-->
          <!--        <option value="">-- Select Role to Remove --</option>-->
          <!--      </select>-->
          <!--      <small class="form-text text-muted">Select a specific role to remove from this user in the current building</small>-->
          <!--    </div>-->
          <!--  </div>-->
          <!--</div>-->

          <!--<div id="guard-info-section" style="display: none;">-->
          <!--  <h6><strong>Guard Assignments:</strong></h6>-->
          <!--  <div id="guard-assignments-list" class="mb-3">-->
          <!--    <div class="text-center">-->
          <!--      <i class="fa fa-spinner fa-spin"></i> Loading guard assignments...-->
          <!--    </div>-->
          <!--  </div>-->
            
            <!--<div id="guard-selection-section" style="display: none;">-->
            <!--  <h6><strong>Select Guard Assignment to Remove:</strong></h6>-->
            <!--  <div class="alert alert-info">-->
            <!--    <div class="form-group">-->
            <!--      <label for="guard_select">Choose which guard assignment to remove:</label>-->
            <!--      <select class="form-control" id="guard_select" name="guard_select">-->
            <!--        <option value="">-- Select Guard Assignment to Remove --</option>-->
            <!--      </select>-->
            <!--      <small class="form-text text-muted">Select a specific guard assignment to remove from this user</small>-->
            <!--    </div>-->
            <!--  </div>-->
            <!--</div>-->
          <!--</div>-->

          <!--<div id="deletion-options" style="display: none;">-->
          <!--  <h6><strong>Deletion Options:</strong></h6>-->
          <!--  <div class="alert alert-info">-->
          <!--    <div class="form-check">-->
          <!--      <input class="form-check-input" type="radio" name="delete_option" id="remove_selected_role" value="remove_selected_role">-->
          <!--      <label class="form-check-label" for="remove_selected_role">-->
          <!--        <strong>Remove Selected Role Only</strong><br>-->
          <!--        <small>Remove the selected role from this user in current building (includes guard data if role is guard/security)</small>-->
          <!--      </label>-->
          <!--    </div>-->
          <!--    <div class="form-check mt-2">-->
          <!--      <input class="form-check-input" type="radio" name="delete_option" id="remove_selected_guard" value="remove_selected_guard">-->
          <!--      <label class="form-check-label" for="remove_selected_guard">-->
          <!--        <strong>Remove Selected Guard Assignment Only</strong><br>-->
          <!--        <small>Remove the selected guard assignment from this user</small>-->
          <!--      </label>-->
          <!--    </div>-->
          <!--    <div class="form-check mt-2">-->
          <!--      <input class="form-check-input" type="radio" name="delete_option" id="remove_from_current" value="remove_current">-->
          <!--      <label class="form-check-label" for="remove_from_current">-->
          <!--        <strong>Remove All Roles from Current Building</strong><br>-->
          <!--        <small>User will be removed from this building but will remain in other buildings (includes all guard data in this building)</small>-->
          <!--      </label>-->
          <!--    </div>-->
          <!--    <div class="form-check mt-2">-->
          <!--      <input class="form-check-input" type="radio" name="delete_option" id="delete_completely" value="delete_all">-->
          <!--      <label class="form-check-label" for="delete_completely">-->
          <!--        <strong>Delete User Completely</strong><br>-->
          <!--        <small>User will be removed from all buildings and deleted from the system (includes all guard data everywhere)</small>-->
          <!--      </label>-->
          <!--    </div>-->
          <!--  </div>-->
          <!--</div>-->
          
          <div id="single-building-message" style="display: none;">
            <div class="alert alert-warning">
              <strong>Single Building User</strong><br>
              This user is only associated with the current building. Deleting will remove the user completely from the system.
            </div>
          </div>
        </div>
        
        <div id="restore-section" style="display: none;">
          <div class="alert alert-info">
            <strong>Restore User</strong><br>
            This will restore the user and make them active again.
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirm-delete-button" disabled>Confirm Action</button>
      </div>
    </div>
  </div>
</div>

<!-- Bulk Upload Modal -->
<div class="modal fade" id="bulkUploadModal" tabindex="-1" role="dialog" aria-labelledby="bulkUploadModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bulkUploadModalLabel">Bulk Upload Users</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(url('bulk-upload-users')); ?>" method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="modal-body">
          <p>
            Please upload a CSV file with user data.  
            <a href="<?php echo e(url('download-sample-users')); ?>" class="btn btn-sm btn-link">Download Sample File</a>
          </p>
          <div class="form-group">
            <label for="bulk_file">Choose File</label>
            <input type="file" name="bulk_file" id="bulk_file" class="form-control" accept=".csv, .xlsx" required>
          </div>
          <input type="hidden" name="upload_type" value="new_users">
          <input type="hidden" name="created_type" value="direct">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" <?php echo e($active_count >= $login_limit ? 'disabled' : ''); ?>>Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>



<?php $__env->startSection('script'); ?>

<style>
.form-control.is-valid {
    border-color: #28a745;
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: none;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
    line-height: 1.4;
}

.invalid-feedback.show {
    display: block;
}

.valid-feedback {
    display: none;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #28a745;
    line-height: 1.4;
}

.valid-feedback.show {
    display: block;
}

#save-button:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
    pointer-events: none;
}

#save-button:disabled:hover {
    opacity: 0.6 !important;
}
</style>

<script>
  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "<?php echo e(csrf_token()); ?>";
    var existingUserId = null;
    
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

    // Real-time validation for password field
    $('#addModal').on('keyup change blur', '#password', function() {
      validatePassword($(this).val());
    });

    // Real-time validation for phone field
    $('#addModal').on('keyup change blur', '#phone', function() {
      validatePhoneFormat($(this).val());
    });

    // Validate phone number format and rules
    function validatePhoneFormat(phone) {
      var feedback = $('#phone-error-feedback');
      if (!feedback.length) {
        $('#phone').after('<div id="phone-error-feedback" style="color: #dc3545; font-size: 12px; margin-top: 5px;"></div>');
        feedback = $('#phone-error-feedback');
      }

      phone = phone.trim();
      feedback.text('');
      
      if (!phone) {
        return true; // Empty is okay if not required
      }

      // Invalid phone numbers
      var invalidNumbers = ['0000000000', '1111111111', '2222222222', '1234567890', '0987654321', '9876543210'];
      
      // Check if phone is in invalid list
      if (invalidNumbers.includes(phone)) {
        feedback.text('This phone number is not valid.');
        return false;
      }

      // Check basic format: 10 digits starting with 6-9
      if (!/^[6-9]\d{9}$/.test(phone)) {
        feedback.text('Phone number must be 10 digits starting with 6-9.');
        return false;
      }

      // Check for all identical digits
      if (/^(\d)\1{9}$/.test(phone)) {
        feedback.text('Phone number cannot have all identical digits.');
        return false;
      }

      // Check for ascending sequence (like 1234567890)
      if (/0123456789|1234567890|2345678901|3456789012|4567890123|5678901234|6789012345|7890123456|8901234567|9012345678/.test(phone)) {
        feedback.text('Phone number cannot be a sequential pattern.');
        return false;
      }

      // Check for descending sequence
      if (/9876543210|8765432109|7654321098|6543210987|5432109876|4321098765|3210987654|2109876543|1098765432|0987654321/.test(phone)) {
        feedback.text('Phone number cannot be a sequential pattern.');
        return false;
      }

      // Check for repeating patterns
      if (/^(\d{2})\1{4}$/.test(phone)) { // Like 0101010101
        feedback.text('Phone number cannot have repeating patterns.');
        return false;
      }
      if (/^(\d{5})\1$/.test(phone)) { // Like 0123001230
        feedback.text('Phone number cannot have repeating patterns.');
        return false;
      }

      // If all validations pass
      feedback.text('');
      updateSaveButtonState();
      return true;
    }

    // Update Save button state based on validation errors
    function updateSaveButtonState() {
      var phoneError = $('#phone-error-feedback').text().trim();
      var passwordError = $('#password-error-feedback').text().trim();
      var saveBtn = $('#save-button');
      
      // If in edit mode with password not required, ignore password errors
      var isEditMode = $('#edit-id').val() ? true : false;
      
      if (phoneError || (passwordError && !isEditMode)) {
        saveBtn.prop('disabled', true);
      } else {
        saveBtn.prop('disabled', false);
      }
    }

    // Validate password
    function validatePassword(password) {
      var feedback = $('#password-error-feedback');
      if (!feedback.length) {
        $('#password').closest('.input-group').after('<div id="password-error-feedback" style="color: #dc3545; font-size: 12px; margin-top: 5px;"></div>');
        feedback = $('#password-error-feedback');
      }

      password = password || '';
      feedback.text('');
      
      if (!password) {
        return true; // Empty is okay in edit mode
      }

      // Check all password requirements
      var hasLowercase = /[a-z]/.test(password);
      var hasUppercase = /[A-Z]/.test(password);
      var hasNumber = /[0-9]/.test(password);
      var hasSpecial = /[@$!%*#?&]/.test(password);
      var noSpaces = /^\S*$/.test(password);
      var validLength = password.length >= 8 && password.length <= 14;

      // If any requirement fails
      if (!validLength || !hasLowercase || !hasUppercase || !hasNumber || !hasSpecial || !noSpaces) {
        feedback.text('Password must be 8–14 characters and include at least one uppercase letter, one lowercase letter, one number, and one special character. No spaces.');
        updateSaveButtonState();
        return false;
      }

      feedback.text('');
      updateSaveButtonState();
      return true;
    }

    // Phone number existence check and Save button control
    function checkPhoneAndToggleSave() {
      var phone = $('#phone').val();
      var feedback = $('#phone-exists-feedback');
      var saveBtn = $('#save-button');
      feedback.text('');
      saveBtn.prop('disabled', true);
      
      // First validate format
      if (!validatePhoneFormat(phone)) {
        saveBtn.prop('disabled', true);
        return;
      }
      
      if (phone.length === 10) {
        $.ajax({
          url: '/check-phone',
          type: 'POST',
          data: {
            phone: phone,
            _token: token
          },
          success: function(res) {
            if (res.exists) {
              feedback.text('This phone number is already in use.');
              saveBtn.prop('disabled', true);
            } else {
              feedback.text('');
              saveBtn.prop('disabled', false);
            }
          },
          error: function() {
            feedback.text('Could not verify phone number.');
            saveBtn.prop('disabled', true);
          }
        });
      } else {
        saveBtn.prop('disabled', true);
      }
    }

    // Always disable Save button on add, enable on edit
    $('#addModal').on('show.bs.modal', function(e) {
      var button = $(e.relatedTarget);
      var edit_id = button && button.data('id') ? button.data('id') : '';
      var saveBtn = $('#save-button');
      if (!edit_id) {
        // Add mode: disable Save until phone is validated
        saveBtn.prop('disabled', true);
        setTimeout(function() {
          $('#phone').trigger('blur');
        }, 300);
      } else {
        // Edit mode: enable Save if no validation errors
        updateSaveButtonState();
      }
    });

    $('#addModal').on('input blur', '#phone', function() {
      var id = $('#edit-id').val();
      if (!id) {
        checkPhoneAndToggleSave();
      }
    });

    // Function to validate password field
    // function validatePasswordField(field) {
    //     const value = field.val();
    //     let isValid = true;
    //     let errorMessages = [];
    //     let successMessages = [];

    //     // Clear previous validation state
    //     field.removeClass('is-valid is-invalid');
    //     field.siblings('.invalid-feedback').text('');

    //     // Skip validation if field is not required and empty
    //     if (!field.attr('required') && !value) {
    //         return;
    //     }

    //     // Password validation rules
    //     if (field.attr('required') && !value) {
    //         errorMessages.push('Password is required');
    //         isValid = false;
    //     } else if (value) {
    //         // Check length requirements
    //         if (value.length < 8) {
    //             errorMessages.push('❌ Must be at least 8 characters (currently ' + value.length + ')');
    //             isValid = false;
    //         } else {
    //             successMessages.push('✅ Length requirement met (' + value.length + ' characters)');
    //         }
            
    //         if (value.length > 14) {
    //             errorMessages.push('❌ Must not exceed 14 characters (currently ' + value.length + ')');
    //             isValid = false;
    //         }
            
    //         // Check for letters
    //         if (!/[a-zA-Z]/.test(value)) {
    //             errorMessages.push('❌ Must contain at least one letter (A-Z or a-z)');
    //             isValid = false;
    //         } else {
    //             successMessages.push('✅ Contains letters');
    //         }
            
    //         // Check for punctuation marks
    //         if (!/[!@#$%^&*(),.?":{}|<>]/.test(value)) {
    //             errorMessages.push('❌ Must contain at least one punctuation mark: ! @ # $ % ^ & * ( ) , . ? " : { } | < >');
    //             isValid = false;
    //         } else {
    //             successMessages.push('✅ Contains punctuation marks');
    //         }

    //         // Check for numbers (optional but good practice)
    //         if (!/[0-9]/.test(value)) {
    //             errorMessages.push('💡 Tip: Adding numbers makes your password stronger');
    //         } else {
    //             successMessages.push('✅ Contains numbers');
    //         }
    //     }

    //     // Combine error and success messages
    //     let finalMessage = '';
    //     if (errorMessages.length > 0) {
    //         finalMessage = errorMessages.join('<br>');
    //         if (successMessages.length > 0) {
    //             finalMessage += '<br><br>' + successMessages.join('<br>');
    //         }
    //     } else if (successMessages.length > 0) {
    //         finalMessage = '✅ Password meets all requirements!';
    //     }

    //     updatePasswordFieldStatus(field, isValid, finalMessage);
    //     updateSaveButtonState(field, isValid);
    // }

    // Function to update password field status and show/hide error messages
    // function updatePasswordFieldStatus(field, isValid, errorMessage) {
    //     const invalidFeedback = field.siblings('.invalid-feedback');
    //     const validFeedback = field.siblings('.valid-feedback');
        
    //     // Clear both feedback elements
    //     invalidFeedback.html('').removeClass('show').hide();
    //     validFeedback.html('').removeClass('show').hide();
        
    //     if (isValid && field.val()) {
    //         field.removeClass('is-invalid').addClass('is-valid');
    //         validFeedback.html(errorMessage).addClass('show').show();
    //     } else if (!isValid && errorMessage) {
    //         field.removeClass('is-valid').addClass('is-invalid');
    //         invalidFeedback.html(errorMessage).addClass('show').show();
    //     } else {
    //         // Empty field that's not required
    //         field.removeClass('is-valid is-invalid');
    //     }
    // }

    // Function to update save button state based on password validation
    // REMOVED - replaced with the updateSaveButtonState() above

    // Also validate password when modal opens to set initial button state
    $('#addModal').on('shown.bs.modal', function() {
        setTimeout(function() {
            const passwordField = $('#password');
            if (passwordField.length) {
                validatePassword(passwordField.val());
                updateSaveButtonState(); // Update button state after validation
            }
        }, 100);
    });

    $('#deleteModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      id = button.data('id');
      var building_user_id = button.data('building_user_id');
      var role_name = button.data('role_name');
      action = button.data('action');
      
      // Store building_user_id and role_name for use in delete
      $(this).data('building_user_id', building_user_id);
      $(this).data('role_name', role_name);
      
      // Reset modal state
      $('#user-info-section').show();
      $('#restore-section').hide();
      $('#role-selection-section').hide();
      $('#guard-info-section').hide();
      $('#guard-selection-section').hide();
    //   $('#deletion-options').hide();
      $('#single-building-message').hide();
      $('#confirm-delete-button').prop('disabled', true);
      $('input[name="delete_option"]').prop('checked', false);
    //   $('#role_select').empty().append('<option value="">-- Select Role to Remove --</option>');
    //   $('#guard_select').empty().append('<option value="">-- Select Guard Assignment to Remove --</option>');
    //   $('#guard-assignments-list').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading guard assignments...</div>');
      loadUserBuildingInfo(id);
      if(action == 'delete'){
        $('#deleteModalLabel').text('Delete ' + role_name + ' Assignment');
        $('#confirm-delete-button').removeClass('btn-success').addClass('btn-danger').text('Confirm Delete');
        
        // Show confirmation message
        var confirmMsg = '<div class="alert alert-warning">';
        confirmMsg += '<strong>Delete Role Assignment</strong><br>';
        confirmMsg += 'Are you sure you want to delete this user\'s <strong>' + role_name + '</strong> role in this building?<br>';
        confirmMsg += '<small>The user will lose this role but will remain in the system and may have other roles.</small>';
        confirmMsg += '</div>';
        $('#user-buildings-list').html(confirmMsg);
        
        $('#confirm-delete-button').prop('disabled', false);
      } else {
        $('#deleteModalLabel').text('Restore User');
        $('#user-info-section').hide();
        $('#restore-section').show();
        $('#confirm-delete-button').removeClass('btn-danger').addClass('btn-success').text('Confirm Restore').prop('disabled', false);
      }
    });

    // Function to load user building information
    function loadUserBuildingInfo(userId) {
      $.ajax({
        url: "<?php echo e(url('get-user-building-info')); ?>",
        type: "POST",
        data: {
          '_token': token,
          'user_id': userId
        },
        success: function(response) {
          if(response.success) {
            var user = response.user;
            var buildings = response.buildings;
            
            // Display user information
            var userHtml = '<div class="card card-body bg-light">';
            userHtml += '<strong>' + user.name + '</strong>';
            if(user.role === 'BA') {
              userHtml += ' <span class="badge badge-warning">Building Admin</span>';
            }
            userHtml += '<br>';
            userHtml += '<small>Email: ' + user.email + '</small><br>';
            userHtml += '<small>Phone: ' + user.phone + '</small><br>';
            userHtml += '<small>Role: ' + (user.role || 'User') + '</small>';
            userHtml += '</div>';
            $('#user-details').html(userHtml);
            
            // Display buildings information
            var buildingsHtml = '';
            if(buildings.length > 0) {
              buildingsHtml += '<div class="row">';
              buildings.forEach(function(building, index) {
                var isCurrentBuilding = building.is_current;
                buildingsHtml += '<div class="col-md-6 mb-2">';
                buildingsHtml += '<div class="card ' + (isCurrentBuilding ? 'border-primary' : 'border-secondary') + '">';
                buildingsHtml += '<div class="card-body p-2">';
                buildingsHtml += '<h6 class="card-title mb-1">' + building.name;
                if(isCurrentBuilding) {
                  buildingsHtml += ' <span class="badge badge-primary">Current</span>';
                }
                buildingsHtml += '</h6>';
                buildingsHtml += '<small class="text-muted">Builder: ' + building.builder_name + '</small><br>';
                buildingsHtml += '<small class="text-muted">Role: ' + (building.role || 'User') + '</small>';
                buildingsHtml += '</div></div></div>';
              });
              buildingsHtml += '</div>';
              
              // Set the buildings HTML first
              $('#user-buildings-list').html(buildingsHtml);
              
              // Populate role dropdown with current building roles
              var currentBuildingRoles = [];
              buildings.forEach(function(building) {
                if(building.is_current && building.role) {
                  currentBuildingRoles.push(building.role);
                }
              });
              
              // Add roles to dropdown
            //   if(currentBuildingRoles.length > 0) {
            //     currentBuildingRoles.forEach(function(role) {
            //       $('#role_select').append('<option value="' + role + '">' + role + '</option>');
            //     });
            //     $('#role-selection-section').show();
            //   }
              
              // Check for guard data and show guard info if applicable
              checkAndDisplayGuardInfo(userId);
              
              // Show appropriate options based on user role and building count
              if(user.role === 'BA') {
                // For BA users, only show removal from current building option
                $('#role-selection-section').hide();
                // $('#deletion-options').hide();
                $('#single-building-message').hide();
                
                // Show special BA message
                var baMessageHtml = '<div class="alert alert-warning mt-3">';
                baMessageHtml += '<strong><i class="fa fa-shield"></i> Building Admin Protection</strong><br>';
                baMessageHtml += 'This user is a Building Admin created by Super Admin. ';
                baMessageHtml += 'BA accounts cannot be completely deleted - only their assignment to this building will be removed.';
                baMessageHtml += '</div>';
                $('#user-buildings-list').append(baMessageHtml);
                
                $('#confirm-delete-button').prop('disabled', false).text('Remove from Building');
              } else if(buildings.length > 1) {
                // $('#deletion-options').show();
                $('#single-building-message').hide();
              } else {
                // $('#deletion-options').hide();
                $('#single-building-message').show();
                $('#confirm-delete-button').prop('disabled', false);
              }
            } else {
              buildingsHtml = '<div class="alert alert-warning">No buildings found for this user.</div>';
              $('#user-buildings-list').html(buildingsHtml);
              $('#confirm-delete-button').prop('disabled', false);
            }
          } else {
            $('#user-buildings-list').html('<div class="alert alert-danger">Error loading user information.</div>');
          }
        },
        error: function() {
          $('#user-buildings-list').html('<div class="alert alert-danger">Error loading user information.</div>');
        }
      });
    }

    // Function to check and display guard information
    function checkAndDisplayGuardInfo(userId) {
      $.ajax({
        url: "<?php echo e(url('get-user-guard-info')); ?>",
        type: "POST",
        data: {
          '_token': token,
          'user_id': userId
        },
        success: function(response) {
          if(response.success && response.guard_data && response.guard_data.length > 0) {
            // Display guard assignments like buildings
            var guardHtml = '<div class="row">';
            response.guard_data.forEach(function(guard, index) {
              var isCurrentBuilding = guard.is_current_building;
              guardHtml += '<div class="col-md-6 mb-2">';
              guardHtml += '<div class="card ' + (isCurrentBuilding ? 'border-primary' : 'border-secondary') + '">';
              guardHtml += '<div class="card-body p-2">';
              guardHtml += '<h6 class="card-title mb-1">' + guard.building_name;
              if(isCurrentBuilding) {
                guardHtml += ' <span class="badge badge-primary">Current</span>';
              }
              guardHtml += '</h6>';
              if(guard.block_name) {
                guardHtml += '<small class="text-muted">Block: ' + guard.block_name + '</small><br>';
              }
              if(guard.gate_name) {
                guardHtml += '<small class="text-muted">Gate: ' + guard.gate_name + '</small><br>';
              }
              guardHtml += '<small class="text-muted">Shift: ' + (guard.shift || 'Not specified') + '</small><br>';
              guardHtml += '<small class="text-muted">Status: ' + (guard.status || 'Active') + '</small>';
              guardHtml += '</div></div></div>';
              
              // Add to dropdown
              var guardLabel = guard.building_name;
              if(guard.block_name) guardLabel += ' - ' + guard.block_name;
              if(guard.gate_name) guardLabel += ' - ' + guard.gate_name;
              guardLabel += ' (' + (guard.shift || 'No shift') + ')';
              
            //   $('#guard_select').append('<option value="' + guard.id + '">' + guardLabel + '</option>');
            });
            guardHtml += '</div>';
            
            // $('#guard-assignments-list').html(guardHtml);
            $('#guard-info-section').show();
            $('#guard-selection-section').show();
          } else {
            // $('#guard-assignments-list').html('<div class="alert alert-info">No guard assignments found for this user.</div>');
            $('#guard-info-section').show();
          }
        },
        error: function() {
        //   $('#guard-assignments-list').html('<div class="alert alert-danger">Error loading guard assignments.</div>');
          $('#guard-info-section').show();
        }
      });
    }

    // Handle role selection change
    // $(document).on('change', '#role_select', function() {
    //   var selectedRole = $(this).val();
    //   if(selectedRole) {
    //     // Enable the "Remove Selected Role" option when a role is selected
    //     $('#remove_selected_role').prop('disabled', false);
    //     // Auto-select the remove selected role option
    //     $('#remove_selected_role').prop('checked', true);
    //     $('#confirm-delete-button').prop('disabled', false);
    //   } else {
    //     $('#remove_selected_role').prop('disabled', true).prop('checked', false);
    //     // Check if any other option is selected
    //     if(!$('input[name="delete_option"]:checked').length) {
    //       $('#confirm-delete-button').prop('disabled', true);
    //     }
    //   }
    // });

    // Handle guard selection change
    $(document).on('change', '#guard_select', function() {
      var selectedGuard = $(this).val();
      if(selectedGuard) {
        // Enable the "Remove Selected Guard" option when a guard is selected
        $('#remove_selected_guard').prop('disabled', false);
        // Auto-select the remove selected guard option
        $('#remove_selected_guard').prop('checked', true);
        $('#confirm-delete-button').prop('disabled', false);
      } else {
        $('#remove_selected_guard').prop('disabled', true).prop('checked', false);
        // Check if any other option is selected
        if(!$('input[name="delete_option"]:checked').length) {
          $('#confirm-delete-button').prop('disabled', true);
        }
      }
    });

    // Handle deletion option selection
    $(document).on('change', 'input[name="delete_option"]', function() {
      var selectedOption = $(this).val();
      
      if(selectedOption === 'remove_selected_role') {
        // Require role selection for this option
        // var selectedRole = $('#role_select').val();
        // if(!selectedRole) {
        //   alert('Please select a role to remove first.');
        //   $(this).prop('checked', false);
        //   $('#confirm-delete-button').prop('disabled', true);
        //   return;
        // }
      } else if(selectedOption === 'remove_selected_guard') {
        // Require guard selection for this option
        // var selectedGuard = $('#guard_select').val();
        // if(!selectedGuard) {
        //   alert('Please select a guard assignment to remove first.');
        //   $(this).prop('checked', false);
        //   $('#confirm-delete-button').prop('disabled', true);
        //   return;
        // }
      }
      
      $('#confirm-delete-button').prop('disabled', false);
    });

    // Handle confirm delete button click
    $(document).on('click','#confirm-delete-button',function(){
      var deleteOption = '';
      var selectedRole = '';
      var selectedGuard = '';
      var building_user_id = $('#deleteModal').data('building_user_id');
      
      if(action == 'delete') {
        deleteOption = $('input[name="delete_option"]:checked').val() || 'delete_all';
        
        if(deleteOption === 'remove_selected_role') {
          selectedRole = $('#role_select').val();
          if(!selectedRole) {
            alert('Please select a role to remove.');
            return;
          }
        }
        
        if(deleteOption === 'remove_selected_guard') {
          selectedGuard = $('#guard_select').val();
          if(!selectedGuard) {
            alert('Please select a guard assignment to remove.');
            return;
          }
        }
      }
      
      var url = "<?php echo e(url('delete-user-enhanced')); ?>";
      $.ajax({
        url : url,
        type: "POST",
        data : {
          '_token': token,
          'id': id,
          'building_user_id': building_user_id,
          'action': action,
          'delete_option': deleteOption,
          'selected_role': selectedRole,
          'selected_guard': selectedGuard
        },
        success: function(data) {
          if(data.success) {
            $('#deleteModal').modal('hide');
            if(data.message) {
              alert(data.message);
            }
            window.location.reload();
          } else {
            alert(data.message || 'An error occurred');
          }
        },
        error: function() {
          alert('An error occurred while processing the request');
        }
      });
    });

    $('#addModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      
      // Reset form and clear all fields
      $('.add-form')[0].reset();
      $('.error').html('');
      $('#user-check-result').html('');
      $('#existing-user-id').val('');
       $('#building_user_id').val('');
      existingUserId = null;
      
      // Show all form fields and reset button text
      $('.add-form .form-group').show();
      $('#save-button').text('Save');
      
      // Remove any existing add_existing hidden inputs
      $('input[name="add_existing"]').remove();
      
      
      // Set default values for new user
      $('.modal-title').text('Add New User');
      $('.password').attr('required', true).attr('minlength', 7);
      $('.password-update-label').text('');
      
      // If editing, populate the form with user data
      if(edit_id) {
        $('.modal-title').text('Edit User');
        $('.password').removeAttr('required').removeAttr('minlength');
        $('.password-update-label').text('(Leave blank to keep current password)');
        
        // Set form values from data attributes
        $('#first_name').val(button.data('first_name') || '');
        $('#last_name').val(button.data('last_name') || '');
        $('#email').val(button.data('email') || '');
        $('#phone').val(button.data('phone') || '');
        $('#gender').val(button.data('gender') || '');
        $('#city_id').val(button.data('city_id') || '');
        $('#address').val(button.data('address') || '');
        $('#status').val(button.data('status') || 'Active');
        $('#building_user_id').val(button.data('building_user_id') || '');
        $('#edit-id').val(edit_id);
      } else {
        // Set default status for new user
        $('#status').val('Active');
      }
      // Ensure flats are filtered for the selected/default building
      // Trigger change so flats dropdown is populated according to selected building
      $('#building_select').trigger('change');

      // If editing and the button provides building/flat info, preselect them
      var btnBuilding = button.data('building_id') || null;
      var btnFlat = button.data('flat_id') || null;
      if(btnBuilding) {
        $('#building_select').val(btnBuilding).trigger('change');
        if(btnFlat) {
          // Delay to ensure options are filtered
          setTimeout(function(){ $('#flat_select').val(btnFlat); }, 50);
        }
      }
      $('#edit-id').val(edit_id);
      $('#first_name').val(button.data('first_name'));
      $('#last_name').val(button.data('last_name'));
      $('#email').val(button.data('email'));
      $('#phone').val(button.data('phone'));
      $('#gender').val(button.data('gender'));
      $('#city_id').val(button.data('city_id'));
      $('#address').val(button.data('address'));
      $('.modal-title').text('Add New User');
      $('#password').attr('required',true);
    //   $('#password').attr('disabled',false);
      $('#password').attr('minlength',7);
      $('.password-update-label').text('');
      if(edit_id){
          $('.modal-title').text('Update User');
          $('#password').attr('required',false);
          $('#password').attr('minlength',0);
        //   $('#password').attr('disabled',true);
        $('.password-update-label').text("(Leave blank if you don't want to update)");
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


    // Check User functionality
    $('#check-user-btn').on('click', function() {
        var email = $('#email').val().trim();
        
        if (!email) {
            $('#user-check-result').html('<div class="alert alert-warning">Please enter an email address first.</div>');
            return;
        }
        
        // Disable button and show loading
        $(this).prop('disabled', true).text('Checking...');
        $('#user-check-result').html('<div class="text-info">Checking user...</div>');
        
        $.ajax({
            url: "<?php echo e(url('get-user-by-email')); ?>",
            type: "POST",
            data: {
                '_token': token,
                'email': email
            },
            success: function(response) {
                $('#check-user-btn').prop('disabled', false).text('Check User');
                
                if (response.success && response.data) {
                    // User exists - populate form
                    var user = response.data;
                    existingUserId = user.id;
                    $('#existing-user-id').val(user.id);
                     $('#building_user_id').val(user.building_user_id);
                    
                    $('#first_name').val(user.first_name || '');
                    $('#last_name').val(user.last_name || '');
                    $('#phone').val(user.phone || '');
                    $('#gender').val(user.gender || '');
                    
                    // Make password optional for existing users
                    $('#password').removeAttr('required');
                    $('.password-update-label').text('(Leave blank to keep current password)');
                    
                    // Automatically set up for adding existing user
                    $('#existing-user-id').val(user.id);
                    
                    // Add hidden input to indicate we're adding existing user
                    if ($('input[name="add_existing"]').length === 0) {
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'add_existing',
                            value: '1'
                        }).appendTo('.add-form');
                    }
                    
                    // Hide all form fields except the result
                    $('.add-form .form-group').not(':has(#user-check-result)').hide();
                    
                    $('#user-check-result').html(
                        '<div class="alert alert-success">' +
                        '<strong>User Found!</strong><br>' +
                        'Name: ' + (user.first_name || '') + ' ' + (user.last_name || '') + '<br>' +
                        'Phone: ' + (user.phone || 'N/A') + '<br>' +
                        '<p class="mt-2 mb-1"><strong>This user will be added to the current building.</strong></p>' +
                        '</div>'
                    );
                    
                    // Change save button text and ensure it's enabled and clickable
                    $('#save-button')
                        .text('Add User to Building')
                        .prop('disabled', false)
                        .removeClass('disabled')
                        .css('pointer-events', 'auto')
                        .css('opacity', '1')
                        .css('cursor', 'pointer')
                        .removeAttr('title');
                    
                    // Force update button state to ensure it stays enabled
                    updateSaveButtonState($('#password'), true);
                } else {
                  // User not found - new user
                  existingUserId = null;
                  $('#existing-user-id').val('');
                  // Clear all form fields for new user
                  $('#first_name').val('');
                  $('#last_name').val('');
                  $('#phone').val('');
                  $('#gender').val('');
                  $('#city_id').val('');
                  $('#address').val('');
                  $('#status').val('Active');
                  $('#building_user_id').val('');
                  $('#password').val('').attr('required', true);
                  $('.password-update-label').text('');

                  // Show all form fields for new user
                  $('.add-form .form-group').show();

                  $('#user-check-result').html(
                    '<div class="alert alert-info">' +
                    '<strong>New User</strong><br>' +
                    'This email is not registered. A new user will be created.' +
                    '</div>'
                  );
                }
            },
            error: function() {
                $('#check-user-btn').prop('disabled', false).text('Check User');
                $('#user-check-result').html('<div class="alert alert-danger">Error checking user. Please try again.</div>');
            }
        });
    });

    // Filter flats dropdown based on selected building
    function filterFlats(buildingId) {
      if(!buildingId) {
        // show placeholder only
        $('#flat_select option').hide();
        $('#flat_select option[value=""]').show();
        $('#flat_select').val('');
        return;
      }
      $('#flat_select option').each(function(){
        var bid = $(this).data('building-id') ? String($(this).data('building-id')) : '';
        if(bid === String(buildingId)) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
      // If currently selected flat does not belong to building, reset
      var cur = $('#flat_select').val();
      if(cur) {
        var curOpt = $('#flat_select option[value="'+cur+'"]');
        if(curOpt.length && String(curOpt.data('building-id')) !== String(buildingId)) {
          $('#flat_select').val('');
        }
      }
    }

    // Bind change handler
    $(document).on('change', '#building_select', function(){
      var b = $(this).val();
      filterFlats(b);
    });

    // Add debugging for form submission
    $('.add-form').on('submit', function(e) {
        console.log('Form submitted');
        console.log('Add existing mode:', $('input[name="add_existing"]').val());
        console.log('Existing user ID:', $('#existing-user-id').val());
        
        // If we're adding existing user, make sure no validation blocks it
        if ($('input[name="add_existing"]').val() === '1') {
            console.log('Submitting existing user form');
            // Remove any validation that might block submission
            $('.add-form input, .add-form select').removeAttr('required');
        }
    });

    // Ensure save button is always clickable
    $(document).on('click', '#save-button', function(e) {
        console.log('Save button clicked');
        
        // If we're in add existing mode, make sure form can submit
        if ($('input[name="add_existing"]').val() === '1') {
            console.log('Add existing user - removing validation');
            $('.add-form input, .add-form select').removeAttr('required');
        }
    });

    // Handle "create new instead" link click
    $(document).on('click', '#create-new-instead', function(e) {
        e.preventDefault();
        
        // Reset to new user mode
        existingUserId = null;
        $('#existing-user-id').val('');
        $('#password').attr('required', true);
        $('.password-update-label').text('');
        
        // Show all form fields
        $('.add-form .form-group').show();
        
        // Clear the result area
        $('#user-check-result').html(
            '<div class="alert alert-info">' +
            '<strong>New User Mode</strong><br>' +
            'Fill in all the details below to create a new user with this email.' +
            '</div>'
        );
        
        // Reset save button text
        $('#save-button').text('Save');
        
        // Remove add_existing hidden input if it exists
        $('input[name="add_existing"]').remove();
    });

    // Status is updated via the modal form submission; no toggle button handler.


  });
</script>
<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/user/index.blade.php ENDPATH**/ ?>