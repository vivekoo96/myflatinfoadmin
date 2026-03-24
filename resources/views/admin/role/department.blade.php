@extends('layouts.admin')


@section('title')
    {{$role->slug == 'president' ? 'President' : $role->name }} List
@endsection

@section('style')
<style>
/* Select2 Custom Styling */
.select2-container--bootstrap4 .select2-selection--multiple {
    min-height: 38px;
}

.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__rendered {
    padding-left: 12px;
    padding-right: 12px;
}

.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
    background-color: #3c5795;
    border-color: #3c5795;
    color: white;
}

.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
    color: white;
}

.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #dc3545;
}
</style>
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
            <h1>{{$role->slug == 'president' ? 'President' : $role->name}}</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">{{$role->slug == 'president' ? 'President' : $role->name}}</li>
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
               <?php 
                      $created_counts = \App\Models\BuildingUser::where('building_id', Auth::User()->building_id)
                      ->whereHas('role', function($query) {
                        $query->where('slug', '!=', 'user');
                      })->count();
                    $login_limit = Auth::user()->building->no_of_other_users;
                ?>
                <span>{{$created_counts}}/{{$login_limit}}</span>
                <!--<button class="btn btn-sm btn-success right mr-2" data-toggle="modal" data-target="#addUserModal" {{ $created_counts >= $login_limit ? 'disabled' : '' }}>Add New User</button>-->
                 @if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "Issue Tracker")
                <button class="btn btn-sm btn-success right mr-2" data-toggle="modal" data-target="#addUserModal" {{ $created_counts >= $login_limit ? 'disabled' : '' }}>Add New {{$role->slug == 'president' ? 'President' : $role->name}}</button>
                 <button class="btn btn-sm btn-success right mr-2" data-toggle="modal" data-target="#addModal" {{ $created_counts >= $login_limit ? 'disabled' : '' }}>Add {{$role->slug == 'president' ? 'President' : $role->name}} By Email</button>
                @endif
              </div>
              <!-- /.card-header -->
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
                    <th>Company</th>
                    <th>City</th>
                    <th>Address</th>
                    <th>Status</th>
                     @if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "Issue Tracker")
                    <th>Action</th>
                    @endif
                  </tr>
                  </thead>
                  <tbody id="department-table-body">
                    
                    <?php $i = 0; ?>
                  @forelse($building_users as $item)
                  <?php $i++; ?>
                  <tr data-building_user_id="{{$item->id}}" data-user_id="{{$item->user->id}}">
                    <td>{{$i}}</td>
                    <td>
                      
                      @if($item->user->photo)
                        <img src="{{$item->user->photo}}" alt="Profile" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                      @else
                        <div class="bg-secondary text-white text-center" style="width: 50px; height: 50px; line-height: 50px; border-radius: 4px;">
                          {{strtoupper(substr($item->user->name, 0, 1))}}
                        </div>
                      @endif
                    </td>
                    <td>{{$item->user->name}}</td>
                    <td>{{$item->user->phone}}</td>
                    <td>{{$item->user->email}}</td>
                    <td>{{$item->user->gender}}</td>
                    <td>{{$item->user->company_name}}</td>
                    <td>{{$item->user->city ? $item->user->city->name : 'N/A'}}</td>
                    <td>
                      <span class="text-truncate" style="max-width: 150px; display: inline-block;" title="{{$item->user->address ?? 'N/A'}}">
                        {{$item->user->address ?? 'N/A'}}
                      </span>
                    </td>
                    <td>
                      <span class="badge badge-{{$item->status == 'Active' ? 'success' : 'danger'}}">
                        {{$item->status ?? 'Active'}}
                      </span>
                    </td>
                      @if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "Issue Tracker")
                    <td>
                    
                      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" 
                              data-id="{{$item->id}}" 
                              data-user_name="{{$item->user->name}}" 
                              data-first_name="{{$item->user->first_name ?? 'No First Name'}}" 
                              data-last_name="{{$item->user->last_name ?? 'No Last Name'}}" 
                              data-user_email="{{$item->user->email}}" 
                              data-user_id="{{$item->user->id}}"
                              data-building_user_id="{{$item->id}}"
                              data-phone="{{$item->user->phone}}"
                              data-gender="{{$item->user->gender}}"
                              data-city_id="{{$item->user->city_id}}"
                              data-address="{{$item->user->address}}"
                              data-company="{{$item->user->company_name}}"
                              data-status="{{$item->status ?? 'Active'}}"
                              data-image="{{$item->user->photo}}"
                              title="Edit {{$role->slug == 'president' ? 'President' : $role->name}}">
                        <i class="fa fa-edit"></i>
                      </button>
                      @if($item->deleted_at)
                      <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="{{$item->id}}" data-building_user_id="{{$item->id}}" data-action="restore"><i class="fa fa-undo"></i></button>
                      @else
                      <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$item->id}}" data-building_user_id="{{$item->id}}" data-action="delete"><i class="fa fa-trash"></i></button>
                      @endif
                     
                    </td>
                     @endif

                  </tr>
                  @empty
                  @endforelse
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
    

