@extends('layouts.admin')


@section('title')
    Guard List
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
            <h1>Security Guards</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Security Guards</li>
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
                @if(Auth::User()->role == 'BA' || Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Security')
                <button class="btn btn-sm btn-success right mr-2" data-toggle="modal" data-target="#addUserModal" {{ $created_counts >= $login_limit ? 'disabled' : '' }}>Add New Guard</button>
                @endif
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Building</th>
                    <th>Block</th>
                    <th>Gate</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Gender</th>
                    <th>Company</th>
                    <th>Shift</th>
                    <th>Status</th>
                     @if(Auth::User()->role == 'BA')
                    <th>Action</th>
                    @endif
                   
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  @forelse($guardsData as $entry)
                  <?php $i++; $bu = $entry->building_user; $guard = $entry->guard; ?>
                  <tr>
                    <td>{{$i}}</td>
                    <td>{{$building->name}}</td>
                    <td>{{ $guard && $guard->block ? $guard->block->name : '-' }}</td>
                    <td>{{ $guard && $guard->gate ? $guard->gate->name : '-' }}</td>
                    <td><img src="{{ $bu->user->photo ?? '' }}" style="width:40px"></td>
                    <td>{{ $bu->user->name ?? ( ($bu->user->first_name ?? '') . ' ' . ($bu->user->last_name ?? '') ) }}</td>
                    <td>{{ $bu->user->phone ?? '' }}</td>
                    <td>{{ $bu->user->email ?? '' }}</td>
                    <td>{{ $bu->user->gender ?? '' }}</td>
                    <td>{{ $bu->user->company_name ?? '' }}</td>
                    <td>{{ $guard ? $guard->shift : '-' }}</td>
                    <td>{{ $bu->status }}</td>
                       @if(Auth::User()->role == 'BA')
                    <td>
                   @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'security'))
                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$guard->id}}" data-building_id="{{$guard->building_id}}" 
                          data-block_id="{{$guard->block_id}}" data-gate_id="{{$guard->gate_id}}" data-shift="{{$guard->shift}}" data-user_email="{{$bu->user->email}}" 
                          data-user_id="{{$bu->user->id}}" data-user_name="{{ $bu->user->name ?? $bu->user->first_name }}" data-company_name="{{$bu->user->company_name}}" data-status="{{$bu->status}}"><i class="fa fa-edit"></i></button>
                        @if($guard->deleted_at)
                          <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="{{$guard->id}}" data-action="restore"><i class="fa fa-undo"></i></button>
                        @else
                          <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$guard->id}}" data-action="delete"><i class="fa fa-trash"></i></button>
                        @endif
                      @else
                     
                       @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'security'))
                        <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal" 
                          data-user_email="{{$bu->user->email}}" data-user_name="{{ $bu->user->name ?? $bu->user->first_name }}" data-user_id="{{$bu->user->id}}">
                          <i class="fa fa-plus"></i> Add Guard
                        </button>
                         @endif
                       
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
        <h5 class="modal-title" id="exampleModalLabel">Add New Guard</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{url('store-new-guard')}}" method="post" class="add-user-form">
        @csrf
        <div class="modal-body">
          <div class="error"></div>
          <div class="form-group">
            <label for="email_new" class="col-form-label">Email:</label>
            <div class="input-group">
              <input type="email" name="email" class="form-control" id="email_new" maxlength="40" placeholder="Email" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-info" id="check-existing-user">Check User</button>
              </div>
            </div>
            <div id="user-check-result" class="mt-2"></div>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">First Name:</label>
            <input type="text" name="first_name" id="first_name" class="form-control" placeholder="First Name" 
                          onkeypress="return event.charCode >= 65 && event.charCode <= 90 || event.charCode >= 97 && event.charCode <= 122 || event.charCode == 32" required>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Last Name:</label>
            <input type="text" name="last_name" id="last_name" class="form-control" placeholder="Last Name"
                          onkeypress="return event.charCode >= 65 && event.charCode <= 90 || event.charCode >= 97 && event.charCode <= 122 || event.charCode == 32" required>
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
            <label for="email" class="col-form-label">Company Name:</label>
            <input type="company_name" name="company_name" class="form-control" id="company_name" maxlength="40" placeholder="Company Name">
          </div>
          
          <input type="hidden" name="role" id="role" value="user">
          
           

            <div class="form-group">
              <label>Password <span class="password-update-label"></span></label>
              <div class="input-group">
                <input type="password" name="password" id="password" class="form-control password" maxlength="14" id="re_pass">
                <div class="input-group-append">
                  <div class="input-group-text">
                    <span class="show-password password-icon"><i class="fa fa-eye-slash"></i></span>
                    <span class="hide-password password-icon" style="display:none;"><i class="fa fa-eye"></i></span>
                  </div>
                </div>
              </div>
              <small class="form-text text-muted password-helper-text">Required for new users. Leave blank if using existing user.</small>
            </div>
            <div class="form-group">
            <label for="name" class="col-form-label">Shift:</label>
            <select name="shift" class="form-control" id="user-shift" required>
              <option value="Day">Day</option>
              <option value="Night">Night</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="name" class="col-form-label">Building:</label>
            <select name="building_id" id="user-building_id" class="form-control" required>
                <option value="{{$building->id}}">{{$building->name}}</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="name" class="col-form-label">Block:</label>
            <select name="block_id" id="user-block_id" class="form-control block" required>
                <option value="">--Select--</option>
                @forelse($building->blocks as $block)
                <option value="{{$block->id}}">{{$block->name}}</option>
                @empty
                @endforelse
            </select>
          </div>
          
          <div class="form-group">
            <label for="name" class="col-form-label">Gate:</label>
            <div class="gates">
                <select name="gate_id" id="user-gate_id" class="form-control" required>
                    <option value="">--Select--</option>
                </select>
            </div>
          </div>
          
          <div class="form-group">
            <label for="status" class="col-form-label">Status:</label>
            <select name="status" class="form-control" id="user-status" required>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <input type="hidden" name="new-user" id="new-user">
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
        <h5 class="modal-title" id="exampleModalLabel">Add New Guard</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{route('guard.store')}}" method="post" class="add-form">
        @csrf
        <div class="modal-body">
          
          <div class="form-group">
            <label for="email" class="col-form-label">User Email:</label>
            <div class="input-group">
              <input type="email" name="user_email" class="form-control" id="user_email" maxlength="40" placeholder="Email" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-primary" id="getUserData">Get User Data</button>
              </div>
            </div>
          </div>
          <div class="error text-danger"></div>
          <div class="form-group">
            <label for="email" class="col-form-label">User Name:</label>
            <input type="text" name="user_name" class="form-control" id="user_name" disabled required>
          </div>
          <div class="form-group">
            <label for="company_name" class="col-form-label">Company Name:</label>
            <input type="text" name="company_name" class="form-control" id="company_name_guard" maxlength="40" placeholder="Company Name">
          </div>
          <div class="form-group">
            <label for="password_guard" class="col-form-label">New Password:</label>
            <input type="password" name="password" class="form-control" id="password_guard" placeholder="Leave blank to keep current password" minlength="6">
            <small class="form-text text-muted">Leave blank if you don't want to change the password</small>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Shift:</label>
            <select name="shift" class="form-control" id="shift" required>
              <option value="Day">Day</option>
              <option value="Night">Night</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="name" class="col-form-label">Building:</label>
            <select name="building_id" id="building_id" class="form-control" required>
                <option value="{{$building->id}}">{{$building->name}}</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="name" class="col-form-label">Block:</label>
            <select name="block_id" id="block_id" class="form-control block" required>
                <option value="">--Select--</option>
                @forelse($building->blocks as $block)
                <option value="{{$block->id}}">{{$block->name}}</option>
                @empty
                @endforelse
            </select>
          </div>
          
          <div class="form-group">
            <label for="name" class="col-form-label">Gate:</label>
            <div class="gates">
                <select name="gate_id" id="gate_id" class="form-control" required>
                    <option value="">--Select--</option>
                </select>
            </div>
          </div>
          
          <div class="form-group">
            <label for="status" class="col-form-label">Status:</label>
            <select name="status" class="form-control" id="status" required>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          
          <input type="hidden" name="id" id="edit-id">
          <input type="hidden" name="user_id" id="user_id">
          <input type="hidden" name="building_id" id="building_id" value="{{$building->id}}">
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
      $('#delete-id').val(id);
      action= button.data('action');
      $('#delete-button').removeClass('btn-success');
      $('#delete-button').removeClass('btn-danger');
      $('.modal-title').text('Are you sure ?');
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

    $(document).on('click', '#delete-button', function() {
        var url = "{{ route('guard.destroy', '') }}";
        $.ajax({
            url: url + '/' + id,
            type: "DELETE",
            data: {
                '_token': token,
                'id': id,
                'action': action
            },
            success: function(data) {
                if (data.msg === 'success') {
                    window.location.reload();
                } else {
                    alert(data.msg); // show custom message from server
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Laravel validation-like error
                    var response = xhr.responseJSON;
                    if (response && response.msg) {
                        alert(response.msg); // show our restore blocking message
                    } else {
                        alert('An error occurred while processing your request.');
                    }
                } else {
                    alert('An unexpected error occurred.');
                }
            }
        });
    });

    
    $('#addModal').on('show.bs.modal', function (event) {
        $('.modal-title').text('Add New Guard');
    });

    $('#addModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');

      // Reset password field when Add New Guard modal (#addUserModal) opens
      $('#addUserModal').on('show.bs.modal', function (event) {
        // Clear all fields
        $('#email_new').val('');
        $('#first_name').val('');
        $('#last_name').val('');
        $('#phone').val('');
        $('#gender').val('');
        $('#company_name').val('');
        $('#password').val('');
        $('#user-check-result').html('');
        
        // Make password required for new guard
        $('#password').attr('required', 'required');
        $('.password-update-label').text('(Required for new user)');
        $('.password-helper-text').text('Required for new users. Leave blank if using existing user.');
      });
      
      // Clear fields first
      $('#user_email').val('');
      $('#user_name').val('');
      $('#company_name_guard').val('');
      $('#password_guard').val('');
      $('#shift').val('');
      $('#status').val('');
      
      $('.modal-title').text('Add New Guard');
      
      if(edit_id){
          $('.modal-title').text('Update Guard');
          
          // Populate fields with a small delay to ensure modal is fully loaded
          setTimeout(function() {
            $('#edit-id').val(edit_id);
            $('#user_email').val(button.data('user_email'));
            $('#user_name').val(button.data('user_name'));
            $('#company_name_guard').val(button.data('company_name'));
            $('#user_id').val(button.data('user_id'));
            $('#building_id').val(button.data('building_id'));
            $('#block_id').val(button.data('block_id'));
            $('#gate_id').val(button.data('gate_id'));
            $('#shift').val(button.data('shift'));
            $('#status').val(button.data('status'));
          }, 100);
      }
      var block_id = button.data('block_id');
      var gate_id = button.data('gate_id');
        $.ajax({
            url : "{{url('/get-gates')}}",
            type: "post",
            data : {'_token':token,'block_id':block_id,'gate_id':gate_id},
            success: function(data)
            {
                $('.gates').html(data);
            }
        });
        
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-event-status')}}",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });
        
    $('.add-form').on('submit', function (event) {
      if ($('#user_name').val().trim() === '') {
        event.preventDefault();
        $('.error').text('Customer Name is required. Please fetch user data.');
      }
    });
    
    // Fetch user data when clicking "Get User Data"
    $('#getUserData').on('click', function () {
      var email = $('#user_email').val().trim();
      if (email === '') {
        $('.error').text('Please enter an email to fetch user data.');
        return;
      }
      
      $('.error').text(''); // Clear previous errors
      
      $.ajax({
        url: '{{ url("get-user-by-email") }}', // Update with your actual route
        type: 'POST',
        data: { _token:token,email: email },
        success: function (response) {
          if (response.success) {
            $('#user_name').val(response.data.name);
            $('#user_id').val(response.data.id);
          } else {
            $('.error').text('User not found.');
            $('#user_name').val('');
          }
        },
        error: function () {
          $('.error').text('Error fetching user data.');
          $('#user_name').val('');
        }
      });
    });
    
    $(document).on('change','.block',function(){
        var block_id = $(this).val();
        var gate_id = '';
        $.ajax({
            url : "{{url('/get-gates')}}",
            type: "post",
            data : {'_token':token,'block_id':block_id,'gate_id':gate_id},
            success: function(data)
            {
                $('.gates').html(data);
            }
        });
    });
    
    // Check User functionality for Add New Guard modal
    $('#check-existing-user').on('click', function() {
        var email = $('#email_new').val().trim();
        
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
                $('#check-existing-user').prop('disabled', false).text('Check User');
                
                if (response.success && response.data) {
                    // User exists - populate form
                    var user = response.data;
                    $('#first_name').val(user.first_name || '');
                    $('#last_name').val(user.last_name || '');
                    $('#phone').val(user.phone || '');
                    $('#gender').val(user.gender || '');
                    
                    $('#user-check-result').html(
                        '<div class="alert alert-success">' +
                        '<strong>User Found!</strong><br>' +
                        'Name: ' + (user.first_name || '') + ' ' + (user.last_name || '') + '<br>' +
                        'Phone: ' + (user.phone || 'N/A') +
                        '</div>'
                    );
                } else {
                    // User not found
                    $('#user-check-result').html(
                        '<div class="alert alert-info">' +
                        '<strong>New User</strong><br>' +
                        'This email is not registered. A new guard user will be created.' +
                        '</div>'
                    );
                }

                // Update password field based on user existence
                if (response.success && response.data) {
                  // Existing user - password is optional
                  $('#password').removeAttr('required');
                  $('.password-update-label').text('(Optional - Leave blank to keep current password)');
                  $('.password-helper-text').text('Leave blank if you do not want to change the password');
                } else {
                  // New user - password is required
                  $('#password').attr('required', 'required');
                  $('.password-update-label').text('(Required for new user)');
                  $('.password-helper-text').text('Required for new users. Leave blank if using existing user.');
                }
            },
            error: function() {
                $('#check-existing-user').prop('disabled', false).text('Check User');
                $('#user-check-result').html('<div class="alert alert-danger">Error checking user. Please try again.</div>');
            }
        });
    });

  });
</script>
@endsection

@endsection
