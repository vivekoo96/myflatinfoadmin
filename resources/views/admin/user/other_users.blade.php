@extends('layouts.admin')


@section('title')
    Other Users 
@endsection

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-md-12">
                @if(session()->has('error'))
                <div class="alert alert-danger">
                    {{ session()->get('error') }}
                </div>
                @endif
                @if(session()->has('success'))
                <div class="alert alert-success">
                    {{ session()->get('success') }}
                </div>
                @endif
            </div>
          <div class="col-sm-6">
            <h1>Other Users</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Other Users</li>
            </ol>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">User List <span id="other-user-count" class="ml-2">{{count($users)}}/{{Auth::user()->building->no_of_other_users}}</span></h3>
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
                        <th>Status</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $i = 0; ?>
                      @forelse($users as $user)
                        <?php $i++; ?>
                        <tr>
                          <td>{{$i}}</td>
                          <td><img src="{{$user->photo}}" style="width:40px"></td>
                          <td>{{$user->first_name}} {{$user->last_name}}</td>
                          <td>{{$user->phone}}</td>
                          <td>{{$user->email}}</td>
                        <td>{{$user->gender}}</td>
                          <td><span class="badge badge-info">{{ $user->role_name }}</span></td>
                          <td>
                            @if($user->status === 'Active')
                              <span class="badge badge-success">Active</span>
                            @else
                              <span class="badge badge-danger">Inactive</span>
                            @endif
                          </td>
                          <td>
                             <a href="{{url('user/'.$user->id.'/'.$user->building_user_id)}}"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                            @if(Auth::User()->role == 'BA')
                              <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$user->id}}" data-building_user_id="{{$user->building_user_id}}" data-first_name="{{$user->first_name}}" data-last_name="{{$user->last_name}}"
                              data-email="{{$user->email}}" data-phone="{{$user->phone}}" data-gender="{{$user->gender}}" data-city_id="{{$user->city_id}}" data-address="{{$user->address}}" data-status="{{$user->status}}" data-role_name="{{$user->role_name}}"><i class="fa fa-edit"></i></button>
                              
                              @if($user->deleted_at)
                                <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="{{$user->id}}" data-building_user_id="{{$user->building_user_id}}" data-action="restore" data-role_name="{{$user->role_name}}"><i class="fa fa-undo"></i></button>
                              @else
                                <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$user->id}}" data-building_user_id="{{$user->building_user_id}}" data-action="delete" data-role_name="{{$user->role_name}}"><i class="fa fa-trash"></i></button>
                              @endif
                            @endif
                          </td>
                        </tr>
                      @empty
                      @endforelse
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
      <form action="{{url('store-user')}}" method="post" class="add-form">
        @csrf
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
            <input type="text" name="phone" class="form-control" id="phone" value="{{old('phone')}}" placeholder="Phone" minlength="10" maxlength="10" 
                                      onkeypress="return event.charCode >= 48 && event.charCode <= 57" required />
            <div class="invalid-feedback"></div>
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
              @forelse($cities as $city)
                <option value="{{$city->id}}">{{$city->name}}</option>
              @empty
              @endforelse
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
          <input type="hidden" name="created_type" id="created_type" value="other">
          <input type="hidden" name="building_user_id" id="building_user_id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="save-button">Save</button>
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
          
          <div id="role-selection-section" style="display: none;">
            <h6><strong>Select Role to Remove:</strong></h6>
            <div class="alert alert-info">
              <div class="form-group">
                <label for="role_select">Choose which role to remove for this user:</label>
                <select class="form-control" id="role_select" name="role_select">
                  <option value="">-- Select Role to Remove --</option>
                </select>
                <small class="form-text text-muted">Select a specific role to remove from this user in the current building</small>
              </div>
            </div>
          </div>

          <div id="guard-info-section" style="display: none;">
            <h6><strong>Guard Assignments:</strong></h6>
            <div id="guard-assignments-list" class="mb-3">
              <div class="text-center">
                <i class="fa fa-spinner fa-spin"></i> Loading guard assignments...
              </div>
            </div>
            
            <div id="guard-selection-section" style="display: none;">
              <h6><strong>Select Guard Assignment to Remove:</strong></h6>
              <div class="alert alert-info">
                <div class="form-group">
                  <label for="guard_select">Choose which guard assignment to remove:</label>
                  <select class="form-control" id="guard_select" name="guard_select">
                    <option value="">-- Select Guard Assignment to Remove --</option>
                  </select>
                  <small class="form-text text-muted">Select a specific guard assignment to remove from this user</small>
                </div>
              </div>
            </div>
          </div>

          <div id="deletion-options" style="display: none;">
            <h6><strong>Deletion Options:</strong></h6>
            <div class="alert alert-info">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="delete_option" id="remove_selected_role" value="remove_selected_role">
                <label class="form-check-label" for="remove_selected_role">
                  <strong>Remove Selected Role Only</strong><br>
                  <small>Remove the selected role from this user in current building (includes guard data if role is guard/security)</small>
                </label>
              </div>
              <div class="form-check mt-2">
                <input class="form-check-input" type="radio" name="delete_option" id="remove_selected_guard" value="remove_selected_guard">
                <label class="form-check-label" for="remove_selected_guard">
                  <strong>Remove Selected Guard Assignment Only</strong><br>
                  <small>Remove the selected guard assignment from this user</small>
                </label>
              </div>
              <div class="form-check mt-2">
                <input class="form-check-input" type="radio" name="delete_option" id="remove_from_current" value="remove_current">
                <label class="form-check-label" for="remove_from_current">
                  <strong>Remove All Roles from Current Building</strong><br>
                  <small>User will be removed from this building but will remain in other buildings (includes all guard data in this building)</small>
                </label>
              </div>
              <div class="form-check mt-2">
                <input class="form-check-input" type="radio" name="delete_option" id="delete_completely" value="delete_all">
                <label class="form-check-label" for="delete_completely">
                  <strong>Delete User Completely</strong><br>
                  <small>User will be removed from all buildings and deleted from the system (includes all guard data everywhere)</small>
                </label>
              </div>
            </div>
          </div>
          
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