<!--Add User-->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add User</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{url('store-user')}}" method="post" class="add-user-form" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="error"></div>
          <div class="form-group">
            <label for="name" class="col-form-label">First Name:</label>
            <input type="text" name="first_name" id="first_name" class="form-control" placeholder="First Name" minlength="3" maxlength="20"
                          onkeypress="return event.charCode >= 65 && event.charCode <= 90 || event.charCode >= 97 && event.charCode <= 122 || event.charCode == 32" required>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Last Name:</label>
            <input type="text" name="last_name" id="last_name" class="form-control" placeholder="Last Name" minlength="3" maxlength="20"
                          onkeypress="return event.charCode >= 65 && event.charCode <= 90 || event.charCode >= 97 && event.charCode <= 122 || event.charCode == 32" required>
          </div>
          <div class="form-group">
            <label for="email" class="col-form-label">Email:</label>
            <input type="email" name="email" class="form-control" id="email" maxlength="40" placeholder="Email" required>
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Phone:</label>
            <input type="text" name="phone" class="form-control" id="phone" value="{{old('phone')}}" placeholder="Phone" minlength="10" maxlength="10" 
                                      onkeypress="return event.charCode >= 48 && event.charCode <= 57" required />
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Gender:</label>
            <select name="gender" class="form-control" id="gender" required>
                <option value="">--Select Gender--</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Others">Others</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="city_id" class="col-form-label">City:</label>
            <select name="city_id" id="city_id" class="form-control" required>
              <option value="">--Select City--</option>
              @forelse($cities as $city)
                <option value="{{$city->id}}">{{$city->name}}</option>
              @empty
              @endforelse
            </select>
          </div>
          
          <div class="form-group">
            <label for="address" class="col-form-label">Address:</label>
            <textarea name="address" id="address" class="form-control" placeholder="Enter address" required></textarea>
          </div>
          
          <div class="form-group">
            <label for="email" class="col-form-label">Company Name:</label>
            <input type="text" name="company_name" class="form-control" id="company_name_add" maxlength="40" placeholder="Company Name">
          </div>

          <div class="form-group">
            <label for="image" class="col-form-label">Image:</label>
            <input type="file" name="image" class="form-control" id="image" accept="image/*">
            <small class="text-muted">Upload profile image (optional)</small>
          </div>

          <div class="form-group">
            <label for="status" class="col-form-label">Status:</label>
            <select name="status" class="form-control" id="status" required>
                <option value="">--Select Status--</option>
                <option value="Active" selected>Active</option>
                <option value="Inactive">Inactive</option>
            </select>
          </div>


          <input type="hidden" name="role" id="role" value="user">
            <div class="form-group">
                <label>Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control password" minlength="8" maxlength="14" id="re_pass" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="show-password password-icon"><i class="fa fa-eye-slash"></i></span>
                            <span class="hide-password password-icon" style="display:none;"><i class="fa fa-eye"></i></span>
                        </div>
                    </div>
                </div>
            </div>
          <input type="hidden" name="created_type" id="created_type" value="other">
          <input type="hidden" name="role_id" id="role-id" value="{{$role->id}}">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="save-button">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Modal -->