@section('script')

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
    var token = "{{csrf_token()}}";
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

    // Real-time validation for password field only
    $('#addModal').on('keyup change blur', '#password', function() {
        validatePasswordField($(this));
    });

    // Function to update save button state based on password validation
    function updateSaveButtonState(passwordField, isPasswordValid) {
        const saveButton = $('#save-button');
        const passwordValue = passwordField.val();
        const isPasswordRequired = passwordField.attr('required');
        
        // Skip validation if we're adding existing user
        if ($('input[name="add_existing"]').val() === '1') {
            saveButton.prop('disabled', false);
            saveButton.css('opacity', '1');
            saveButton.css('cursor', 'pointer');
            saveButton.removeAttr('title');
            return;
        }
        
        // Check if password validation should affect button state
        let shouldDisableButton = false;
        
        if (isPasswordRequired && !passwordValue) {
            // Required password is empty
            shouldDisableButton = true;
        } else if (passwordValue && !isPasswordValid) {
            // Password has value but is invalid
            shouldDisableButton = true;
        }
        
        if (shouldDisableButton) {
            saveButton.prop('disabled', true);
            saveButton.css('opacity', '0.6');
            saveButton.css('cursor', 'not-allowed');
            saveButton.attr('title', 'Please fix password errors before saving');
        } else {
            saveButton.prop('disabled', false);
            saveButton.css('opacity', '1');
            saveButton.css('cursor', 'pointer');
            saveButton.removeAttr('title');
        }
    }

    // Also validate password when modal opens to set initial button state
    $('#addModal').on('shown.bs.modal', function() {
        setTimeout(function() {
            const passwordField = $('#password');
            if (passwordField.length) {
                validatePasswordField(passwordField);
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
      $('#deletion-options').hide();
      $('#single-building-message').hide();
      $('#confirm-delete-button').prop('disabled', true);
      $('input[name="delete_option"]').prop('checked', false);
      $('#role_select').empty().append('<option value="">-- Select Role to Remove --</option>');
      $('#guard_select').empty().append('<option value="">-- Select Guard Assignment to Remove --</option>');
      $('#guard-assignments-list').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading guard assignments...</div>');

      // Always load user details in modal
      $('#user-details').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading user details...</div>');
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
      var buildingUserId = $('#deleteModal').data('building_user_id');
      $.ajax({
        url: "{{url('get-user-building-info')}}",
        type: "POST",
        data: {
          '_token': token,
          'user_id': userId,
          'building_user_id': buildingUserId
        },
        success: function(response) {
          if(response.success) {
            var user = response.user;
            var buildings = response.buildings;
            // User info card
            var userHtml = '<div class="card card-body bg-light mb-2">';
            userHtml += '<strong>' + (user.name || '') + '</strong><br>';
            userHtml += 'Email: ' + (user.email || '') + '<br>';
            userHtml += 'Phone: ' + (user.phone || '') + '<br>';
            // Show only the current role for the selected building_user_id
            var buildingUserId = $('#deleteModal').data('building_user_id');
            var currentRole = '';
            buildings.forEach(function(building) {
              if (building.id && buildingUserId && building.id == buildingUserId && building.role) {
                currentRole = building.role;
              }
            });
            userHtml += 'Role: ' + (currentRole || (buildings.length > 0 ? (buildings[0].role || 'User') : 'User')) + '<br>';
            userHtml += '</div>';
            $('#user-details').html(userHtml);

            // Populate role dropdown with only the current role
            var $roleSelect = $('#role_select');
            $roleSelect.empty().append('<option value="">-- Select Role to Remove --</option>');
            if(currentRole) {
              $roleSelect.append('<option value="' + currentRole + '">' + currentRole + '</option>');
            } 

            // Building info card(s)
            var buildingsHtml = '';
            if(buildings.length > 0) {
              buildings.forEach(function(building) {
                buildingsHtml += '<div class="card card-body mb-2">';
                buildingsHtml += '<strong>' + building.name + '</strong> ';
                buildingsHtml += 'Builder: ' + building.builder_name + '<br>';
                buildingsHtml += 'Role: ' + (building.role || 'User') + '<br>';
                buildingsHtml += '</div>';
              });
            } else {
              buildingsHtml = '<div class="alert alert-info">No building information found for this user.</div>';
            }
            $('#user-buildings-list').html(buildingsHtml);

            // Yellow warning for single-building user
            if(buildings.length === 1) {
              var warningHtml = '<div class="alert alert-warning" style="background:#ffc107;color:#222;">';
              warningHtml += '<strong>Single Building User</strong><br>';
              warningHtml += 'This user is only associated with the current building. Deleting will remove the user completely from the system.';
              warningHtml += '</div>';
              $('#single-building-message').html(warningHtml).show();
            } else {
              $('#single-building-message').hide();
            }
          } else {
            $('#user-details').html('<div class="alert alert-danger">User information not found.</div>');
            $('#user-buildings-list').html('');
            $('#single-building-message').hide();
          }
        },
        error: function() {
          $('#user-details').html('<div class="alert alert-danger">Error loading user information.</div>');
          $('#user-buildings-list').html('');
        }
      });
    }
    

    // Function to check and display guard information
    function checkAndDisplayGuardInfo(userId) {
      $.ajax({
        url: "{{url('get-user-guard-info')}}",
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
              
              $('#guard_select').append('<option value="' + guard.id + '">' + guardLabel + '</option>');
            });
            guardHtml += '</div>';
            
            $('#guard-assignments-list').html(guardHtml);
            $('#guard-info-section').show();
            $('#guard-selection-section').show();
          } else {
            $('#guard-assignments-list').html('<div class="alert alert-info">No guard assignments found for this user.</div>');
            $('#guard-info-section').show();
          }
        },
        error: function() {
          $('#guard-assignments-list').html('<div class="alert alert-danger">Error loading guard assignments.</div>');
          $('#guard-info-section').show();
        }
      });
    }

    // Handle role selection change
    $(document).on('change', '#role_select', function() {
      var selectedRole = $(this).val();
      if(selectedRole) {
        // Enable the "Remove Selected Role" option when a role is selected
        $('#remove_selected_role').prop('disabled', false);
        // Auto-select the remove selected role option
        $('#remove_selected_role').prop('checked', true);
        $('#confirm-delete-button').prop('disabled', false);
      } else {
        $('#remove_selected_role').prop('disabled', true).prop('checked', false);
        // Check if any other option is selected
        if(!$('input[name="delete_option"]:checked').length) {
          $('#confirm-delete-button').prop('disabled', true);
        }
      }
    });

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
        var selectedRole = $('#role_select').val();
        if(!selectedRole) {
          alert('Please select a role to remove first.');
          $(this).prop('checked', false);
          $('#confirm-delete-button').prop('disabled', true);
          return;
        }
      } else if(selectedOption === 'remove_selected_guard') {
        // Require guard selection for this option
        var selectedGuard = $('#guard_select').val();
        if(!selectedGuard) {
          alert('Please select a guard assignment to remove first.');
          $(this).prop('checked', false);
          $('#confirm-delete-button').prop('disabled', true);
          return;
        }
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
      
      var url = "{{url('delete-user-enhanced')}}";
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
      existingUserId = null;
      
      // Show all form fields and reset button text
      $('.add-form .form-group').show();
      $('#save-button').text('Save');
      
      // Remove any existing add_existing hidden inputs
      $('input[name="add_existing"]').remove();
      
      
      // Set default values for new user
      $('.modal-title').text('Add New Direct User');
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
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-user-status')}}",
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
            url: "{{url('get-user-by-email')}}",
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
                    
                    $('#first_name').val(user.first_name || '');
                    $('#last_name').val(user.last_name || '');
                    $('#phone').val(user.phone || '');
                    $('#gender').val(user.gender || '');
                    
                    // Make password optional for existing users
                    $('#password').removeAttr('required');
                    $('.password-update-label').text('(Leave blank to keep current password)');
                    
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
                        '<small><a href="#" id="create-new-instead" class="text-info">Want to create a new user instead? Click here</a></small>' +
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
                    $('#password').attr('required', true);
                    $('.password-update-label').text('');
                    
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
@endsection

@endsection