<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add New {{$role->slug == 'president' ? 'President' : $role->name}}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{url('store-user-role')}}" method="post" class="add-form" enctype="multipart/form-data" id="user-role-form">
        @csrf
        <div class="modal-body">
          <div class="error"></div>
          <div class="form-group">
            <label for="email" class="col-form-label">User Email:</label>
            <div class="input-group">
              <input type="email" name="user_email" class="form-control" id="user_email" maxlength="40" placeholder="User Email" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-primary" id="getUserData">Get User Data</button>
              </div>
            </div>
          </div>
          <div class="user_error text-danger"></div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="first_name_email" class="col-form-label">First Name:</label>
                <input type="text" name="first_name" class="form-control" id="first_name_email" readonly required style="background-color: white !important; color: black !important;">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="last_name_email" class="col-form-label">Last Name:</label>
                <input type="text" name="last_name" class="form-control" id="last_name_email" readonly required style="background-color: white !important; color: black !important;">
              </div>
            </div>
          </div>
          
          <!-- Additional fields for complete user management -->
          <div class="form-group">
            <label for="phone_update" class="col-form-label">Phone:</label>
            <input type="text" name="phone" class="form-control" id="phone_update" placeholder="Phone" minlength="10" maxlength="10" 
                                  onkeypress="return event.charCode >= 48 && event.charCode <= 57">
          </div>
          
          <div class="form-group">
            <label for="gender_update" class="col-form-label">Gender:</label>
            <select name="gender" class="form-control" id="gender_update">
                <option value="">--Select Gender--</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Others">Others</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="city_id_update" class="col-form-label">City:</label>
            <select name="city_id" id="city_id_update" class="form-control">
              <option value="">--Select City--</option>
              @forelse($cities as $city)
                <option value="{{$city->id}}">{{$city->name}}</option>
              @empty
              @endforelse
            </select>
          </div>
          
          <div class="form-group">
            <label for="address_update" class="col-form-label">Address:</label>
            <textarea name="address" id="address_update" class="form-control" placeholder="Enter address"></textarea>
          </div>
          
          <div class="form-group">
            <label for="password_update" class="col-form-label">New Password:</label>
            <div class="input-group">
              <input type="password" name="password" class="form-control password" id="password_update" placeholder="Leave blank to keep current password" minlength="6">
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="show-password password-icon"><i class="fa fa-eye-slash"></i></span>
                  <span class="hide-password password-icon" style="display:none;"><i class="fa fa-eye"></i></span>
                </div>
              </div>
            </div>
            <small class="form-text text-muted">Leave blank if you don't want to change the password</small>
          </div>
          
          <div class="form-group">
            <label for="company_name_update" class="col-form-label">Company Name:</label>
            <input type="text" name="company_name" class="form-control" id="company_name_update" maxlength="40" placeholder="Company Name">
          </div>

          <div class="form-group">
            <label for="image_update" class="col-form-label">Image:</label>
            <input type="file" name="image" class="form-control" id="image_update" accept="image/*">
            <small class="text-muted">Upload profile image (optional)</small>
            <div id="current_image_preview" style="margin-top: 10px;"></div>
          </div>

          <div class="form-group">
            <label for="status_update" class="col-form-label">Status:</label>
            <select name="status" class="form-control" id="status_update" required>
                <option value="">--Select Status--</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
          </div>

          
          <input type="hidden" name="id" id="edit-id">
          <input type="hidden" name="user_id" id="user_id" value="">
          <input type="hidden" name="role_id" id="role_id" value="{{$role->id}}">
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


@section('script')


<script>
  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "{{csrf_token()}}";
    
    
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
      var url = "{{url('delete-user-role')}}";
      $.ajax({
        url : url,
        type: "POST",
        data : {'_token':token,'id':id,'action':action},
        success: function(data)
        {
          $('#deleteModal').modal('hide');
          if(data && data.success){
            if(action == 'delete'){
              // remove the row for this building_user id
              $('tr[data-building_user_id="'+id+'"]').remove();
            } else {
              // for restore, reload to reflect restored state
              window.location.reload();
            }
          } else {
            alert(data.message || 'An error occurred');
          }
        },
        error: function(){
          alert('An error occurred while processing the request');
        }
      });
    });

    // AJAX submit for Add By Email form so we can append the new assignment row
    $(document).on('submit', '#user-role-form', function(e){
      e.preventDefault();
      var form = $(this)[0];
      var formData = new FormData(form);
      var url = $(this).attr('action');
      // Debug: log action and ensure id is present when editing
      console.log('Submitting user-role-form to:', url);
      // If the hidden edit id exists but FormData doesn't have id, add it
      try {
        var fdId = formData.get('id');
        var editId = $('#edit-id').val();
        console.log('FormData id:', fdId, 'edit-id:', editId);
        if ((!fdId || fdId === null || fdId === '') && editId) {
          formData.append('id', editId);
          console.log('Appended id to FormData:', editId);
        }
      } catch(err) {
        console.log('FormData inspection error:', err);
      }
      $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response){
          // Do NOT append a transient row in the client. Reload to fetch canonical server data.
          if (response && response.success) {
            // Close modal then reload so the table reflects DB state
            $('#addModal').modal('hide');
            window.location.reload();
            return;
          }
          alert(response.message || 'An error occurred');
        },
        error: function(xhr){
          var msg = 'An error occurred while processing the request';
          if(xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
          alert(msg);
        }
      });
    });

    $('#addModal').on('show.bs.modal', function (event) {
      var role = "{{$role->slug == 'president' ? 'President' : $role->name}}";
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      
      // Clear all fields first
      $('#user_email').val('');
      $('#first_name_email').val('');
      $('#last_name_email').val('');
      $('#phone_update').val('');
      $('#gender_update').val('');
      $('#city_id_update').val('');
      $('#address_update').val('');
      $('#password_update').val('');
      $('#company_name_update').val('');
      $('#status_update').val('');
      $('#current_image_preview').html('');
      
      $('#edit-id').val(edit_id);
      $('.modal-title').text('Add ' + role + ' By Email');
      $('#user-role-form').attr('action', '{{url("store-user-role")}}');
      
      if(edit_id){
          // Editing existing user - populate fields and change form action
          $('.modal-title').text('Update ' + role);
          $('#user-role-form').attr('action', '{{url("update-user-role")}}');
          
          // Hide "Get User Data" button in edit mode and make all fields editable
          $('#getUserData').hide();
          $('#first_name_email').prop('readonly', false);
          $('#last_name_email').prop('readonly', false);
          
          // Populate all fields with existing data
          $('#user_email').val(button.data('user_email') || '');
          $('#first_name_email').val(button.data('first_name') || '');
          $('#last_name_email').val(button.data('last_name') || '');
          $('#user_email').val(button.data('user_email'));
          $('#user_id').val(button.data('user_id'));
          
          // Populate additional fields from data attributes with a small delay
          setTimeout(function() {
            var phoneVal = button.data('phone') || '';
            var genderVal = button.data('gender') || '';
            var cityVal = button.data('city_id') || '';
            var addressVal = button.data('address') || '';
            var companyVal = button.data('company') || '';
            var statusVal = button.data('status') || 'Active';
            
            // Debug log to check data values
            console.log('Edit Data:', {
              phone: phoneVal,
              gender: genderVal,
              city_id: cityVal,
              address: addressVal,
              company: companyVal,
              status: statusVal
            });
            
            $('#phone_update').val(phoneVal);
            $('#gender_update').val(genderVal);
            $('#city_id_update').val(cityVal);
            $('#address_update').val(addressVal);
            $('#company_name_update').val(companyVal);
            $('#status_update').val(statusVal);
            
            // Force trigger change events to ensure fields are updated
            $('#gender_update').trigger('change');
            $('#city_id_update').trigger('change');
            $('#company_name_update').trigger('change');
            $('#status_update').trigger('change');
            
            // Verify fields were populated
            console.log('Field Values After Population:', {
              phone: $('#phone_update').val(),
              gender: $('#gender_update').val(),
              city: $('#city_id_update').val(),
              address: $('#address_update').val(),
              company: $('#company_name_update').val(),
              status: $('#status_update').val()
            });
          }, 100);
          
          
          // Show current image if exists
          if(button.data('image')) {
            var imageUrl = button.data('image');
            $('#current_image_preview').html('<img src="' + imageUrl + '" alt="Current Image" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;"><br><small class="text-muted">Current Image</small>');
          }
      } else {
          // Adding new user - show "Get User Data" button and set fields to readonly initially
          $('#getUserData').show();
          $('#first_name_email').prop('readonly', true);
          $('#last_name_email').prop('readonly', true);
      }
    });
    
    $('#addUserModal').on('show.bs.modal', function (event) {
      var role = "{{$role->slug == 'president' ? 'President' : $role->name}}";
      $('.modal-title').text('Add New ' + role);
    });
    
    
    $('.add-form').on('submit', function (event) {
      if ($('#first_name_email').val().trim() === '' || $('#last_name_email').val().trim() === '') {
        event.preventDefault();
        $('.error').text('First Name and Last Name are required. Please fetch user data.');
      } else {
        // Debug: Log form action and data
        console.log('Form Action:', $(this).attr('action'));
        console.log('Edit ID:', $('#edit-id').val());
        console.log('User ID:', $('#user_id').val());
        console.log('Status:', $('#status_update').val());
      }
    });
    
    // Fetch user data when clicking "Get Owner Data"
    $('#getUserData').on('click', function () {
      var user_email = $('#user_email').val().trim();
      if (user_email === '') {
        $('.user_error').text('Please enter an email to fetch user data.');
        return;
      }
      
      $('.user_error').text(''); // Clear previous errors
      
      $.ajax({
        url: '{{ url("get-user-by-email") }}', // Update with your actual route
        type: 'POST',
        data: {'_token':token, email: user_email },
        success: function (response) {
          if (response.success) {
            // Debug: Log the response data
            console.log('AJAX Response:', response.data);
            console.log('First Name from API:', response.data.first_name);
            console.log('Last Name from API:', response.data.last_name);
            
            // Test if fields exist
            console.log('AJAX - First name field exists:', $('#first_name_email').length);
            console.log('AJAX - Last name field exists:', $('#last_name_email').length);
            
            // Force set values for testing
            $('#first_name_email').val(response.data.first_name || 'API TEST FIRST');
            $('#last_name_email').val(response.data.last_name || 'API TEST LAST');
            
            // Check if values were set
            console.log('AJAX - First name field value after setting:', $('#first_name_email').val());
            console.log('AJAX - Last name field value after setting:', $('#last_name_email').val());
            $('#user_id').val(response.data.id);
            
            // Populate additional fields if available
            $('#phone_update').val(response.data.phone || '');
            $('#gender_update').val(response.data.gender || '');
            $('#city_id_update').val(response.data.city_id || '');
            $('#address_update').val(response.data.address || '');
            $('#company_name_update').val(response.data.company_name || '');
            $('#status_update').val(response.data.status || 'Active');
            
            // Trigger change events to ensure dropdowns update properly
            $('#gender_update').trigger('change');
            $('#city_id_update').trigger('change');
            
            // Show current image if exists
            if(response.data.image) {
              $('#current_image_preview').html('<img src="' + response.data.image_url + '" alt="Current Image" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;"><br><small class="text-muted">Current Image</small>');
            }
          } else {
            $('.user_error').text('User not found.');
            $('#user_name').val('');
            // Clear other fields
            $('#phone_update').val('');
            $('#gender_update').val('');
            $('#city_id_update').val('');
            $('#address_update').val('');
            $('#company_name_update').val('');
            $('#status_update').val('');
            $('#current_image_preview').html('');
          }
        },
        error: function () {
          $('.user_error').text('Error fetching user data.');
          $('#user_name').val('');
        }
      });
    });
    

  });
</script>
@endsection

@endsection


